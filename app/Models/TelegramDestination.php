<?php

namespace App\Models;

use App\Enums\TelegramDestinationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramDestination extends Model
{
    protected $fillable = [
        'telegram_chat_id',
        'type',
        'title',
        'username',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => TelegramDestinationType::class,
            'is_active' => 'boolean',
        ];
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

}
