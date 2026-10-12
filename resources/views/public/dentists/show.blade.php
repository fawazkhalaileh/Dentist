@php $t = $profile->translation(); @endphp

<x-layouts::public :title="$t->display_name" :description="$t->specialty" :alternates="$alternates" :image="$profile->photo?->url()" type="profile">
    <nav aria-label="{{ __('Breadcrumb') }}" class="mb-4 text-sm text-ink-muted">
        <a href="{{ route('public.dentists.index') }}" class="hover:underline">{{ __('Meet the dentists') }}</a>
        <span aria-hidden="true"> / </span>
        <span aria-current="page">{{ $t->display_name }}</span>
    </nav>

    <article class="grid gap-8 md:grid-cols-[240px_1fr]">
        <div>
            @if ($profile->photo)
                <x-ui.picture :media="$profile->photo" :alt="$t->display_name" eager sizes="240px" class="w-full rounded-lg object-cover" />
            @endif
        </div>
        <div class="max-w-2xl">
            <h1 class="text-3xl font-semibold">{{ $t->display_name }}</h1>
            @if ($t->specialty)
                <p class="mt-1 text-lg text-ink-muted">{{ $t->specialty }}</p>
            @endif
            @if ($t->bio)
                <x-ui.prose :markdown="$t->bio" class="mt-6" />
            @endif

            @if ($services->isNotEmpty())
                <h2 class="mt-8 text-xl font-semibold">{{ __('Services') }}</h2>
                <ul class="mt-3 flex flex-wrap gap-2">
                    @foreach ($services as $service)
                        @php $st = $service->translation(); @endphp
                        <li>
                            <a href="{{ route('public.services.show', ['slug' => $st->slug]) }}" class="inline-block rounded-full border border-line px-3 py-1 text-sm hover:bg-surface-muted">{{ $st->title }}</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </article>
</x-layouts::public>
