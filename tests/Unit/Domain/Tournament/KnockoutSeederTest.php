<?php

declare(strict_types=1);

use App\Domain\Tournament\Bracket\KnockoutSeeder;

/** @param list<array{round:int,slot:int,home_source:string,away_source:string}> $ties */
function tieAt(array $ties, int $round, int $slot): array
{
    foreach ($ties as $tie) {
        if ($tie['round'] === $round && $tie['slot'] === $slot) {
            return $tie;
        }
    }
    throw new RuntimeException("Tie r{$round}s{$slot} não encontrada");
}

test('4 grupos com 2 classificados reproduzem o cruzamento do seeder', function () {
    $ties = KnockoutSeeder::seed(['A', 'B', 'C', 'D'], 2);

    // 8 classificados => 4 quartas + 2 semis + 1 final = 7 confrontos
    expect($ties)->toHaveCount(7);

    // quartas: cruzamento em espelho por par de grupos
    expect(tieAt($ties, 1, 1))->toMatchArray(['home_source' => 'seed:A1', 'away_source' => 'seed:B2']);
    expect(tieAt($ties, 1, 2))->toMatchArray(['home_source' => 'seed:C1', 'away_source' => 'seed:D2']);
    expect(tieAt($ties, 1, 3))->toMatchArray(['home_source' => 'seed:B1', 'away_source' => 'seed:A2']);
    expect(tieAt($ties, 1, 4))->toMatchArray(['home_source' => 'seed:D1', 'away_source' => 'seed:C2']);

    // semis encadeiam vencedores de slots adjacentes (placeholders)
    expect(tieAt($ties, 2, 1))->toMatchArray(['home_source' => 'winner:r1s1', 'away_source' => 'winner:r1s2']);
    expect(tieAt($ties, 2, 2))->toMatchArray(['home_source' => 'winner:r1s3', 'away_source' => 'winner:r1s4']);

    // final
    expect(tieAt($ties, 3, 1))->toMatchArray(['home_source' => 'winner:r2s1', 'away_source' => 'winner:r2s2']);
});

test('2 grupos com 2 classificados geram semis + final', function () {
    $ties = KnockoutSeeder::seed(['A', 'B'], 2);

    expect($ties)->toHaveCount(3); // 2 semis + 1 final
    expect(tieAt($ties, 1, 1))->toMatchArray(['home_source' => 'seed:A1', 'away_source' => 'seed:B2']);
    expect(tieAt($ties, 1, 2))->toMatchArray(['home_source' => 'seed:B1', 'away_source' => 'seed:A2']);
    expect(tieAt($ties, 2, 1))->toMatchArray(['home_source' => 'winner:r1s1', 'away_source' => 'winner:r1s2']);
});

test('um classificado por grupo emparelha vencedores', function () {
    $ties = KnockoutSeeder::seed(['A', 'B', 'C', 'D'], 1);

    expect($ties)->toHaveCount(3); // 4 grupos => 2 semis + 1 final
    expect(tieAt($ties, 1, 1))->toMatchArray(['home_source' => 'seed:A1', 'away_source' => 'seed:B1']);
    expect(tieAt($ties, 1, 2))->toMatchArray(['home_source' => 'seed:C1', 'away_source' => 'seed:D1']);
});

test('16 classificados geram um bracket completo de 15 confrontos', function () {
    $ties = KnockoutSeeder::seed(['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'], 2);

    // 16 => 8 + 4 + 2 + 1 = 15
    expect($ties)->toHaveCount(15);
    expect(array_filter($ties, fn ($t) => $t['round'] === 1))->toHaveCount(8);
    expect(array_filter($ties, fn ($t) => $t['round'] === 4))->toHaveCount(1);
});

test('recusa configurações que não fecham numa potência de 2', function () {
    expect(fn () => KnockoutSeeder::seed(['A', 'B', 'C'], 2))->toThrow(InvalidArgumentException::class);
    expect(fn () => KnockoutSeeder::seed(['A', 'B', 'C'], 1))->toThrow(InvalidArgumentException::class);
    expect(fn () => KnockoutSeeder::seed(['A', 'B', 'C', 'D'], 3))->toThrow(InvalidArgumentException::class);
});
