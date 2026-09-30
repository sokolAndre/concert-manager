<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ConcertDetails extends Model
{
    protected $table = 'concert_details';

    protected $fillable = [
        'event_id',
        'fee',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'fee' => 'decimal:2',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function setlist(): HasOne
    {
        return $this->hasOne(Setlist::class);
    }

    public function paidAmount(): string
    {
        return number_format(
            (float) $this->payments()->sum('amount'),
            2,
            '.',
            ''
        );
    }

    public function remainingAmount(): string
    {
        return number_format(
            max(0, (float) $this->fee - (float) $this->paidAmount()),
            2,
            '.',
            ''
        );
    }

    public function paymentStatus(): PaymentStatus
    {
        $paid = (float) $this->paidAmount();
        $fee = (float) $this->fee;

        if ($paid <= 0) {
            return PaymentStatus::Unpaid;
        }

        if ($paid < $fee) {
            return PaymentStatus::Partial;
        }

        return PaymentStatus::Paid;
    }

    public function overpaidAmount(): string
    {
        return number_format(
            max(0, (float) $this->paidAmount() - (float) $this->fee),
            2,
            '.',
            ''
        );
    }
}
