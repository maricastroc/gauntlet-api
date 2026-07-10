<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Bracket;

use App\Domain\Tournament\Input\TeamRef;

/**
 * The resolved view of a tie — what the UI consumes. Immutable value object.
 *
 * status:
 *  - 'pending'  one side is still unknown (the source tie has not finished)
 *  - 'ready'    both teams defined, no result yet
 *  - 'decided'  there is a winner
 */
final class ResolvedTie
{
    public function __construct(
        public readonly int $id,
        public readonly int $round,
        public readonly ?TeamRef $home,      // null = to be determined (TBD)
        public readonly ?TeamRef $away,      // null = to be determined (TBD)
        public readonly ?TeamRef $winner,    // null = not decided yet
        public readonly string $status,
        public readonly bool $decidedByPenalties,
    ) {}
}
