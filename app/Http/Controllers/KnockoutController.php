<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Tournament\BuildKnockout;
use App\Http\Resources\TournamentDetailResource;
use App\Models\Tournament;
use Illuminate\Support\Facades\Gate;

final class KnockoutController extends Controller
{
    /**
     * Gera o mata-mata a partir da fase de grupos e devolve o torneio completo.
     * Só o dono. Recusa (422) se não houver grupos ou a chave não fechar.
     */
    public function store(Tournament $tournament, BuildKnockout $action): TournamentDetailResource
    {
        Gate::authorize('manage', $tournament);

        $action->handle($tournament);

        return new TournamentDetailResource($tournament->loadFullDetail());
    }
}
