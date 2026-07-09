<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Tournament\ConfirmMatchResult;
use App\Http\Requests\ConfirmMatchResultRequest;
use App\Http\Resources\StandingResource;
use App\Models\Fixture;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class MatchResultController extends Controller
{
    /**
     * Lança/edita o resultado de um jogo e devolve a classificação recalculada.
     * Só o organizador do torneio pode; conflito de versão vira 409 (StaleResultException).
     */
    public function update(
        ConfirmMatchResultRequest $request,
        Fixture $fixture,
        ConfirmMatchResult $action,
    ): AnonymousResourceCollection {
        Gate::authorize('manage', $fixture->tournament);

        $standings = $action->handle(
            $fixture,
            $request->homeScore(),
            $request->awayScore(),
            $request->expectedVersion(),
        );

        return StandingResource::collection($standings);
    }
}
