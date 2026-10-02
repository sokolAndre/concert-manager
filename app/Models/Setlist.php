<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Setlist extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'description',
        'concert_details_id',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function sets(): HasMany
    {
        return $this->hasMany(SetlistSet::class)->orderBy('position');
    }

    public function concertDetails(): BelongsTo
    {
        return $this->belongsTo(ConcertDetails::class);
    }
}
