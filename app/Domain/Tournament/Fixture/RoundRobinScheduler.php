<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Fixture;

/**
 * Gera o returno-único de um grupo — engine pura, sem framework.
 *
 * Método do círculo (round-robin): cada par de times se enfrenta uma vez.
 * Com número ímpar de times, entra um "bye" que folga a rodada. O mando
 * (home/away) alterna por rodada só para equilibrar — num grupo é neutro.
 */
final class RoundRobinScheduler
{
    private const BYE = -1;

    /**
     * @param  int[]  $teamIds  ids dos times do grupo, na ordem de entrada
     * @return list<array{home: int, away: int}>  os confrontos, cada par uma vez
     */
    public static function schedule(array $teamIds): array
    {
        $teams = array_values($teamIds);
        if (count($teams) < 2) {
            return [];
        }

        if (count($teams) % 2 === 1) {
            $teams[] = self::BYE;
        }

        $count = count($teams);
        $rounds = $count - 1;
        $half = intdiv($count, 2);
        $fixtures = [];

        for ($round = 0; $round < $rounds; $round++) {
            for ($i = 0; $i < $half; $i++) {
                $a = $teams[$i];
                $b = $teams[$count - 1 - $i];

                if ($a === self::BYE || $b === self::BYE) {
                    continue;
                }

                [$home, $away] = $round % 2 === 0 ? [$a, $b] : [$b, $a];
                $fixtures[] = ['home' => $home, 'away' => $away];
            }

            // rotaciona: fixa o índice 0 e gira o resto (leva o último para a posição 1)
            $last = array_pop($teams);
            array_splice($teams, 1, 0, [$last]);
        }

        return $fixtures;
    }
}
