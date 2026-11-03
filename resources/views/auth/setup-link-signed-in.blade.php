<x-layouts::auth :title="__('Setup link')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('You are already signed in')"
            :description="__('This link is for setting a password:account', ['account' => $email ? ' ('.$email.')' : ''])" />

        <flux:callout icon="information-circle">
            <flux:callout.text>
                {{ __('You are signed in as :name. To use this link here, sign out first. Tip: send setup links to the staff member, or open them in a private window.', ['name' => auth()->user()->name]) }}
            </flux:callout.text>
        </flux:callout>

        <form method="POST" action="{{ route('setup-link.continue') }}" class="flex flex-col gap-3">
            @csrf
            <input type="hidden" name="next" value="{{ $next }}">
            <flux:button type="submit" variant="primary" class="w-full">{{ __('Sign out and continue') }}</flux:button>
        </form>

        <flux:button :href="route('dashboard')" variant="ghost" class="w-full">{{ __('Stay signed in') }}</flux:button>
    </div>
</x-layouts::auth>
