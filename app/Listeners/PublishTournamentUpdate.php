<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TournamentUpdated;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Bridges the post-commit TournamentUpdated event onto the Redis pub/sub channel that
 * RedisRevisionChannel subscribers block on — the write half of the "real push" transport.
 *
 * Runs synchronously so the nudge goes out the instant the save's response is being built (Redis
 * publish is sub-millisecond). It is a no-op unless the redis driver is active, so the default
 * (poll) build — and the whole test suite — never touches Redis. A publish failure is swallowed:
 * the save already committed, the revision is the source of truth, and clients re-sync on reconnect,
 * so a Redis outage must never turn a successful save into an error.
 */
final class PublishTournamentUpdate
{
    public function handle(TournamentUpdated $event): void
    {
        if (config('sse.driver') !== 'redis') {
            return;
        }

        try {
            $payload = json_encode([
                'tournament_id' => $event->tournamentId,
                'revision' => $event->revision,
                'type' => $event->type,
                'ts' => time(),
            ], JSON_THROW_ON_ERROR);

            Redis::connection((string) config('sse.redis_connection', 'default'))
                ->publish('tournament.'.$event->tournamentId, $payload);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
