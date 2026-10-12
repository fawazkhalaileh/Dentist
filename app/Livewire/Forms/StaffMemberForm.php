<?php

namespace App\Livewire\Forms;

use App\Enums\Role;
use App\Livewire\Forms\Concerns\TranslatedFields;
use App\Models\User;
use App\Support\Locales;
use App\Support\Slug;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * A staff account plus, for dentists, the public "Meet the dentists" profile.
 */
class StaffMemberForm extends Form
{
    use TranslatedFields;

    private const FIELDS = ['display_name', 'specialty', 'bio'];

    public ?int $userId = null;

    public string $name = '';

    public string $email = '';

    public string $role = 'receptionist';

    public bool $is_active = true;

    public string $locale = 'en';

    // Public profile (dentists only).
    public bool $is_public = false;

    public string $slug = '';

    public string $license_number = '';

    public ?int $photo_media_id = null;

    public int $sort_order = 0;

    /** @var array<string, array<string, ?string>> */
    public array $translations = [];

    public function load(?User $user): void
    {
        $profile = $user?->staffProfile;
        $this->translations = $this->blankOrExistingTranslations(self::FIELDS, $profile?->translations);

        if ($user) {
            $this->userId = $user->id;
            $this->name = $user->name;
            $this->email = $user->email;
            $this->role = $user->roles->first()->name ?? Role::Receptionist->value;
            $this->is_active = $user->is_active;
            $this->locale = $user->locale;
        }

        if ($profile) {
            $this->is_public = $profile->is_public;
            $this->slug = $profile->slug;
            $this->license_number = (string) $profile->license_number;
            $this->photo_media_id = $profile->photo_media_id;
            $this->sort_order = $profile->sort_order;
        }
    }

    public function isDentist(): bool
    {
        return $this->role === Role::Dentist->value;
    }

    /**
     * Default the profile slug and the default-language display name from the account name.
     */
    public function prepareProfile(): void
    {
        if (! $this->isDentist()) {
            return;
        }

        $default = Locales::default();
        if (trim((string) ($this->translations[$default]['display_name'] ?? '')) === '') {
            $this->translations[$default]['display_name'] = trim($this->name);
        }

        $this->slug = Slug::make($this->slug !== '' ? $this->slug : (string) $this->translations[$default]['display_name']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->userId)],
            'role' => ['required', Rule::in(array_map(fn (Role $r) => $r->value, Role::staff()))],
            'is_active' => ['boolean'],
            'locale' => ['required', Rule::in(Locales::supported())],
        ];

        if ($this->isDentist()) {
            $rules += [
                'is_public' => ['boolean'],
                'slug' => ['required', 'string', 'max:255', 'regex:'.Slug::PATTERN,
                    Rule::unique('staff_profiles', 'slug')->ignore($this->userId, 'user_id')],
                'license_number' => ['nullable', 'string', 'max:100'],
                'photo_media_id' => ['nullable', 'integer', 'exists:media,id'],
                'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            ];

            foreach (Locales::supported() as $l) {
                $rules["translations.{$l}.display_name"] = [$this->titleRule($l), 'string', 'max:255'];
                $rules["translations.{$l}.specialty"] = ['nullable', 'string', 'max:255'];
                $rules["translations.{$l}.bio"] = ['nullable', 'string', 'max:5000'];
            }
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [...$this->translatedAttributeNames(self::FIELDS), 'slug' => __('profile URL')];
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'name' => trim($this->name),
            'email' => strtolower(trim($this->email)),
            'role' => $this->role,
            'is_active' => $this->is_active,
            'locale' => $this->locale,
            'profile' => $this->isDentist() ? [
                'is_public' => $this->is_public,
                'slug' => $this->slug,
                'license_number' => trim($this->license_number) ?: null,
                'photo_media_id' => $this->photo_media_id ?: null,
                'sort_order' => $this->sort_order,
                'translations' => $this->translations,
            ] : null,
        ];
    }
}
