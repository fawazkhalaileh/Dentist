{{--
    Default layout for full-page Livewire components (config livewire.component_layout),
    e.g. the account settings pages. Staff get the clinic shell, patients the portal shell.
--}}
@if (auth()->user()?->isStaff())
    <x-layouts::clinic :title="$title ?? null">
        {{ $slot }}
    </x-layouts::clinic>
@else
    <x-layouts::portal :title="$title ?? null">
        {{ $slot }}
    </x-layouts::portal>
@endif
