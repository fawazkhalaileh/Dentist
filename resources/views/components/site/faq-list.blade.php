@props(['items', 'markdown' => false])

{{-- items: list of [question, answer]. Native <details>, so it works without JavaScript. --}}
<div {{ $attributes->class('divide-y divide-line border-y border-line') }}>
    @foreach ($items as [$question, $answer])
        <details class="group py-2">
            <summary class="flex cursor-pointer items-center justify-between gap-6 py-4 text-lg font-medium">
                {{ $question }}
                <span class="flex size-9 shrink-0 items-center justify-center rounded-full border border-line text-wine transition group-open:rotate-45 group-open:bg-wine group-open:text-ivory" aria-hidden="true">
                    <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                </span>
            </summary>
            <div class="pb-5 pe-14 leading-relaxed text-ink-muted">
                @if ($markdown)
                    <x-ui.prose :markdown="$answer" />
                @else
                    <p>{{ $answer }}</p>
                @endif
            </div>
        </details>
    @endforeach
</div>
