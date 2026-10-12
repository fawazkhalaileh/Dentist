<x-layouts::public :title="__('Before and after gallery')" :description="__('Treatment results shared with our patients\' consent.')">
    <x-ui.page-header :title="__('Before and after gallery')" :description="__('All photos are shared with the patient\'s written consent. Individual results vary.')" />

    @if ($items->isEmpty())
        <x-ui.empty-state :title="__('No gallery items published yet')" />
    @else
        <ul class="grid gap-8 md:grid-cols-2">
            @foreach ($items as $item)
                @php $t = $item->translation(); @endphp
                <li>
                    <figure class="rounded-lg border border-line p-4">
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <x-ui.picture :media="$item->beforeMedia" :alt="__('Before: :title', ['title' => $t->title])" sizes="(min-width: 768px) 25vw, 50vw" class="aspect-square w-full rounded object-cover" />
                                <p class="mt-1 text-center text-xs font-medium">{{ __('Before') }}</p>
                            </div>
                            <div>
                                <x-ui.picture :media="$item->afterMedia" :alt="__('After: :title', ['title' => $t->title])" sizes="(min-width: 768px) 25vw, 50vw" class="aspect-square w-full rounded object-cover" />
                                <p class="mt-1 text-center text-xs font-medium">{{ __('After') }}</p>
                            </div>
                        </div>
                        <figcaption class="mt-3">
                            <span class="font-medium">{{ $t->title }}</span>
                            @if ($t->description)
                                <span class="mt-1 block text-sm text-ink-muted">{{ $t->description }}</span>
                            @endif
                        </figcaption>
                    </figure>
                </li>
            @endforeach
        </ul>
    @endif
</x-layouts::public>
