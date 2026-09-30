<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SetlistItem extends Model
{
    protected $fillable = [
        'setlist_set_id',
        'song_id',
        'position',
        'key',
        'notes',
    ];

    public function set(): BelongsTo
    {
        return $this->belongsTo(SetlistSet::class, 'setlist_set_id');
    }

    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }
}
