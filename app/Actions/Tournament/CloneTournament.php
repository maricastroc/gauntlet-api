<?php

declare(strict_types=1);

namespace App\Actions\Tournament;

use App\Models\Fixture;
use App\Models\Group;
use App\Models\Stage;
use App\Models\Team;
use App\Models\Tie;
use App\Models\Tournament;
use Illuminate\Support\Facades\DB;

/**
 * Deep-copies a tournament — teams, stages, groups (+ membership), knockout ties
 * and matches — into a brand new tournament, remapping every internal id reference
 * so the copy is fully self-contained and the source is never touched.
 */
final class CloneTournament
{
    /**
     * @param  array<string, mixed>  $overrides  merged into the new tournament row
     */
    public function handle(Tournament $source, array $overrides = []): Tournament
    {
        $source->loadMissing([
            'teams',
            'stages.groups.teams',
            'stages.ties',
            'stages.fixtures',
        ]);

        return DB::transaction(function () use ($source, $overrides) {
            $clone = Tournament::create(array_merge([
                'user_id' => $source->user_id,
                'name' => $source->name,
                'format' => $source->format,
                'tiebreak' => $source->tiebreak,
                'status' => $source->status,
                'is_demo_template' => false,
                'template_id' => null,
                'demo_token_id' => null,
                'demo_expires_at' => null,
            ], $overrides));

            $teamMap = $this->cloneTeams($source, $clone);
            [$tieMap, $stageMap, $groupMap] = $this->cloneStructure($source, $clone, $teamMap);

            $this->remapTieSources($tieMap);
            $this->cloneFixtures($source, $clone, $stageMap, $groupMap, $teamMap, $tieMap);

            return $clone->refresh();
        });
    }

    /** @return array<int, int> oldTeamId => newTeamId */
    private function cloneTeams(Tournament $source, Tournament $clone): array
    {
        $map = [];

        foreach ($source->teams as $team) {
            $map[$team->id] = Team::create([
                'tournament_id' => $clone->id,
                'name' => $team->name,
                'code' => $team->code,
                'flag' => $team->flag,
            ])->id;
        }

        return $map;
    }

    /**
     * Copies stages, groups (+ pivot) and ties. Ties keep their original source
     * strings here; {@see remapTieSources} rewrites `winner:{id}` refs afterwards,
     * once the full tie map exists (a later round can only reference earlier ties,
     * but a single pass keeps the ordering assumption out of the code).
     *
     * @param  array<int, int>  $teamMap
     * @return array{0: array<int, int>, 1: array<int, int>, 2: array<int, int>} [tieMap, stageMap, groupMap]
     */
    private function cloneStructure(Tournament $source, Tournament $clone, array $teamMap): array
    {
        $tieMap = [];
        $stageMap = [];
        $groupMap = [];

        foreach ($source->stages as $stage) {
            $newStage = Stage::create([
                'tournament_id' => $clone->id,
                'type' => $stage->type,
                'name' => $stage->name,
                'position' => $stage->position,
            ]);
            $stageMap[$stage->id] = $newStage->id;

            foreach ($stage->groups as $group) {
                $newGroup = Group::create([
                    'stage_id' => $newStage->id,
                    'name' => $group->name,
                    'qualify_count' => $group->qualify_count,
                ]);
                $groupMap[$group->id] = $newGroup->id;

                $newGroup->teams()->attach(
                    $group->teams->pluck('id')->map(fn (int $id) => $teamMap[$id])->all()
                );
            }

            foreach ($stage->ties as $tie) {
                $tieMap[$tie->id] = Tie::create([
                    'stage_id' => $newStage->id,
                    'round' => $tie->round,
                    'slot' => $tie->slot,
                    'home_source' => $tie->home_source,
                    'away_source' => $tie->away_source,
                    'feeds_side' => $tie->feeds_side,
                ])->id;
            }
        }

        return [$tieMap, $stageMap, $groupMap];
    }

    /**
     * Rewrites `winner:{tieId}` references on the cloned ties to point at the
     * cloned tie ids. Seed refs (`seed:A1`) carry no ids and are left untouched.
     *
     * @param  array<int, int>  $tieMap  oldTieId => newTieId
     */
    private function remapTieSources(array $tieMap): void
    {
        foreach ($tieMap as $oldTieId => $newTieId) {
            $tie = Tie::findOrFail($newTieId);

            $tie->update([
                'home_source' => $this->remapSource($tie->home_source, $tieMap),
                'away_source' => $this->remapSource($tie->away_source, $tieMap),
                'feeds_tie_id' => $tie->feeds_tie_id !== null ? ($tieMap[$tie->feeds_tie_id] ?? null) : null,
            ]);
        }
    }

    /** @param  array<int, int>  $tieMap */
    private function remapSource(string $source, array $tieMap): string
    {
        if (! str_starts_with($source, 'winner:')) {
            return $source;
        }

        $oldId = (int) substr($source, strlen('winner:'));
        $newId = $tieMap[$oldId] ?? null;

        return $newId !== null ? "winner:{$newId}" : $source;
    }

    /**
     * @param  array<int, int>  $stageMap
     * @param  array<int, int>  $groupMap
     * @param  array<int, int>  $teamMap
     * @param  array<int, int>  $tieMap
     */
    private function cloneFixtures(
        Tournament $source,
        Tournament $clone,
        array $stageMap,
        array $groupMap,
        array $teamMap,
        array $tieMap,
    ): void {
        foreach ($source->stages as $stage) {
            foreach ($stage->fixtures as $fixture) {
                Fixture::create([
                    'tournament_id' => $clone->id,
                    'stage_id' => $stageMap[$stage->id],
                    'group_id' => $fixture->group_id !== null ? ($groupMap[$fixture->group_id] ?? null) : null,
                    'tie_id' => $fixture->tie_id !== null ? ($tieMap[$fixture->tie_id] ?? null) : null,
                    'home_team_id' => $fixture->home_team_id !== null ? ($teamMap[$fixture->home_team_id] ?? null) : null,
                    'away_team_id' => $fixture->away_team_id !== null ? ($teamMap[$fixture->away_team_id] ?? null) : null,
                    'home_score' => $fixture->home_score,
                    'away_score' => $fixture->away_score,
                    'home_penalties' => $fixture->home_penalties,
                    'away_penalties' => $fixture->away_penalties,
                    'status' => $fixture->status,
                    'kickoff_at' => $fixture->kickoff_at,
                    'version' => 0,
                ]);
            }
        }
    }
}
