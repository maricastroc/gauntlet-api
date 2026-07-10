<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Bracket;

use InvalidArgumentException;

/**
 * Gera a TOPOLOGIA do mata-mata a partir dos grupos — engine pura, sem framework.
 *
 * A primeira rodada semeia cruzando grupos (1º de A × 2º de B, …), no mesmo
 * padrão do World Cup, para que times do mesmo grupo só se reencontrem adiante.
 * As rodadas seguintes encadeiam vencedores.
 *
 * Os sources de vencedor saem como PLACEHOLDER "winner:r{rodada}s{slot}" — quem
 * insere as tuplas (a Action) resolve para "winner:{id real}" ao gravar em ordem
 * de rodada. Isso mantém a engine ignorante de ids de banco.
 *
 * Suporta 4, 8 ou 16 classificados (potência de 2). Fora disso, recusa.
 */
final class KnockoutSeeder
{
    /**
     * @param  string[]  $groupNames  nomes dos grupos (ex.: ['A','B','C','D'])
     * @param  int  $qualifyCount  quantos avançam por grupo (1 ou 2)
     * @return list<array{round: int, slot: int, home_source: string, away_source: string}>
     */
    public static function seed(array $groupNames, int $qualifyCount): array
    {
        $names = array_values($groupNames);
        sort($names);
        $groups = count($names);
        $qualified = $groups * $qualifyCount;

        self::guard($names, $qualifyCount, $qualified);

        $ties = self::firstRound($names, $qualifyCount);

        // rodadas seguintes: emparelha vencedores de slots adjacentes até sobrar a final
        $round = 2;
        $previousCount = count($ties);
        while ($previousCount > 1) {
            $currentCount = intdiv($previousCount, 2);
            for ($slot = 1; $slot <= $currentCount; $slot++) {
                $ties[] = [
                    'round' => $round,
                    'slot' => $slot,
                    'home_source' => 'winner:r'.($round - 1).'s'.(2 * $slot - 1),
                    'away_source' => 'winner:r'.($round - 1).'s'.(2 * $slot),
                ];
            }
            $previousCount = $currentCount;
            $round++;
        }

        return $ties;
    }

    /**
     * @param  string[]  $names
     * @return list<array{round: int, slot: int, home_source: string, away_source: string}>
     */
    private static function firstRound(array $names, int $qualifyCount): array
    {
        $ties = [];
        $slot = 1;

        if ($qualifyCount === 1) {
            // um por grupo: emparelha vencedores de grupos consecutivos
            foreach (array_chunk($names, 2) as [$x, $y]) {
                $ties[] = self::tie(1, $slot++, "seed:{$x}1", "seed:{$y}1");
            }

            return $ties;
        }

        // dois por grupo: cruzamento em espelho por par de grupos
        $pairs = array_chunk($names, 2);
        foreach ($pairs as [$x, $y]) {
            $ties[] = self::tie(1, $slot++, "seed:{$x}1", "seed:{$y}2");
        }
        foreach ($pairs as [$x, $y]) {
            $ties[] = self::tie(1, $slot++, "seed:{$y}1", "seed:{$x}2");
        }

        return $ties;
    }

    /** @return array{round: int, slot: int, home_source: string, away_source: string} */
    private static function tie(int $round, int $slot, string $home, string $away): array
    {
        return ['round' => $round, 'slot' => $slot, 'home_source' => $home, 'away_source' => $away];
    }

    /** @param  string[]  $names */
    private static function guard(array $names, int $qualifyCount, int $qualified): void
    {
        if (! in_array($qualifyCount, [1, 2], true)) {
            throw new InvalidArgumentException('Só é possível gerar chave com 1 ou 2 classificados por grupo.');
        }

        if (! in_array($qualified, [4, 8, 16], true)) {
            throw new InvalidArgumentException(
                "O total de classificados precisa ser 4, 8 ou 16 (recebido {$qualified})."
            );
        }

        if ($qualifyCount === 2 && count($names) % 2 !== 0) {
            throw new InvalidArgumentException('Com 2 classificados por grupo, o número de grupos precisa ser par.');
        }
    }
}
