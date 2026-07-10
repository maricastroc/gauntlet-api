<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tournament;
use App\Models\User;

final class TournamentPolicy
{
    /** Only the organizer (owner) can submit or edit tournament results. */
    public function manage(User $user, Tournament $tournament): bool
    {
        return $tournament->user_id === $user->id;
    }
}
