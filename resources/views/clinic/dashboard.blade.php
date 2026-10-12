@use('App\Enums\Role')
@use('App\Enums\TreatmentStatus')

<x-layouts::clinic :title="__('Dashboard')">
    <x-ui.page-header
        :title="__('Welcome, :name', ['name' => auth()->user()->name])"
        :description="__('Signed in as: :roles', ['roles' => $roles->map(fn ($r) => Role::from($r)->label())->join(', ')])"
    />

    <section aria-labelledby="modules-heading">
        <h2 id="modules-heading" class="mb-3 text-lg font-medium">{{ __('Your modules') }}</h2>

        <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($navigation as $group)
                @foreach ($group['items'] as $item)
                    @continue($item['route'] === 'clinic.dashboard')
                    <li>
                        <a href="{{ route($item['route']) }}" wire:navigate
                           class="flex items-center gap-3 rounded-lg border border-line p-4 hover:bg-surface-muted dark:border-zinc-700 dark:hover:bg-zinc-700">
                            <flux:icon :name="$item['icon']" class="size-5 shrink-0" />
                            <span>
                                <span class="block font-medium">{{ __($item['label']) }}</span>
                                <span class="block text-xs text-ink-muted dark:text-zinc-400">{{ __($group['heading']) }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            @endforeach
        </ul>
    </section>

    <section aria-labelledby="legend-heading" class="mt-10">
        <h2 id="legend-heading" class="mb-3 text-lg font-medium">{{ __('Treatment status legend') }}</h2>
        <ul class="flex flex-wrap gap-2">
            @foreach (TreatmentStatus::cases() as $status)
                <li><x-ui.status-badge :status="$status" /></li>
            @endforeach
        </ul>
    </section>
</x-layouts::clinic>
