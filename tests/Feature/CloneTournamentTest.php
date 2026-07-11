<?php

declare(strict_types=1);

use App\Actions\Tournament\CloneTournament;
use App\Models\Fixture;
use App\Models\Group;
use App\Models\Stage;
use App\Models\Team;
use App\Models\Tie;
use App\Models\Tournament;
use Database\Seeders\TournamentDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/** @return array<string, int> */
function graphCounts(Tournament $tournament): array
{
    $stageIds = Stage::where('tournament_id', $tournament->id)->pluck('id');
    $groupIds = Group::whereIn('stage_id', $stageIds)->pluck('id');

    return [
        'teams' => Team::where('tournament_id', $tournament->id)->count(),
        'stages' => $stageIds->count(),
        'groups' => $groupIds->count(),
        'ties' => Tie::whereIn('stage_id', $stageIds)->count(),
        'fixtures' => Fixture::where('tournament_id', $tournament->id)->count(),
        'pivot' => DB::table('group_team')->whereIn('group_id', $groupIds)->count(),
    ];
}

beforeEach(function () {
    $this->seed(TournamentDemoSeeder::class);
    $this->template = Tournament::where('is_demo_template', true)->firstOrFail();
});

test('clones the whole graph with identical counts and leaves the source untouched', function () {
    $before = graphCounts($this->template);

    $clone = (new CloneTournament)->handle($this->template);

    expect($clone->id)->not->toBe($this->template->id)
        ->and($clone->is_demo_template)->toBeFalse()
        ->and(graphCounts($clone))->toBe($before)
        ->and(graphCounts($this->template->fresh()))->toBe($before);
});

test('every cloned reference points inside the clone, never the source', function () {
    $clone = (new CloneTournament)->handle($this->template);

    $stageIds = Stage::where('tournament_id', $clone->id)->pluck('id');
    $teamIds = Team::where('tournament_id', $clone->id)->pluck('id')->all();
    $groupIds = Group::whereIn('stage_id', $stageIds)->pluck('id')->all();
    $tieIds = Tie::whereIn('stage_id', $stageIds)->pluck('id')->all();

    foreach (Fixture::where('tournament_id', $clone->id)->get() as $fixture) {
        expect($fixture->stage_id)->toBeIn($stageIds->all());

        if ($fixture->group_id !== null) {
            expect($fixture->group_id)->toBeIn($groupIds);
        }
        if ($fixture->tie_id !== null) {
            expect($fixture->tie_id)->toBeIn($tieIds);
        }
        if ($fixture->home_team_id !== null) {
            expect($fixture->home_team_id)->toBeIn($teamIds);
        }
        if ($fixture->away_team_id !== null) {
            expect($fixture->away_team_id)->toBeIn($teamIds);
        }
    }
});

test('rewrites winner: tie references to the cloned tie ids', function () {
    $clone = (new CloneTournament)->handle($this->template);

    $cloneStageIds = Stage::where('tournament_id', $clone->id)->pluck('id');
    $cloneTieIds = Tie::whereIn('stage_id', $cloneStageIds)->pluck('id')->all();

    $templateStageIds = Stage::where('tournament_id', $this->template->id)->pluck('id');
    $templateTieIds = Tie::whereIn('stage_id', $templateStageIds)->pluck('id')->all();

    $downstream = Tie::whereIn('stage_id', $cloneStageIds)
        ->where('home_source', 'like', 'winner:%')
        ->get();

    expect($downstream)->not->toBeEmpty();

    foreach ($downstream as $tie) {
        $refId = (int) substr($tie->home_source, strlen('winner:'));

        expect($refId)->toBeIn($cloneTieIds)
            ->and($refId)->not->toBeIn($templateTieIds);
    }
});

test('preserves played scores and resets the optimistic-lock version', function () {
    $clone = (new CloneTournament)->handle($this->template);

    $knockoutStageId = Stage::where('tournament_id', $clone->id)->where('type', 'knockout')->value('id');
    $firstTie = Tie::where('stage_id', $knockoutStageId)->where('round', 1)->where('slot', 1)->firstOrFail();
    $fixture = Fixture::where('tie_id', $firstTie->id)->firstOrFail();

    expect($fixture->home_score)->toBe(3)
        ->and($fixture->away_score)->toBe(1)
        ->and($fixture->status)->toBe('finished')
        ->and($fixture->version)->toBe(0);
});
