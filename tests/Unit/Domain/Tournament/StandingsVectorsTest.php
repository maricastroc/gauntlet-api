<?php

declare(strict_types=1);

use App\Domain\Tournament\Input\MatchResult;
use App\Domain\Tournament\Input\TeamRef;
use App\Domain\Tournament\Standings\GroupTable;
use App\Domain\Tournament\Standings\TiebreakRules;

/*
 * Conformance vectors shared byte-for-byte with the web app (tournament-game: src/lib/standings.ts).
 * Both engines must rank these identically; a drift in either one breaks its build.
 * Source of truth: tests/Vectors/standings.json — keep the two copies identical.
 */

test('matches the shared standings vector', function (array $case) {
    $teams = array_map(
        fn (array $t) => new TeamRef($t['id'], $t['name']),
        $case['teams'],
    );

    $matches = array_map(
        fn (array $m) => new MatchResult($m['homeId'], $m['awayId'], $m['homeScore'], $m['awayScore']),
        $case['matches'],
    );

    $table = GroupTable::compute($teams, $matches, TiebreakRules::fifa(), $case['qualifyCount']);

    expect(array_map(fn ($standing) => $standing->team->id, $table))->toBe($case['expected']);
})->with(function () {
    $path = dirname(__DIR__, 3).'/Vectors/standings.json';
    $suite = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

    $dataset = [];
    foreach ($suite['cases'] as $case) {
        $dataset[$case['name']] = [$case];
    }

    return $dataset;
});
