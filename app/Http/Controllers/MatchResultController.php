<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Tournament\ConfirmKnockoutResult;
use App\Actions\Tournament\ConfirmMatchResult;
use App\Exceptions\StaleResultException;
use App\Http\Requests\ConfirmMatchResultRequest;
use App\Http\Resources\BracketResource;
use App\Http\Resources\StandingResource;
use App\Models\Fixture;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Facades\Gate;

final class MatchResultController extends Controller
{
    /**
     * @throws StaleResultException on a version conflict (someone else saved this match first)
     */
    public function update(
        ConfirmMatchResultRequest $request,
        Fixture $fixture,
        ConfirmMatchResult $group,
        ConfirmKnockoutResult $knockout,
    ): Responsable {
        Gate::authorize('manage', $fixture->tournament);

        if ($fixture->tie_id !== null) {
            return new BracketResource($knockout->handle(
                $fixture,
                $request->homeScore(),
                $request->awayScore(),
                $request->expectedVersion(),
                $request->homePenalties(),
                $request->awayPenalties(),
            ));
        }

        return StandingResource::collection($group->handle(
            $fixture,
            $request->homeScore(),
            $request->awayScore(),
            $request->expectedVersion(),
        ));
    }
}
