<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Collection extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'sort_order', 'is_published'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function titles(): BelongsToMany
    {
        return $this->belongsToMany(Title::class, 'collection_items')
            ->withPivot('position')->withTimestamps()->orderByPivot('position');
    }
}
