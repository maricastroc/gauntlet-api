<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tournament;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

final class TournamentPolicy
{
    public function manage(User $user, Tournament $tournament): bool
    {
        if ($tournament->is_demo_template) {
            return false;
        }

        if ($tournament->isDemoSandbox()) {
            $token = $user->currentAccessToken();

            return $token instanceof PersonalAccessToken
                && (int) $token->getKey() === (int) $tournament->demo_token_id
                && ! $tournament->demoExpired();
        }

        return $tournament->user_id === $user->id;
    }
}
