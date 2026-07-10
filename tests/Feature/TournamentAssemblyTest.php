<?php

declare(strict_types=1);

use App\Models\Fixture;
use App\Models\Stage;
use App\Models\Team;
use App\Models\Tie;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/** Cria um torneio com 8 times (4 grupos de 2) pertencente a $owner. */
function ownedTournamentWithTeams(User $owner): array
{
    $tournament = Tournament::create(['user_id' => $owner->id, 'name' => 'My Cup']);
    $teams = collect(['A1', 'A2', 'B1', 'B2', 'C1', 'C2', 'D1', 'D2'])
        ->map(fn (string $name) => Team::create(['tournament_id' => $tournament->id, 'name' => $name]));

    return [$tournament, $teams];
}

/** @param  \Illuminate\Support\Collection<int, Team>  $teams */
function fourGroupsPayload($teams): array
{
    return [
        'qualify_count' => 2,
        'groups' => [
            ['name' => 'A', 'team_ids' => [$teams[0]->id, $teams[1]->id]],
            ['name' => 'B', 'team_ids' => [$teams[2]->id, $teams[3]->id]],
            ['name' => 'C', 'team_ids' => [$teams[4]->id, $teams[5]->id]],
            ['name' => 'D', 'team_ids' => [$teams[6]->id, $teams[7]->id]],
        ],
    ];
}

/* -------------------------- create / list -------------------------- */

test('criar torneio exige autenticação', function () {
    $this->postJson('/api/tournaments', ['name' => 'My Cup'])->assertUnauthorized();
});

test('o organizador cria um torneio em rascunho', function () {
    Sanctum::actingAs($owner = User::factory()->create());

    $this->postJson('/api/tournaments', ['name' => 'My Cup'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'My Cup')
        ->assertJsonPath('data.status', 'draft');

    expect(Tournament::where('user_id', $owner->id)->where('name', 'My Cup')->exists())->toBeTrue();
});

test('nome vazio é rejeitado (422)', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/tournaments', ['name' => ''])->assertStatus(422);
});

test('a listagem traz só os torneios do organizador', function () {
    $owner = User::factory()->create();
    Tournament::create(['user_id' => $owner->id, 'name' => 'Mine 1']);
    Tournament::create(['user_id' => $owner->id, 'name' => 'Mine 2']);
    Tournament::create(['user_id' => User::factory()->create()->id, 'name' => 'Someone else']);

    Sanctum::actingAs($owner);
    $this->getJson('/api/tournaments')->assertOk()->assertJsonCount(2, 'data');
});

/* -------------------------- teams -------------------------- */

test('quem não é dono não adiciona times (403)', function () {
    $tournament = Tournament::create(['user_id' => User::factory()->create()->id, 'name' => 'My Cup']);
    Sanctum::actingAs(User::factory()->create());

    $this->postJson("/api/tournaments/{$tournament->id}/teams", [
        'teams' => [['name' => 'Brazil', 'code' => 'BRA', 'flag' => '🇧🇷']],
    ])->assertForbidden();
});

test('o dono adiciona times em lote', function () {
    Sanctum::actingAs($owner = User::factory()->create());
    $tournament = Tournament::create(['user_id' => $owner->id, 'name' => 'My Cup']);

    $this->postJson("/api/tournaments/{$tournament->id}/teams", [
        'teams' => [
            ['name' => 'Brazil', 'code' => 'BRA', 'flag' => '🇧🇷'],
            ['name' => 'Japan'],
        ],
    ])->assertCreated()->assertJsonCount(2, 'data')->assertJsonPath('data.0.code', 'BRA');

    expect(Team::where('tournament_id', $tournament->id)->count())->toBe(2);
});

/* -------------------------- group stage -------------------------- */

test('monta a fase de grupos e gera o returno-único', function () {
    Sanctum::actingAs($owner = User::factory()->create());
    [$tournament, $teams] = ownedTournamentWithTeams($owner);

    $this->postJson("/api/tournaments/{$tournament->id}/group-stage", fourGroupsPayload($teams))
        ->assertOk()
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.stages.0.type', 'group')
        ->assertJsonCount(4, 'data.stages.0.groups');

    // 4 grupos de 2 => 1 jogo por grupo => 4 jogos, todos 'scheduled'
    expect(Fixture::where('tournament_id', $tournament->id)->count())->toBe(4);
    expect(Fixture::where('tournament_id', $tournament->id)->where('status', 'scheduled')->count())->toBe(4);
    expect($tournament->fresh()->status)->toBe('active');
});

test('não deixa montar a fase de grupos duas vezes (422)', function () {
    Sanctum::actingAs($owner = User::factory()->create());
    [$tournament, $teams] = ownedTournamentWithTeams($owner);

    $this->postJson("/api/tournaments/{$tournament->id}/group-stage", fourGroupsPayload($teams))->assertOk();
    $this->postJson("/api/tournaments/{$tournament->id}/group-stage", fourGroupsPayload($teams))->assertStatus(422);
});

test('rejeita team_ids de outro torneio', function () {
    Sanctum::actingAs($owner = User::factory()->create());
    [$tournament] = ownedTournamentWithTeams($owner);
    $foreign = Team::create(['tournament_id' => Tournament::create(['user_id' => $owner->id, 'name' => 'Other'])->id, 'name' => 'X']);

    $this->postJson("/api/tournaments/{$tournament->id}/group-stage", [
        'qualify_count' => 2,
        'groups' => [['name' => 'A', 'team_ids' => [$foreign->id, $foreign->id]]],
    ])->assertStatus(422);
});

/* -------------------------- knockout -------------------------- */

test('gera o mata-mata a partir dos grupos, com winner: resolvido para ids reais', function () {
    Sanctum::actingAs($owner = User::factory()->create());
    [$tournament, $teams] = ownedTournamentWithTeams($owner);
    $this->postJson("/api/tournaments/{$tournament->id}/group-stage", fourGroupsPayload($teams))->assertOk();

    $this->postJson("/api/tournaments/{$tournament->id}/knockout")
        ->assertOk()
        ->assertJsonPath('data.stages.1.type', 'knockout')
        ->assertJsonCount(7, 'data.stages.1.ties'); // 4 QF + 2 SF + 1 final

    $knockout = Stage::where('tournament_id', $tournament->id)->where('type', 'knockout')->firstOrFail();
    $ties = Tie::where('stage_id', $knockout->id)->get();

    // quartas semeadas por grupo
    $qf1 = $ties->firstWhere(fn (Tie $t) => $t->round === 1 && $t->slot === 1);
    expect($qf1->home_source)->toBe('seed:A1')->and($qf1->away_source)->toBe('seed:B2');

    // semifinal referencia ids REAIS das quartas, não placeholders
    $sf1 = $ties->firstWhere(fn (Tie $t) => $t->round === 2 && $t->slot === 1);
    $slot1 = $ties->firstWhere(fn (Tie $t) => $t->round === 1 && $t->slot === 1)->id;
    $slot2 = $ties->firstWhere(fn (Tie $t) => $t->round === 1 && $t->slot === 2)->id;
    expect($sf1->home_source)->toBe("winner:{$slot1}")->and($sf1->away_source)->toBe("winner:{$slot2}");

    // um jogo 'scheduled' por tie
    expect(Fixture::where('stage_id', $knockout->id)->count())->toBe(7);
});

test('não gera mata-mata sem fase de grupos (422)', function () {
    Sanctum::actingAs($owner = User::factory()->create());
    $tournament = Tournament::create(['user_id' => $owner->id, 'name' => 'Empty']);

    $this->postJson("/api/tournaments/{$tournament->id}/knockout")->assertStatus(422);
});

/* -------------------------- detail read -------------------------- */

test('a visão completa do torneio é pública (visão torcedor)', function () {
    $owner = User::factory()->create();
    [$tournament, $teams] = ownedTournamentWithTeams($owner);
    // monta direto pela Action (sem autenticar), depois lê sem token
    app(\App\Actions\Tournament\BuildGroupStage::class)->handle($tournament, 2, [
        ['name' => 'A', 'team_ids' => [$teams[0]->id, $teams[1]->id]],
        ['name' => 'B', 'team_ids' => [$teams[2]->id, $teams[3]->id]],
        ['name' => 'C', 'team_ids' => [$teams[4]->id, $teams[5]->id]],
        ['name' => 'D', 'team_ids' => [$teams[6]->id, $teams[7]->id]],
    ]);

    $this->getJson("/api/tournaments/{$tournament->id}")
        ->assertOk()
        ->assertJsonPath('data.name', 'My Cup')
        ->assertJsonCount(8, 'data.teams');
});

test('a visão completa expõe jogos com versão para o console', function () {
    Sanctum::actingAs($owner = User::factory()->create());
    [$tournament, $teams] = ownedTournamentWithTeams($owner);
    $this->postJson("/api/tournaments/{$tournament->id}/group-stage", fourGroupsPayload($teams))->assertOk();

    $this->getJson("/api/tournaments/{$tournament->id}")
        ->assertOk()
        ->assertJsonPath('data.stages.0.groups.0.qualify_count', 2)
        ->assertJsonStructure([
            'data' => ['stages' => [['groups' => [['fixtures' => [['id', 'status', 'version']]]]]]],
        ]);
});
