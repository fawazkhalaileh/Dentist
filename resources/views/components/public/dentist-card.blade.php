@props(['profile'])

@php $t = $profile->translation(); @endphp

<article class="rounded-lg border border-line p-5 text-center">
    @if ($profile->photo)
        <x-ui.picture :media="$profile->photo" alt="" sizes="200px" class="mx-auto mb-4 size-32 rounded-full object-cover" />
    @endif
    <h3 class="font-semibold">
        <a href="{{ route('public.dentists.show', ['slug' => $profile->slug]) }}" class="hover:underline">{{ $t->display_name }}</a>
    </h3>
    @if ($t->specialty)
        <p class="mt-1 text-sm text-ink-muted">{{ $t->specialty }}</p>
    @endif
</article>
