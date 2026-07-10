<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Tournament\BuildGroupStage;
use App\Http\Requests\BuildGroupStageRequest;
use App\Http\Resources\TournamentDetailResource;
use App\Models\Tournament;
use Illuminate\Support\Facades\Gate;

final class GroupStageController extends Controller
{
    /**
     * Monta a fase de grupos (grupos + distribuição + returno-único) e devolve o
     * torneio completo já com os ids novos. Só o dono.
     */
    public function store(
        BuildGroupStageRequest $request,
        Tournament $tournament,
        BuildGroupStage $action,
    ): TournamentDetailResource {
        Gate::authorize('manage', $tournament);

        $action->handle($tournament, $request->qualifyCount(), $request->groups());

        return new TournamentDetailResource($tournament->loadFullDetail());
    }
}
