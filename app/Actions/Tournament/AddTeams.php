<?php

declare(strict_types=1);

namespace App\Actions\Tournament;

use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Adds teams to a tournament, in bulk. Each team is just a name + (optional) code and flag.
 */
final class AddTeams
{
    /**
     * @param  list<array{name: string, code?: ?string, flag?: ?string}>  $teams
     * @return Collection<int, Team>
     */
    public function handle(Tournament $tournament, array $teams): Collection
    {
        return DB::transaction(fn () => collect($teams)->map(fn (array $team) => Team::create([
            'tournament_id' => $tournament->id,
            'name' => $team['name'],
            'code' => $team['code'] ?? null,
            'flag' => $team['flag'] ?? null,
        ]))->values());
    }
}
