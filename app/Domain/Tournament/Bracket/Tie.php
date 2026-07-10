<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Bracket;

final class Tie
{
    public function __construct(
        public readonly int $id,
        public readonly int $round,
        public readonly SlotSource $home,
        public readonly SlotSource $away,
    ) {}
}
