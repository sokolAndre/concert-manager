<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'concert_details_id',
        'amount',
        'currency',
        'paid_at',
        'type',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function concertDetails(): BelongsTo
    {
        return $this->belongsTo(ConcertDetails::class);
    }
}
