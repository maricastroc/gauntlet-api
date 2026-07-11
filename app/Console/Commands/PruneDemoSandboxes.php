<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tournament;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

final class PruneDemoSandboxes extends Command
{
    protected $signature = 'demo:prune-sandboxes';

    protected $description = 'Deletes expired demo sandbox tournaments and their cascade (teams, stages, matches).';

    public function handle(): int
    {
        $expired = Tournament::query()
            ->whereNotNull('demo_expires_at')
            ->where('demo_expires_at', '<', Carbon::now())
            ->get();

        $expired->each->delete();

        $this->info("Pruned {$expired->count()} expired demo sandbox(es).");

        return self::SUCCESS;
    }
}
