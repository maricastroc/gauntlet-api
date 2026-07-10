<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    protected $guarded = [];

    protected $casts = [
        'tiebreak' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function stages(): HasMany
    {
        return $this->hasMany(Stage::class);
    }

    /** Carrega tudo que o TournamentDetailResource precisa. */
    public function loadFullDetail(): self
    {
        return $this->load([
            'teams',
            'stages.groups.teams',
            'stages.fixtures.homeTeam',
            'stages.fixtures.awayTeam',
            'stages.ties',
        ]);
    }
}
