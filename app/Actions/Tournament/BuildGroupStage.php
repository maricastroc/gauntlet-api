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
 * Monta a fase de grupos: cria a etapa, os grupos, distribui os times e gera o
 * returno-único de cada grupo (jogos 'scheduled', sem placar) — pela engine pura.
 * Tudo numa transação: ou o torneio ganha a fase inteira, ou nada.
 */
final class BuildGroupStage
{
    /**
     * @param  list<array{name: string, team_ids: int[]}>  $groups
     */
    public function handle(Tournament $tournament, int $qualifyCount, array $groups): Stage
    {
        if ($tournament->stages()->where('type', 'group')->exists()) {
            throw new InvalidTournamentStructure('Este torneio já tem uma fase de grupos.');
        }

        return DB::transaction(function () use ($tournament, $qualifyCount, $groups) {
            $stage = Stage::create([
                'tournament_id' => $tournament->id,
                'type' => 'group',
                'name' => 'Fase de grupos',
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
