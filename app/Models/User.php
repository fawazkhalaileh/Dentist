<?php

namespace App\Models;

use App\Enums\Role;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $locale
 * @property bool $is_active
 * @property CarbonImmutable|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property CarbonImmutable|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property CarbonImmutable|null $last_login_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read StaffProfile|null $staffProfile
 * @property-read Patient|null $patient
 */
#[Fillable(['name', 'email', 'password', 'locale'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * @return HasOne<StaffProfile, $this>
     */
    public function staffProfile(): HasOne
    {
        return $this->hasOne(StaffProfile::class);
    }

    /**
     * The patient record linked to this portal account, if any.
     *
     * @return HasOne<Patient, $this>
     */
    public function patient(): HasOne
    {
        return $this->hasOne(Patient::class);
    }

    /**
     * Patients whose care team this dentist belongs to.
     *
     * @return BelongsToMany<Patient, $this>
     */
    public function assignedPatients(): BelongsToMany
    {
        return $this->belongsToMany(Patient::class, 'patient_dentist', 'dentist_id', 'patient_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /**
     * Users with the role. Unlike spatie's role() scope this matches nothing,
     * instead of throwing RoleDoesNotExist, when the role has not been created.
     *
     * @param  Builder<User>  $query
     */
    public function scopeHavingRole(Builder $query, Role $role): void
    {
        $query->whereHas('roles', fn (Builder $roles) => $roles->where('name', $role->value)->where('guard_name', 'web'));
    }

    public function hasRoleEnum(Role $role): bool
    {
        return $this->hasRole($role->value);
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole(array_map(fn (Role $r) => $r->value, Role::staff()));
    }

    public function hasConfirmedTwoFactor(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    /**
     * Where this user lands after login.
     */
    public function homeRoute(): string
    {
        return $this->isStaff() ? route('clinic.dashboard') : route('portal.dashboard');
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}
