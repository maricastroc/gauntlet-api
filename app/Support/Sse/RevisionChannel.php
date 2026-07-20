<?php

declare(strict_types=1);

namespace App\Support\Sse;

/**
 * The transport the spectator stream uses to learn that a tournament's revision advanced.
 *
 * The controller's connection loop is written against this seam so the "how we detect a change"
 * decision is swappable without touching the loop, the SSE frames, or the client:
 *
 *   - PollingRevisionChannel — reads the committed `tournaments.revision` on a short cadence.
 *     No extra infrastructure; latency is bounded by the poll interval. (default)
 *   - RedisRevisionChannel   — blocks on a Redis pub/sub channel fed by PublishTournamentUpdate,
 *     so a committed save pushes the new revision in ~milliseconds.
 *
 * `current()` is always a read of the authoritative snapshot (the DB): it seeds the first `sync`
 * frame and lets a reconnecting client re-sync from state rather than replayed events.
 */
interface RevisionChannel
{
    /** The tournament's current committed revision (authoritative snapshot read). */
    public function current(int $tournamentId): int;

    /**
     * Wait up to $timeoutMs for the revision to advance past $knownRevision.
     *
     * Returns the new revision as soon as it is observed (which may be immediately, if it already
     * advanced before this call), or null if the window elapsed with no change. Implementations
     * MUST NOT throw on a timeout or transport hiccup — a null return degrades cleanly to "no news
     * yet", and the caller re-polls on the next tick.
     */
    public function awaitChange(int $tournamentId, int $knownRevision, int $timeoutMs): ?int;
}
