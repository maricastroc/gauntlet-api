<?php

declare(strict_types=1);

namespace App\Actions\Tournament;

use App\Domain\Tournament\Standings\Standing;
use App\Exceptions\StaleResultException;
use App\Models\Fixture;
use Illuminate\Support\Facades\DB;

/**
 * Grava (ou edita) o resultado de um jogo de grupo e devolve a classificação recalculada.
 *
 * É a fronteira de ESCRITA entre Laravel e o Domain puro:
 *   1. grava o placar sob lock OTIMISTA (só se a versão bater) — protege edição concorrente;
 *   2. recomputa a classificação delegando à engine pura (via ComputeGroupStandings);
 * tudo numa única transação, para a classificação nunca refletir um estado parcial.
 *
 * A classificação em si não é gravada: é PROJEÇÃO das partidas. Editar um resultado é
 * só recomputar — não sincronizar estado.
 */
final class ConfirmMatchResult
{
    public function __construct(private readonly ComputeGroupStandings $standings = new ComputeGroupStandings) {}

    /**
     * @return Standing[] a classificação recalculada do grupo do jogo
     *
     * @throws StaleResultException se outra pessoa alterou o jogo nesse meio-tempo
     */
    public function handle(Fixture $fixture, int $homeScore, int $awayScore, int $expectedVersion): array
    {
        return DB::transaction(function () use ($fixture, $homeScore, $awayScore, $expectedVersion) {
            $affected = Fixture::whereKey($fixture->getKey())
                ->where('version', $expectedVersion)
                ->update([
                    'home_score' => $homeScore,
                    'away_score' => $awayScore,
                    'status' => 'finished',
                    'version' => $expectedVersion + 1,
                ]);

            if ($affected === 0) {
                throw new StaleResultException($fixture->getKey(), $expectedVersion);
            }

            $group = $fixture->group()->firstOrFail();

            return $this->standings->for($group);
        });
    }
}
