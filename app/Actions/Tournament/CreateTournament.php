<?php

declare(strict_types=1);

namespace App\Actions\Tournament;

use App\Models\Tournament;
use App\Models\User;

final class CreateTournament
{
    public function handle(User $owner, string $name): Tournament
    {
        return Tournament::create([
            'user_id' => $owner->id,
            'name' => $name,
            'status' => 'draft',
        ]);
    }
}
