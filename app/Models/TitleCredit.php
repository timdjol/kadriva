<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TitleCredit extends Model
{
    protected $fillable = ['title_id', 'name', 'character', 'position'];

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }
}
