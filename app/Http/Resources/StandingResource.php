<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Tournament\Standings\Standing;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Formats a standings row (Domain value object) as JSON.
 *
 * @property-read Standing $resource
 */
final class StandingResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $standing = $this->resource;

        return [
            'position' => $standing->position,
            'team' => [
                'id' => $standing->team->id,
                'name' => $standing->team->name,
            ],
            'played' => $standing->played,
            'won' => $standing->won,
            'drawn' => $standing->drawn,
            'lost' => $standing->lost,
            'goals_for' => $standing->goalsFor,
            'goals_against' => $standing->goalsAgainst,
            'goal_difference' => $standing->goalDifference(),
            'points' => $standing->points,
            'form' => $standing->form,
            'qualified' => $standing->qualified,
        ];
    }
}
