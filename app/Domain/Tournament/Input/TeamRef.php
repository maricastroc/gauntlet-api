<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Input;

/**
 * Minimal reference to a team — a pure input DTO for the engine.
 * Not the Eloquent model: only what the engine needs to rank.
 */
final class TeamRef
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {}
}
