<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\EventStatus;
use App\Enums\EventType;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Event extends Model
{
    protected $fillable = [
        'project_id',
        'type',
        'title',
        'description',
        'starts_at',
        'ends_at',
        'location',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => EventType::class,
            'status' => EventStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function concertDetails(): HasOne
    {
        return $this->hasOne(ConcertDetails::class);
    }
}
