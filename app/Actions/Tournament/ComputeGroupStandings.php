<?php

declare(strict_types=1);

namespace App\Actions\Tournament;

use App\Domain\Tournament\Input\MatchResult;
use App\Domain\Tournament\Input\TeamRef;
use App\Domain\Tournament\Standings\GroupTable;
use App\Domain\Tournament\Standings\Standing;
use App\Domain\Tournament\Standings\TiebreakRules;
use App\Models\Fixture;
use App\Models\Group;
use App\Models\Team;

/**
 * The READ boundary: maps a group (Eloquent) to the Domain DTOs and delegates
 * the computation to the pure engine. Reused both by the write path (ConfirmMatchResult)
 * and by the standings read endpoint — the translation lives in a single place.
 */
final class ComputeGroupStandings
{
    /** @return Standing[] */
    public function for(Group $group): array
    {
        $group->loadMissing('teams');

        $teams = $group->teams
            ->map(fn (Team $team) => new TeamRef($team->id, $team->name))
            ->all();

        $results = Fixture::where('group_id', $group->id)
            ->finished()
            ->get()
            ->map(fn (Fixture $fixture) => new MatchResult(
                $fixture->home_team_id,
                $fixture->away_team_id,
                $fixture->home_score,
                $fixture->away_score,
            ))
            ->all();

        return GroupTable::compute($teams, $results, TiebreakRules::fifa(), $group->qualify_count);
    }
}
