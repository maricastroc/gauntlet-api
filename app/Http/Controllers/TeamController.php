<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Tournament\AddTeams;
use App\Http\Requests\AddTeamsRequest;
use App\Http\Resources\TeamResource;
use App\Models\Tournament;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class TeamController extends Controller
{
    /** Adds teams to the tournament, in batch. Owner only. */
    public function store(AddTeamsRequest $request, Tournament $tournament, AddTeams $action): JsonResponse
    {
        Gate::authorize('manage', $tournament);

        $teams = $action->handle($tournament, $request->teams());

        return TeamResource::collection($teams)->response()->setStatusCode(201);
    }
}
