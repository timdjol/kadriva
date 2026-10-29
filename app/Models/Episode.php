<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Episode extends Model
{
    protected $fillable = [
        'season_id', 'tmdb_id', 'number', 'name', 'description', 'runtime_minutes', 'still_url', 'air_date',
    ];

    protected function casts(): array
    {
        return ['air_date' => 'date'];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function videoSources(): HasMany
    {
        return $this->hasMany(VideoSource::class);
    }
}
