@props([
    // Media models, or null for a neutral placeholder (no consent-confirmed case yet).
    'before' => null,
    'after' => null,
    'label' => null,
    'sizes' => '(min-width: 1024px) 50vw, 100vw',
])

{{--
    Before/after comparison. A native range input drives the split, so it works with
    mouse, touch and keyboard. Kept left-to-right in every language so "before" is always on the left.
    Images are shown exactly as supplied.
--}}
<div data-compare dir="ltr" style="--split: 50%" {{ $attributes->class('group/compare relative isolate aspect-[4/3] select-none overflow-hidden rounded-3xl bg-cream') }}>
    @if ($before && $after)
        <x-ui.picture :media="$after" alt="{{ __('After treatment') }}" :sizes="$sizes" class="absolute inset-0 !h-full w-full object-cover" />
        <div class="absolute inset-0" style="clip-path: inset(0 calc(100% - var(--split)) 0 0)">
            <x-ui.picture :media="$before" alt="{{ __('Before treatment') }}" :sizes="$sizes" class="absolute inset-0 !h-full w-full object-cover" />
        </div>
    @else
        <div class="absolute inset-0 flex items-center justify-center bg-[linear-gradient(135deg,#efe6da,#f8f3ec)]">
            <x-site.icon name="tooth" class="size-16 text-sand" />
        </div>
        <div class="absolute inset-0 flex items-center justify-center bg-[linear-gradient(135deg,#e6d9cb,#efe6da)]" style="clip-path: inset(0 calc(100% - var(--split)) 0 0)">
            <x-site.icon name="tooth" class="size-16 text-stone/40" />
        </div>
    @endif

    <span class="pointer-events-none absolute start-4 top-4 rounded-full bg-night/70 px-3 py-1 text-xs font-medium tracking-wide text-ivory backdrop-blur">{{ __('Before') }}</span>
    <span class="pointer-events-none absolute end-4 top-4 rounded-full bg-ivory/85 px-3 py-1 text-xs font-medium tracking-wide text-night backdrop-blur">{{ __('After') }}</span>

    {{-- Divider and handle --}}
    <div aria-hidden="true" class="pointer-events-none absolute inset-y-0 w-0.5 -translate-x-1/2 bg-ivory shadow-[0_0_12px_rgb(0_0_0/0.25)]" style="left: var(--split)">
        <span class="absolute top-1/2 left-1/2 flex size-12 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-ivory text-wine shadow-lg ring-wine ring-offset-2 group-has-[input:focus-visible]/compare:ring-2">
            <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m9 7-5 5 5 5M15 7l5 5-5 5"/></svg>
        </span>
    </div>

    <label class="sr-only" for="{{ $id = 'compare-'.\Illuminate\Support\Str::random(6) }}">{{ $label ?? __('Drag to compare before and after') }}</label>
    <input id="{{ $id }}" type="range" min="0" max="100" value="50" step="1"
           class="absolute inset-0 z-10 size-full cursor-ew-resize opacity-0" style="touch-action: pan-y" />
</div>
