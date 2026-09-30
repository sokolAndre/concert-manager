<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SetlistSet extends Model
{
    protected $fillable = [
        'setlist_id',
        'name',
        'position',
    ];

    public function setlist(): BelongsTo
    {
        return $this->belongsTo(Setlist::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SetlistItem::class)->orderBy('position');
    }
}
