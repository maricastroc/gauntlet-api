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
        'is_demo_template' => 'boolean',
        'demo_token_id' => 'integer',
        'demo_expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The demo template this tournament was cloned from, if it is a sandbox. */
    public function template(): BelongsTo
    {
        return $this->belongsTo(self::class, 'template_id');
    }

    /** A per-session demo copy is one that belongs to a Sanctum token. */
    public function isDemoSandbox(): bool
    {
        return $this->demo_token_id !== null;
    }

    public function demoExpired(): bool
    {
        return $this->demo_expires_at !== null && $this->demo_expires_at->isPast();
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
