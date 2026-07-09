<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tournament;
use App\Models\User;

final class TournamentPolicy
{
    /** Só o organizador (dono) pode lançar ou editar resultados do torneio. */
    public function manage(User $user, Tournament $tournament): bool
    {
        return $tournament->user_id === $user->id;
    }
}
