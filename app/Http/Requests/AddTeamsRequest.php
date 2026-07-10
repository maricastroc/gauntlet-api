<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AddTeamsRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'teams' => ['required', 'array', 'min:1', 'max:64'],
            'teams.*.name' => ['required', 'string', 'min:1', 'max:255'],
            'teams.*.code' => ['nullable', 'string', 'max:8'],
            'teams.*.flag' => ['nullable', 'string', 'max:16'],
        ];
    }

    /** @return list<array{name: string, code: ?string, flag: ?string}> */
    public function teams(): array
    {
        return array_map(fn (array $team) => [
            'name' => trim((string) $team['name']),
            'code' => isset($team['code']) && $team['code'] !== '' ? (string) $team['code'] : null,
            'flag' => isset($team['flag']) && $team['flag'] !== '' ? (string) $team['flag'] : null,
        ], $this->input('teams', []));
    }
}
