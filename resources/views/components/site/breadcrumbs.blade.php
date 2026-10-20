@props(['items'])

{{-- items: list of [label, url|null]; the last one is the current page. Also emitted as BreadcrumbList data. --}}
<nav aria-label="{{ __('Breadcrumb') }}" {{ $attributes->class('text-sm text-ink-muted') }}>
    <ol class="flex flex-wrap items-center gap-2">
        @foreach ($items as $i => [$label, $url])
            <li class="flex items-center gap-2">
                @if ($i > 0)<span aria-hidden="true" class="text-stone">/</span>@endif
                @if ($url && ! $loop->last)
                    <a href="{{ $url }}" class="hover:text-wine">{{ $label }}</a>
                @else
                    <span aria-current="page" class="text-ink">{{ $label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => collect($items)->values()->map(fn ($item, $i) => array_filter([
        '@type' => 'ListItem', 'position' => $i + 1, 'name' => $item[0], 'item' => $item[1] ?? url()->current(),
    ]))->all(),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
