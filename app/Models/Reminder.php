<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reminder extends Model
{
    protected $fillable = [
        'event_id',
        'telegram_destination_id',
        'offset_minutes',
        'send_at',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'offset_minutes' => 'integer',
            'send_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function telegramDestination(): BelongsTo
    {
        return $this->belongsTo(TelegramDestination::class);
    }
}
