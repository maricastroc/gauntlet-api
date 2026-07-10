<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Tournament;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class BuildGroupStageRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var Tournament $tournament */
        $tournament = $this->route('tournament');

        return [
            'qualify_count' => ['required', 'integer', 'min:1', 'max:4'],
            'groups' => ['required', 'array', 'min:1', 'max:16'],
            'groups.*.name' => ['required', 'string', 'max:8'],
            'groups.*.team_ids' => ['required', 'array', 'min:2', 'max:16'],
            'groups.*.team_ids.*' => [
                'integer',
                Rule::exists('teams', 'id')->where('tournament_id', $tournament->id),
            ],
        ];
    }

    public function qualifyCount(): int
    {
        return (int) $this->integer('qualify_count');
    }

    /** @return list<array{name: string, team_ids: int[]}> */
    public function groups(): array
    {
        return array_map(fn (array $group) => [
            'name' => trim((string) $group['name']),
            'team_ids' => array_map('intval', $group['team_ids']),
        ], $this->input('groups', []));
    }
}
