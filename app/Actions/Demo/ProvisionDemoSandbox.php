<?php

declare(strict_types=1);

namespace App\Actions\Demo;

use App\Actions\Tournament\CloneTournament;
use App\Models\Tournament;
use Illuminate\Support\Carbon;

/**
 * Clones the demo template into a fresh, isolated sandbox owned by a single
 * session (Sanctum token). Used on demo login and on "reset demo".
 */
final class ProvisionDemoSandbox
{
    public function __construct(private readonly CloneTournament $clone = new CloneTournament) {}

    /** Returns the new sandbox, or null when the demo template has not been seeded. */
    public function handle(int $tokenId): ?Tournament
    {
        $template = Tournament::query()->where('is_demo_template', true)->first();

        if ($template === null) {
            return null;
        }

        return $this->clone->handle($template, [
            'template_id' => $template->id,
            'demo_token_id' => $tokenId,
            'demo_expires_at' => Carbon::now()->addHours((int) config('demo.sandbox_ttl_hours', 24)),
        ]);
    }
}
