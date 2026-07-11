<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Tournament\BuildKnockout;
use App\Exceptions\InvalidTournamentStructure;
use App\Http\Resources\TournamentDetailResource;
use App\Models\Tournament;
use Illuminate\Support\Facades\Gate;

final class KnockoutController extends Controller
{
    /**
     * @throws InvalidTournamentStructure when the groups are not fully decided yet
     */
    public function store(Tournament $tournament, BuildKnockout $action): TournamentDetailResource
    {
        Gate::authorize('manage', $tournament);

        $action->handle($tournament);

        return new TournamentDetailResource($tournament->loadFullDetail());
    }
}
