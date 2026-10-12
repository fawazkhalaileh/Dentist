<?php

namespace App\Models;

use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $patient_number
 * @property int|null $user_id
 * @property string $first_name
 * @property string $last_name
 * @property Carbon|null $date_of_birth
 * @property string|null $sex
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $address_line
 * @property string|null $city
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_phone
 * @property string $preferred_locale
 * @property int|null $created_by
 * @property-read string $full_name
 * @property-read User|null $user
 */
#[Fillable([
    'user_id', 'first_name', 'last_name', 'date_of_birth', 'sex', 'phone', 'email',
    'address_line', 'city', 'emergency_contact_name', 'emergency_contact_phone',
    'preferred_locale', 'created_by',
])]
class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    protected static function booted(): void
    {
        // Temporary unique value; replaced with the id-based number right after insert.
        static::creating(function (Patient $patient) {
            $patient->patient_number = 'TMP-'.Str::ulid();
        });

        static::created(function (Patient $patient) {
            $patient->patient_number = static::formatNumber($patient->id);
            $patient->saveQuietly();
        });
    }

    public static function formatNumber(int $id): string
    {
        return config('clinic.patients.number_prefix')
            .str_pad((string) $id, (int) config('clinic.patients.number_padding'), '0', STR_PAD_LEFT);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->first_name} {$this->last_name}"));
    }

    /**
     * Whether the dentist is on this patient's care team: explicitly assigned,
     * or has (had) an appointment or treatment with the patient.
     */
    public function hasCareTeamMember(User $dentist): bool
    {
        return $this->dentists()->whereKey($dentist->id)->exists()
            || $this->appointments()->where('dentist_id', $dentist->id)->exists()
            || $this->treatmentItems()->where('dentist_id', $dentist->id)->exists();
    }

    /**
     * Limit a query to patients on the dentist's care team.
     *
     * @param  Builder<Patient>  $query
     */
    public function scopeInCareTeamOf(Builder $query, User $dentist): void
    {
        $query->where(fn (Builder $q) => $q
            ->whereHas('dentists', fn (Builder $d) => $d->whereKey($dentist->id))
            ->orWhereHas('appointments', fn (Builder $a) => $a->where('dentist_id', $dentist->id))
            ->orWhereHas('treatmentItems', fn (Builder $t) => $t->where('dentist_id', $dentist->id)));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function dentists(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'patient_dentist', 'patient_id', 'dentist_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /**
     * @return HasMany<MedicalHistory, $this>
     */
    public function medicalHistories(): HasMany
    {
        return $this->hasMany(MedicalHistory::class);
    }

    /**
     * @return HasMany<DentalHistory, $this>
     */
    public function dentalHistories(): HasMany
    {
        return $this->hasMany(DentalHistory::class);
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * @return HasMany<TreatmentPlan, $this>
     */
    public function treatmentPlans(): HasMany
    {
        return $this->hasMany(TreatmentPlan::class);
    }

    /**
     * @return HasMany<TreatmentItem, $this>
     */
    public function treatmentItems(): HasMany
    {
        return $this->hasMany(TreatmentItem::class);
    }

    /**
     * @return HasMany<ClinicalNote, $this>
     */
    public function clinicalNotes(): HasMany
    {
        return $this->hasMany(ClinicalNote::class);
    }

    /**
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
