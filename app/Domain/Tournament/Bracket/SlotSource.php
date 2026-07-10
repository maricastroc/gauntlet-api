<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Bracket;

/**
 * Where a side of a tie comes from: either a seeded slot from the group stage
 * (e.g. "A1" = 1st of group A), or the winner of a previous tie.
 *
 * This makes the bracket a TOPOLOGY: "who is in slot X" is derivable by walking
 * through the sources, not a field that gets rewritten.
 */
final class SlotSource
{
    private function __construct(
        public readonly string $kind,   // 'seed' | 'winner'
        public readonly ?string $seed,  // e.g. 'A1' when kind = seed
        public readonly ?int $tieId,    // id of the source tie when kind = winner
    ) {}

    public static function seed(string $key): self
    {
        return new self('seed', $key, null);
    }

    public static function winnerOf(int $tieId): self
    {
        return new self('winner', null, $tieId);
    }

    public function isSeed(): bool
    {
        return $this->kind === 'seed';
    }
}
