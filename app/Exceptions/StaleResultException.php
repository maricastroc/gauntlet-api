<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when the expected result version does not match the one in the database —
 * i.e. someone else saved/edited the same match in the meantime.
 * The API translates this into an HTTP 409 Conflict; the client reloads and tries again.
 */
final class StaleResultException extends RuntimeException
{
    public function __construct(
        public readonly int|string $fixtureId,
        public readonly int $expectedVersion,
    ) {
        parent::__construct(
            "The match result for {$fixtureId} was changed by someone else "
            ."(version {$expectedVersion} is outdated). Reload and try again."
        );
    }
}
