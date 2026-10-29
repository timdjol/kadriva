<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Title extends Model
{
    protected $fillable = [
        'tmdb_id', 'type', 'slug', 'name', 'original_name', 'description',
        'release_year', 'release_date', 'rating', 'popularity', 'runtime_minutes',
        'poster_url', 'backdrop_url', 'country_code', 'age_rating', 'status', 'source_updated_at',
        'imported_at', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'release_date' => 'date',
            'source_updated_at' => 'datetime',
            'imported_at' => 'datetime',
            'rating' => 'decimal:1',
            'is_published' => 'boolean',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function credits(): HasMany
    {
        return $this->hasMany(TitleCredit::class)->orderBy('position');
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }

    public function seasons(): HasMany
    {
        return $this->hasMany(Season::class)->orderBy('number');
    }

    public function videoSources(): HasMany
    {
        return $this->hasMany(VideoSource::class);
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'collection_items')
            ->withPivot('position')->withTimestamps();
    }
}
