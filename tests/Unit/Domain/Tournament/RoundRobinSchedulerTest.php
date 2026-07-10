<?php

declare(strict_types=1);

use App\Domain\Tournament\Fixture\RoundRobinScheduler;

/** @param list<array{home:int, away:int}> $fixtures */
function pairKeys(array $fixtures): array
{
    return array_map(function (array $f) {
        $pair = [$f['home'], $f['away']];
        sort($pair);

        return implode('-', $pair);
    }, $fixtures);
}

test('gera todos os confrontos de um returno-único, cada par uma vez', function () {
    $fixtures = RoundRobinScheduler::schedule([10, 20, 30, 40]);

    // 4 times => C(4,2) = 6 jogos
    expect($fixtures)->toHaveCount(6);

    $keys = pairKeys($fixtures);
    expect($keys)->toEqualCanonicalizing(['10-20', '10-30', '10-40', '20-30', '20-40', '30-40']);
    // sem repetição
    expect(array_unique($keys))->toHaveCount(6);
});

test('lida com número ímpar de times (bye) sem gerar jogo fantasma', function () {
    $fixtures = RoundRobinScheduler::schedule([1, 2, 3]);

    // 3 times => 3 jogos; nenhum id inválido (o bye -1 nunca aparece)
    expect($fixtures)->toHaveCount(3);
    expect(pairKeys($fixtures))->toEqualCanonicalizing(['1-2', '1-3', '2-3']);

    foreach ($fixtures as $f) {
        expect($f['home'])->toBeGreaterThan(0)
            ->and($f['away'])->toBeGreaterThan(0);
    }
});

test('menos de dois times não gera jogo', function () {
    expect(RoundRobinScheduler::schedule([]))->toBe([]);
    expect(RoundRobinScheduler::schedule([7]))->toBe([]);
});

test('cada time joga contra todos os outros exatamente uma vez', function () {
    $ids = [1, 2, 3, 4, 5, 6];
    $fixtures = RoundRobinScheduler::schedule($ids);

    $appearances = array_fill_keys($ids, 0);
    foreach ($fixtures as $f) {
        $appearances[$f['home']]++;
        $appearances[$f['away']]++;
    }

    // cada um enfrenta 5 adversários
    foreach ($appearances as $count) {
        expect($count)->toBe(5);
    }
    expect($fixtures)->toHaveCount(15); // C(6,2)
});
