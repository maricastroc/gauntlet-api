<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Tournament;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resumo de um torneio para a listagem ("meus torneios").
 *
 * @property-read Tournament $resource
 */
final class TournamentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'status' => $this->resource->status,
            'teams_count' => $this->whenCounted('teams'),
            'stages_count' => $this->whenCounted('stages'),
            'created_at' => $this->resource->created_at?->toIso8601String(),
        ];
    }
}
