<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    protected $fillable = [
        'name',
        'organization',
        'phone',
        'email',
        'telegram',
        'whatsapp',
        'notes',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
