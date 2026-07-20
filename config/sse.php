<?php

declare(strict_types=1);

return [
    // How change detection is transported to the spectator stream:
    //   'poll'  — re-read tournaments.revision every poll_ms (no extra infra; the default)
    //   'redis' — block on a Redis pub/sub channel for ~instant push (needs a reachable Redis)
    'driver' => env('SSE_DRIVER', 'poll'),

    // Which config/database.php redis connection the 'redis' driver publishes to / subscribes on.
    'redis_connection' => env('SSE_REDIS_CONNECTION', 'default'),

    'max_seconds' => (int) env('SSE_MAX_SECONDS', 45),

    'poll_ms' => (int) env('SSE_POLL_MS', 1500),

    'retry_ms' => (int) env('SSE_RETRY_MS', 3000),

    'heartbeat_ms' => (int) env('SSE_HEARTBEAT_MS', 15000),
];
