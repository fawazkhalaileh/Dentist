@props([
    'label',
    // Livewire property holding the temporary upload, e.g. "featuredUpload".
    'model',
    'upload' => null,
    'current' => null,
    // Livewire method that clears the current image (optional).
    'remove' => null,
    'hint' => null,
])

@php $id = 'upload-'.str_replace('.', '-', $model); @endphp

<div class="space-y-2">
    <label for="{{ $id }}" class="block text-sm font-medium">{{ $label }}</label>

    <div class="flex flex-wrap items-start gap-4">
        @if ($upload && method_exists($upload, 'isPreviewable') && $upload->isPreviewable())
            <img src="{{ $upload->temporaryUrl() }}" alt="{{ __('Preview of the selected image') }}" class="h-28 w-auto rounded border border-zinc-200 object-cover dark:border-zinc-700">
        @elseif ($current)
            <img src="{{ $current->thumbUrl() }}" alt="{{ $current->altFor() }}" class="h-28 w-auto rounded border border-zinc-200 object-cover dark:border-zinc-700">
        @else
            <div class="flex h-28 w-40 items-center justify-center rounded border border-dashed border-zinc-300 text-xs text-zinc-500 dark:border-zinc-600">{{ __('No image') }}</div>
        @endif

        <div class="space-y-2">
            <input id="{{ $id }}" type="file" wire:model="{{ $model }}" accept="image/jpeg,image/png,image/webp"
                   class="block text-sm file:me-3 file:rounded-md file:border file:border-zinc-300 file:bg-white file:px-3 file:py-1.5 file:text-sm dark:file:border-zinc-600 dark:file:bg-zinc-800"
                   @error($model) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror>
            <p wire:loading wire:target="{{ $model }}" class="text-xs text-zinc-500">{{ __('Uploading…') }}</p>
            <p class="text-xs text-zinc-500">{{ $hint ?? __('JPG, PNG or WebP, up to 8 MB. Images are resized and converted automatically.') }}</p>
            @if ($remove && ($current || $upload))
                <flux:button size="sm" variant="ghost" wire:click="{{ $remove }}">{{ __('Remove image') }}</flux:button>
            @endif
        </div>
    </div>

    @error($model) <p id="{{ $id }}-error" class="text-sm text-red-600">{{ $message }}</p> @enderror
</div>
