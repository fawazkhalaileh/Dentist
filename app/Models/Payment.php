<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Money received from a patient. Never edited or deleted; a mistake is voided
 * with a reason. Amount in fils.
 *
 * @property int $id
 * @property int $patient_id
 * @property int|null $invoice_id
 * @property int $amount
 * @property PaymentMethod $method
 * @property string|null $reference
 * @property CarbonImmutable $paid_on
 * @property string|null $notes
 * @property int $received_by
 * @property CarbonImmutable|null $voided_at
 * @property string|null $void_reason
 */
#[Fillable(['patient_id', 'invoice_id', 'amount', 'method', 'reference', 'paid_on', 'notes', 'received_by'])]
class Payment extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'method' => PaymentMethod::class,
            'paid_on' => 'date',
            'voided_at' => 'datetime',
            'notes' => 'encrypted',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Payment $payment) {
            $allowed = ['voided_at', 'voided_by', 'void_reason', 'updated_at'];
            if (array_diff(array_keys($payment->getDirty()), $allowed) !== []) {
                throw new LogicException('Payments cannot be changed; void and record again instead.');
            }
        });
        static::deleting(fn () => throw new LogicException('Payments are voided, never deleted.'));
    }

    public function isVoid(): bool
    {
        return $this->voided_at !== null;
    }

    /**
     * @param  Builder<Payment>  $query
     */
    public function scopeValid(Builder $query): void
    {
        $query->whereNull('voided_at');
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
