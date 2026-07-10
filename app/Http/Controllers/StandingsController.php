<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Tournament\ComputeGroupStandings;
use App\Http\Resources\StandingResource;
use App\Models\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class StandingsController extends Controller
{
    /** Public read: the current standings of a group (projection from the matches). */
    public function show(Group $group, ComputeGroupStandings $standings): AnonymousResourceCollection
    {
        return StandingResource::collection($standings->for($group));
    }
}
