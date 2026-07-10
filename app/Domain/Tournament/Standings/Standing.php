<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Standings;

use App\Domain\Tournament\Input\TeamRef;

/**
 * A single standings row — an immutable value object.
 * position/qualified only get their values at the end of GroupTable::compute().
 */
final class Standing
{
    /** @param list<string> $form  sequence of 'W'|'D'|'L' in match order */
    public function __construct(
        public readonly TeamRef $team,
        public readonly int $played,
        public readonly int $won,
        public readonly int $drawn,
        public readonly int $lost,
        public readonly int $goalsFor,
        public readonly int $goalsAgainst,
        public readonly int $points,
        public readonly array $form,
        public readonly int $position = 0,
        public readonly bool $qualified = false,
    ) {}

    public function goalDifference(): int
    {
        return $this->goalsFor - $this->goalsAgainst;
    }

    public function withRank(int $position, bool $qualified): self
    {
        return new self(
            $this->team,
            $this->played,
            $this->won,
            $this->drawn,
            $this->lost,
            $this->goalsFor,
            $this->goalsAgainst,
            $this->points,
            $this->form,
            $position,
            $qualified,
        );
    }
}
