<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Tournament\CreateTournament;
use App\Actions\Tournament\UpdateTournament;
use App\Http\Requests\CreateTournamentRequest;
use App\Http\Requests\UpdateTournamentRequest;
use App\Http\Resources\TournamentDetailResource;
use App\Http\Resources\TournamentResource;
use App\Models\Tournament;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\PersonalAccessToken;

final class TournamentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $token = $user->currentAccessToken();
        $tokenId = $token instanceof PersonalAccessToken ? $token->getKey() : null;

        $tournaments = Tournament::query()
            ->where('user_id', $user->id)
            ->where('is_demo_template', false)
            ->where(function ($query) use ($tokenId) {
                $query->whereNull('demo_token_id')->orWhere('demo_token_id', $tokenId);
            })
            ->withCount(['teams', 'stages'])
            ->latest()
            ->get();

        return TournamentResource::collection($tournaments);
    }

    public function store(CreateTournamentRequest $request, CreateTournament $action): JsonResponse
    {
        $tournament = $action->handle($request->user(), $request->tournamentName());

        return (new TournamentResource($tournament->loadCount(['teams', 'stages'])))
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateTournamentRequest $request,
        Tournament $tournament,
        UpdateTournament $action,
    ): TournamentResource {
        Gate::authorize('manage', $tournament);

        $action->handle($tournament, $request->tournamentName());

        return new TournamentResource($tournament->loadCount(['teams', 'stages']));
    }

    public function show(Tournament $tournament): TournamentDetailResource
    {
        return new TournamentDetailResource($tournament->loadFullDetail());
    }

    public function destroy(Tournament $tournament): Response
    {
        Gate::authorize('manage', $tournament);

        $tournament->delete();

        return response()->noContent();
    }
}
