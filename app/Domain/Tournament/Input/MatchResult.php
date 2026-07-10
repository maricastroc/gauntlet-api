<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Input;

/**
 * Result of a finished match — a pure input DTO for the engine.
 * The application layer (Action) maps Eloquent -> this object.
 * Only matches with a defined score reach here.
 */
final class MatchResult
{
    public function __construct(
        public readonly int $homeTeamId,
        public readonly int $awayTeamId,
        public readonly int $homeScore,
        public readonly int $awayScore,
    ) {}
}
