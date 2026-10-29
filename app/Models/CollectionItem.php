<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionItem extends Model
{
    protected $fillable = ['collection_id', 'title_id', 'position'];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }
}
