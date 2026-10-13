@props(['profile'])

@php $t = $profile->translation(); @endphp

<article class="relative rounded-xl border border-line p-5 text-center transition hover:border-brand hover:shadow-sm">
    @if ($profile->photo)
        <x-ui.picture :media="$profile->photo" alt="" sizes="200px" class="mx-auto mb-4 size-32 rounded-full object-cover" />
    @else
        @php $initials = collect(preg_split('/\s+/', trim(preg_replace('/^(Dr\.?|د\.)\s*/u', '', $t->display_name) ?? '')))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode(''); @endphp
        <span aria-hidden="true" class="mx-auto mb-4 flex size-24 items-center justify-center rounded-full bg-brand-soft text-2xl font-semibold text-brand">{{ mb_strtoupper($initials) }}</span>
    @endif
    <h3 class="font-semibold">
        <a href="{{ route('public.dentists.show', ['slug' => $profile->slug]) }}" class="after:absolute after:inset-0">{{ $t->display_name }}</a>
    </h3>
    @if ($t->specialty)
        <p class="mt-1 text-sm text-ink-muted">{{ $t->specialty }}</p>
    @endif
</article>
