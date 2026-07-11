<?php

declare(strict_types=1);

return [
    'email' => env('DEMO_EMAIL', 'demo@bracket.test'),

    'sandbox_ttl_hours' => (int) env('DEMO_SANDBOX_TTL_HOURS', 24),
];
