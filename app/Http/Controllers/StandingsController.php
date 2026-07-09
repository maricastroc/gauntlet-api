<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Tournament\ComputeGroupStandings;
use App\Http\Resources\StandingResource;
use App\Models\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class StandingsController extends Controller
{
    /** Leitura pública: a classificação atual de um grupo (projeção das partidas). */
    public function show(Group $group, ComputeGroupStandings $standings): AnonymousResourceCollection
    {
        return StandingResource::collection($standings->for($group));
    }
}
