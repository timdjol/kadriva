<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provider extends Model
{
    protected $fillable = ['name', 'slug', 'homepage_url', 'integration_type', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function videoSources(): HasMany
    {
        return $this->hasMany(VideoSource::class);
    }
}
