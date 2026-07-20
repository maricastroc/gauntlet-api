<?php

declare(strict_types=1);

use App\Models\Fixture;
use App\Models\Group;
use App\Models\Stage;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/*
 * The write half of the redis stream transport: a committed save must push the new revision onto
 * the tournament's Redis channel — but ONLY under the redis driver, and never in a way that could
 * break the save. Kept local so this file runs standalone (mirrors TournamentRevisionTest).
 *
 * @return array{tournament: Tournament, fixtures: array<int,Fixture>}
 */
function seedPublishGroup(User $owner): array
{
    $tournament = Tournament::create(['user_id' => $owner->id, 'name' => 'Atlas Cup 2026']);
    $stage = Stage::create(['tournament_id' => $tournament->id, 'type' => 'group', 'name' => 'Group stage']);
    $group = Group::create(['stage_id' => $stage->id, 'name' => 'A', 'qualify_count' => 2]);

    $teams = [];
    foreach (['Brasil', 'Croácia', 'Marrocos', 'Japão'] as $name) {
        $teams[$name] = Team::create(['tournament_id' => $tournament->id, 'name' => $name]);
    }
    $group->teams()->attach(collect($teams)->pluck('id'));

    $make = fn (Team $home, Team $away) => Fixture::create([
        'tournament_id' => $tournament->id,
        'stage_id' => $stage->id,
        'group_id' => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'status' => 'scheduled',
    ]);

    return [
        'tournament' => $tournament,
        'fixtures' => [
            1 => $make($teams['Brasil'], $teams['Marrocos']),
            2 => $make($teams['Croácia'], $teams['Japão']),
        ],
    ];
}

test('a committed save publishes the new revision to redis under the redis driver', function () {
    config(['sse.driver' => 'redis', 'sse.redis_connection' => 'default']);

    $owner = User::factory()->create();
    ['tournament' => $t, 'fixtures' => $f] = seedPublishGroup($owner);
    $tid = (int) $t->id;
    Sanctum::actingAs($owner);

    $connection = Mockery::mock();
    $connection->shouldReceive('publish')->once()
        ->with("tournament.{$tid}", Mockery::on(function (string $payload) {
            $data = json_decode($payload, true);

            return is_array($data) && ($data['revision'] ?? null) === 1 && ($data['type'] ?? null) === 'result';
        }));
    Redis::shouldReceive('connection')->with('default')->andReturn($connection);

    $this->putJson("/api/matches/{$f[1]->id}/result", [
        'home_score' => 2, 'away_score' => 0, 'expected_version' => 0,
    ])->assertOk();
});

test('a committed save never touches redis under the default poll driver', function () {
    config(['sse.driver' => 'poll']);
    Redis::shouldReceive('connection')->never();

    $owner = User::factory()->create();
    ['fixtures' => $f] = seedPublishGroup($owner);
    Sanctum::actingAs($owner);

    $this->putJson("/api/matches/{$f[1]->id}/result", [
        'home_score' => 2, 'away_score' => 0, 'expected_version' => 0,
    ])->assertOk();
});
