<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use LogicException;

/**
 * An issued invoice. Its lines and total never change after creation; the
 * only change allowed is voiding it, with a reason. Amounts are fils.
 *
 * @property int $id
 * @property string $number
 * @property int $patient_id
 * @property CarbonImmutable $issued_on
 * @property int $total
 * @property string|null $notes
 * @property int $created_by
 * @property CarbonImmutable|null $voided_at
 * @property int|null $voided_by
 * @property string|null $void_reason
 * @property-read Patient $patient
 */
#[Fillable(['patient_id', 'issued_on', 'total', 'notes', 'created_by'])]
class Invoice extends Model
{
    protected function casts(): array
    {
        return [
            'issued_on' => 'date',
            'total' => 'integer',
            'voided_at' => 'datetime',
            // Free text on a patient's bill may mention treatment: encrypted at rest.
            'notes' => 'encrypted',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            $invoice->number = 'TMP-'.Str::ulid();
        });
        static::created(function (Invoice $invoice) {
            $invoice->number = 'INV-'.str_pad((string) $invoice->id, 6, '0', STR_PAD_LEFT);
            $invoice->saveQuietly();
        });
        static::updating(function (Invoice $invoice) {
            // Only voiding (and the number set right after insert) may change an invoice.
            $allowed = ['number', 'voided_at', 'voided_by', 'void_reason', 'updated_at'];
            if (array_diff(array_keys($invoice->getDirty()), $allowed) !== []) {
                throw new LogicException('Issued invoices cannot be changed; void and re-issue instead.');
            }
        });
        static::deleting(fn () => throw new LogicException('Invoices are voided, never deleted.'));
    }

    public function isVoid(): bool
    {
        return $this->voided_at !== null;
    }

    /**
     * @param  Builder<Invoice>  $query
     */
    public function scopeValid(Builder $query): void
    {
        $query->whereNull('voided_at');
    }

    /**
     * Amount paid against this invoice by valid payments linked to it.
     */
    public function paidAmount(): int
    {
        return (int) $this->payments()->whereNull('voided_at')->sum('amount');
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return HasMany<InvoiceLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
