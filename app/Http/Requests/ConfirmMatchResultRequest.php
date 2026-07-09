<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ConfirmMatchResultRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'home_score' => ['required', 'integer', 'min:0', 'max:99'],
            'away_score' => ['required', 'integer', 'min:0', 'max:99'],
            'expected_version' => ['required', 'integer', 'min:0'],
        ];
    }

    public function homeScore(): int
    {
        return (int) $this->integer('home_score');
    }

    public function awayScore(): int
    {
        return (int) $this->integer('away_score');
    }

    public function expectedVersion(): int
    {
        return (int) $this->integer('expected_version');
    }
}
