<?php

declare(strict_types=1);

namespace App\Actions\Tournament;

use App\Domain\Tournament\Fixture\RoundRobinScheduler;
use App\Exceptions\InvalidTournamentStructure;
use App\Models\Fixture;
use App\Models\Group;
use App\Models\Stage;
use App\Models\Tournament;
use Illuminate\Support\Facades\DB;

/**
 * Builds the group stage: creates the stage, the groups, distributes the teams and generates the
 * single round-robin of each group (matches 'scheduled', without score) — via the pure engine.
 * All in one transaction: either the tournament gets the entire stage, or nothing.
 */
final class BuildGroupStage
{
    /**
     * @param  list<array{name: string, team_ids: int[]}>  $groups
     */
    public function handle(Tournament $tournament, int $qualifyCount, array $groups): Stage
    {
        if ($tournament->stages()->where('type', 'group')->exists()) {
            throw new InvalidTournamentStructure('This tournament already has a group stage.');
        }

        return DB::transaction(function () use ($tournament, $qualifyCount, $groups) {
            $stage = Stage::create([
                'tournament_id' => $tournament->id,
                'type' => 'group',
                'name' => 'Group stage',
                'position' => 1,
            ]);

            foreach ($groups as $definition) {
                $group = Group::create([
                    'stage_id' => $stage->id,
                    'name' => $definition['name'],
                    'qualify_count' => $qualifyCount,
                ]);

                $group->teams()->attach($definition['team_ids']);

                foreach (RoundRobinScheduler::schedule($definition['team_ids']) as $pairing) {
                    Fixture::create([
                        'tournament_id' => $tournament->id,
                        'stage_id' => $stage->id,
                        'group_id' => $group->id,
                        'home_team_id' => $pairing['home'],
                        'away_team_id' => $pairing['away'],
                        'status' => 'scheduled',
                    ]);
                }
            }

            $tournament->update(['status' => 'active']);

            return $stage;
        });
    }
}
