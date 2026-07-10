<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Tournament\CreateTournament;
use App\Http\Requests\CreateTournamentRequest;
use App\Http\Resources\TournamentDetailResource;
use App\Http\Resources\TournamentResource;
use App\Models\Tournament;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class TournamentController extends Controller
{
    /** Lista os torneios do organizador autenticado. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $tournaments = Tournament::query()
            ->where('user_id', $request->user()->id)
            ->withCount(['teams', 'stages'])
            ->latest()
            ->get();

        return TournamentResource::collection($tournaments);
    }

    /** Cria um torneio (rascunho). */
    public function store(CreateTournamentRequest $request, CreateTournament $action): JsonResponse
    {
        $tournament = $action->handle($request->user(), $request->tournamentName());

        return (new TournamentResource($tournament->loadCount(['teams', 'stages'])))
            ->response()
            ->setStatusCode(201);
    }

    /** A visão completa do torneio — estrutura + jogos (com versão). Pública (visão torcedor). */
    public function show(Tournament $tournament): TournamentDetailResource
    {
        return new TournamentDetailResource($tournament->loadFullDetail());
    }

    /** Remove o torneio (cascata cuida de times/etapas/jogos). Só o dono. */
    public function destroy(Tournament $tournament): \Illuminate\Http\Response
    {
        Gate::authorize('manage', $tournament);

        $tournament->delete();

        return response()->noContent();
    }
}
