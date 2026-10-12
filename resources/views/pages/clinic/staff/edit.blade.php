<?php

use App\Actions\Media\StoreMedia;
use App\Actions\Staff\CreateSetupLink;
use App\Actions\Staff\ResetTwoFactor;
use App\Actions\Staff\SaveStaffMember;
use App\Enums\Role;
use App\Livewire\Forms\StaffMemberForm;
use App\Livewire\StaffComponent;
use App\Models\Media;
use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Title('Staff member')] class extends StaffComponent {
    use WithFileUploads;

    public StaffMemberForm $form;

    public ?User $member = null;

    /** @var TemporaryUploadedFile|null */
    public $photoUpload = null;

    public ?string $setupLink = null;

    public function mount(?User $user = null): void
    {
        if ($user?->exists) {
            // Only staff accounts are managed here; patient portal accounts are not.
            abort_unless($user->isStaff(), 404);
            $this->member = $user->load(['roles', 'staffProfile.translations']);
        }

        $this->form->load($this->member);
    }

    #[Computed]
    public function photo(): ?Media
    {
        return $this->form->photo_media_id ? Media::find($this->form->photo_media_id) : null;
    }

    public function isSelf(): bool
    {
        return $this->member?->is($this->actor()) ?? false;
    }

    public function removePhoto(): void
    {
        $this->photoUpload = null;
        $this->form->photo_media_id = null;
    }

    public function save(SaveStaffMember $save, StoreMedia $storeMedia): void
    {
        // Form properties can be changed by the browser; the locked model is the source of truth.
        $this->form->userId = $this->member?->id;

        if ($this->form->isDentist()) {
            $this->validate(['photoUpload' => ['nullable', ...StoreMedia::rules()]]);

            if ($this->photoUpload) {
                $this->form->photo_media_id = $storeMedia->handle($this->photoUpload, $this->actor())->id;
                $this->photoUpload = null;
            }
        }

        $this->form->prepareProfile();
        $this->form->validate();

        $isNew = $this->member === null;
        $this->member = $save->handle($this->form->payload(), $this->actor(), $this->member);

        if ($isNew) {
            session()->flash('staff-created', true);
            $this->redirectRoute('clinic.staff.edit', $this->member, navigate: true);

            return;
        }

        $this->form->load($this->member);
        Flux::toast(variant: 'success', text: __('Staff member saved.'));
    }

    public function createSetupLink(CreateSetupLink $createSetupLink): void
    {
        abort_unless($this->member !== null && $this->member->is_active, 422);

        $this->setupLink = $createSetupLink->handle($this->member);
    }

    public function resetTwoFactor(ResetTwoFactor $resetTwoFactor): void
    {
        abort_unless($this->member !== null && ! $this->isSelf(), 422);

        $resetTwoFactor->handle($this->member, $this->actor());
        $this->member->refresh();
        Flux::toast(variant: 'success', text: __('Two-factor authentication reset. They will be asked to set it up again at their next sign-in.'));
    }
}; ?>

<section class="w-full max-w-4xl">
    <x-ui.page-header :title="$member ? $member->name : __('Add staff member')">
        <x-slot:actions>
            <flux:button :href="route('clinic.staff.index')" wire:navigate>{{ __('Back to staff') }}</flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    @if (session('staff-created'))
        <flux:callout variant="success" icon="check-circle" class="mb-6">
            <flux:callout.heading>{{ __('Account created') }}</flux:callout.heading>
            <flux:callout.text>{{ __('Create a setup link below and send it to them, so they can choose a password.') }}</flux:callout.text>
        </flux:callout>
    @endif

    <form wire:submit="save" class="space-y-6">
        <flux:card class="space-y-4">
            <flux:heading size="lg">{{ __('Account') }}</flux:heading>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="form.name" :label="__('Full name')" required />
                <flux:input wire:model="form.email" type="email" :label="__('Email')" required dir="ltr" />
                <flux:select wire:model.live="form.role" :label="__('Role')" :disabled="$this->isSelf()">
                    @foreach (\App\Enums\Role::staff() as $staffRole)
                        <flux:select.option value="{{ $staffRole->value }}">{{ $staffRole->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="form.locale" :label="__('Language')">
                    @foreach (\App\Support\Locales::supported() as $locale)
                        <flux:select.option value="{{ $locale }}">{{ __(config("clinic.locales.{$locale}.name")) }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <flux:text class="text-sm">
                @switch($form->role)
                    @case('admin') {{ __('Administrators can do everything, including managing staff and settings.') }} @break
                    @case('dentist') {{ __('Dentists see the patients in their care, their clinical records and their own calendar.') }} @break
                    @default {{ __('Receptionists manage patients\' contact details, appointments and the website, but not clinical records.') }}
                @endswitch
            </flux:text>
            <flux:error name="form.role" />
            @unless ($this->isSelf())
                <flux:checkbox wire:model="form.is_active" :label="__('Account active')" :description="__('Deactivated accounts are signed out immediately and cannot sign in. Their records are kept.')" />
            @endunless
        </flux:card>

        @if ($form->isDentist())
            <flux:card class="space-y-4">
                <flux:heading size="lg">{{ __('Website profile') }}</flux:heading>
                <flux:checkbox wire:model="form.is_public" :label="__('Show on the “Meet the dentists” page')" />

                <x-cms.image-upload :label="__('Photo')" model="photoUpload" :upload="$photoUpload" :current="$this->photo" remove="removePhoto" />

                <x-cms.locale-tabs>
                    @foreach (\App\Support\Locales::supported() as $locale)
                        <div x-show="tab === @js($locale)" x-cloak role="tabpanel" dir="{{ \App\Support\Locales::dir($locale) }}" lang="{{ $locale }}" class="space-y-4">
                            <flux:input wire:model="form.translations.{{ $locale }}.display_name" :label="__('Name shown on the website')" :description="$locale === \App\Support\Locales::default() ? __('Leave empty to use the full name.') : null" />
                            <flux:input wire:model="form.translations.{{ $locale }}.specialty" :label="__('Specialty')" />
                            <div>
                                <flux:textarea wire:model="form.translations.{{ $locale }}.bio" :label="__('Biography')" rows="5" />
                                <x-cms.markdown-help class="mt-1" />
                            </div>
                        </div>
                    @endforeach
                </x-cms.locale-tabs>

                <div class="grid gap-4 sm:grid-cols-3">
                    <flux:input wire:model="form.slug" :label="__('Profile URL')" :description="__('Leave empty to create it from the name.')" dir="ltr" />
                    <flux:input wire:model="form.license_number" :label="__('Licence number')" dir="ltr" />
                    <flux:input type="number" min="0" wire:model="form.sort_order" :label="__('Display order')" />
                </div>
            </flux:card>
        @endif

        <flux:button type="submit" variant="primary">{{ $member ? __('Save changes') : __('Create account') }}</flux:button>
    </form>

    @if ($member)
        <flux:card class="mt-6 space-y-4">
            <flux:heading size="lg">{{ __('Sign-in') }}</flux:heading>

            <div class="space-y-2">
                <flux:text>{{ __('A setup link lets them choose a new password. Send it to them directly, for example by message. It works once and expires after :minutes minutes.', ['minutes' => config('auth.passwords.users.expire')]) }}</flux:text>
                @if ($member->is_active)
                    <flux:button wire:click="createSetupLink" icon="link">{{ __('Create setup link') }}</flux:button>
                @else
                    <flux:text class="text-sm">{{ __('Reactivate the account to create a setup link.') }}</flux:text>
                @endif
                @if ($setupLink)
                    <flux:input :value="$setupLink" readonly copyable :label="__('Setup link')" dir="ltr" />
                @endif
            </div>

            <flux:separator />

            <div class="space-y-2">
                <flux:text>
                    {{ __('Two-factor authentication:') }}
                    <strong>{{ $member->hasConfirmedTwoFactor() ? __('set up') : __('not set up yet') }}</strong>.
                    {{ __('Staff must set it up before the clinic dashboard opens.') }}
                </flux:text>
                @if ($member->hasConfirmedTwoFactor() && ! $this->isSelf())
                    <flux:button variant="danger" wire:click="resetTwoFactor"
                                 wire:confirm="{{ __('Reset two-factor authentication for this person? Only do this if they lost access to their authenticator.') }}">
                        {{ __('Reset two-factor authentication') }}
                    </flux:button>
                @endif
            </div>
        </flux:card>
    @endif
</section>
