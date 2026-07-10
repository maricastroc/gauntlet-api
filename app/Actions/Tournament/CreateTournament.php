<?php

declare(strict_types=1);

namespace App\Actions\Tournament;

use App\Models\Tournament;
use App\Models\User;

/**
 * Cria um torneio vazio (rascunho) pertencente a quem organiza.
 * As etapas (grupos, mata-mata) vêm depois, pelas Actions de montagem.
 */
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
