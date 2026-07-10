<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A match. The model is named Fixture (not Match — a reserved word in PHP 8+),
 * mapped to the `matches` table.
 */
class Fixture extends Model
{
    protected $table = 'matches';

    protected $guarded = [];

    protected $casts = [
        'home_score' => 'integer',
        'away_score' => 'integer',
        'home_penalties' => 'integer',
        'away_penalties' => 'integer',
        'version' => 'integer',
        'kickoff_at' => 'datetime',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function scopeFinished(Builder $query): Builder
    {
        return $query->where('status', 'finished');
    }
}
