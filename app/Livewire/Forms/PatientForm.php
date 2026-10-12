<?php

namespace App\Livewire\Forms;

use App\Enums\Role;
use App\Models\Patient;
use App\Models\User;
use App\Support\Locales;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * A patient's demographic and contact details plus care team. No clinical data.
 */
class PatientForm extends Form
{
    public const SEXES = ['female', 'male'];

    public ?int $patientId = null;

    public string $first_name = '';

    public string $last_name = '';

    public string $date_of_birth = '';

    public string $sex = '';

    public string $phone = '';

    public string $email = '';

    public string $address_line = '';

    public string $city = '';

    public string $emergency_contact_name = '';

    public string $emergency_contact_phone = '';

    public string $preferred_locale = 'en';

    /** @var list<int> */
    public array $dentist_ids = [];

    public function load(?Patient $patient): void
    {
        $this->preferred_locale = Locales::default();

        if (! $patient) {
            return;
        }

        $this->patientId = $patient->id;
        foreach (['first_name', 'last_name', 'sex', 'phone', 'email', 'address_line', 'city', 'emergency_contact_name', 'emergency_contact_phone'] as $field) {
            $this->{$field} = (string) $patient->getAttribute($field);
        }
        $this->date_of_birth = $patient->date_of_birth?->format('Y-m-d') ?? '';
        $this->preferred_locale = $patient->preferred_locale;
        $this->dentist_ids = array_values(array_map('intval', $patient->dentists()->pluck('users.id')->all()));
    }

    /**
     * Existing patients that look like the same person: same phone number, or
     * same name and date of birth. Shown before creating a possible duplicate.
     *
     * @return Collection<int, Patient>
     */
    public function possibleDuplicates(): Collection
    {
        // Compare without leading zeros so 079... and +962 79... count as the same number.
        $digits = ltrim(preg_replace('/\D+/', '', $this->phone) ?? '', '0');

        return Patient::query()
            ->whereKeyNot($this->patientId ?? 0)
            ->where(function ($q) use ($digits) {
                $q->whereRaw('1 = 0');
                if (strlen($digits) >= 7) {
                    $stored = "TRIM(LEADING '0' FROM REGEXP_REPLACE(phone, '[^0-9]', ''))";
                    $q->orWhereRaw("({$stored} LIKE ? OR ? LIKE CONCAT('%', {$stored}))", ['%'.$digits, $digits]);
                }
                if ($this->date_of_birth !== '' && trim($this->first_name) !== '' && trim($this->last_name) !== '') {
                    $q->orWhere(fn ($q) => $q
                        ->where('first_name', trim($this->first_name))
                        ->where('last_name', trim($this->last_name))
                        ->whereDate('date_of_birth', $this->date_of_birth));
                }
            })
            ->orderBy('last_name')
            ->limit(5)
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today', 'after:1900-01-01'],
            'sex' => ['nullable', Rule::in(self::SEXES)],
            'phone' => ['nullable', 'required_without:email', 'string', 'max:30', 'regex:/^[0-9+()\-\s.]+$/'],
            'email' => ['nullable', 'required_without:phone', 'email', 'max:255'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s.]+$/'],
            'preferred_locale' => ['required', Rule::in(Locales::supported())],
            'dentist_ids' => ['array'],
            'dentist_ids.*' => ['integer', Rule::in($this->dentistOptionIds())],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'phone.required_without' => __('Give a phone number or an email address.'),
            'email.required_without' => __('Give a phone number or an email address.'),
            'phone.regex' => __('Use digits, spaces and + ( ) - only.'),
            'emergency_contact_phone.regex' => __('Use digits, spaces and + ( ) - only.'),
        ];
    }

    /**
     * Active dentists who can be put on a care team, plus anyone already on this
     * patient's stored care team (read from the database, never from the request).
     *
     * @return list<int>
     */
    public function dentistOptionIds(): array
    {
        $current = $this->patientId
            ? Patient::find($this->patientId)?->dentists()->pluck('users.id')->all() ?? []
            : [];

        return array_values(array_unique(array_map('intval', [
            ...User::havingRole(Role::Dentist)->where('is_active', true)->pluck('id')->all(),
            ...$current,
        ])));
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $nullable = fn (string $v) => trim($v) === '' ? null : trim($v);

        return [
            'first_name' => trim($this->first_name),
            'last_name' => trim($this->last_name),
            'date_of_birth' => $nullable($this->date_of_birth),
            'sex' => $nullable($this->sex),
            'phone' => $nullable($this->phone),
            'email' => $nullable(strtolower($this->email)),
            'address_line' => $nullable($this->address_line),
            'city' => $nullable($this->city),
            'emergency_contact_name' => $nullable($this->emergency_contact_name),
            'emergency_contact_phone' => $nullable($this->emergency_contact_phone),
            'preferred_locale' => $this->preferred_locale,
        ];
    }

    /**
     * @return list<int>
     */
    public function dentistIds(): array
    {
        return array_values(array_unique(array_map('intval', $this->dentist_ids)));
    }
}
