<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Song extends Model
{
    protected $fillable = [
        'title',
        'artist',
        'duration_seconds',
        'notes',
    ];

    public function setlistItems(): HasMany
    {
        return $this->hasMany(SetlistItem::class);
    }
}
