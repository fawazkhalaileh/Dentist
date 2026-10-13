<?php

use App\Actions\Patients\StoreDocument;
use App\Enums\Permission;
use App\Models\Document;
use App\Models\Patient;
use App\Models\User;
use App\Support\Audit;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Title('Documents')] class extends Component {
    use WithFileUploads;

    public Patient $patient;

    /** Inside the patient page: no page header. */
    #[Locked]
    public bool $embedded = false;

    /** @var TemporaryUploadedFile|null */
    public $upload = null;

    public string $category = 'xray';

    public function mount(Patient $patient, bool $embedded = false): void
    {
        $this->embedded = $embedded;
        $this->authorize('viewClinical', $patient);
        $this->patient = $patient;

        Audit::record('clinical.documents_viewed', $patient);
    }

    /**
     * @return Collection<int, Document>
     */
    #[Computed]
    public function documents(): Collection
    {
        return $this->patient->documents()->with('uploader:id,name')->latest('id')->get();
    }

    public function canRemove(): bool
    {
        // Clinical files are kept; only administrators may remove one added by mistake.
        return $this->actor()->can(Permission::ClinicalViewAll->value) && $this->actor()->can('writeClinical', $this->patient);
    }

    public function save(StoreDocument $store): void
    {
        $this->authorize('writeClinical', $this->patient);

        $this->validate([
            'upload' => ['required', 'file', 'mimes:'.implode(',', Document::MIMES), 'max:'.Document::MAX_KILOBYTES],
            'category' => ['required', Rule::in(Document::CATEGORIES)],
        ], [
            'upload.mimes' => __('Upload a PDF, JPG, PNG or WebP file.'),
        ]);

        $store->handle($this->patient, $this->upload, $this->category, $this->actor());

        $this->upload = null;
        unset($this->documents);
        Flux::toast(variant: 'success', text: __('Document uploaded.'));
    }

    public function remove(int $id): void
    {
        abort_unless($this->canRemove(), 403);

        $document = $this->patient->documents()->findOrFail($id);
        $document->delete();
        Audit::record('clinical.document_removed', $document);

        unset($this->documents);
        Flux::toast(variant: 'success', text: __('Document removed from the list. The file is kept.'));
    }

    private function actor(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}; ?>

<section @class(['w-full', 'max-w-5xl' => ! $embedded])>
    @unless ($embedded)
        <x-ui.page-header :title="__('Documents')" :description="$patient->full_name.' · '.$patient->patient_number">
            <x-slot:actions>
                <flux:button :href="route('clinic.patients.show', $patient)" wire:navigate>{{ __('Open patient record') }}</flux:button>
            </x-slot:actions>
        </x-ui.page-header>
    @endunless

    @can('writeClinical', $patient)
        <flux:card class="mb-6">
            <form wire:submit="save" class="grid gap-4 sm:grid-cols-[1fr_14rem_auto] sm:items-end">
                <div>
                    <label for="document-upload" class="mb-1 block text-sm font-medium">{{ __('File') }}</label>
                    <input id="document-upload" type="file" wire:model="upload" accept="application/pdf,image/jpeg,image/png,image/webp"
                           class="block w-full text-sm file:me-3 file:rounded-md file:border file:border-zinc-300 file:bg-white file:px-3 file:py-1.5 file:text-sm dark:file:border-zinc-600 dark:file:bg-zinc-800"
                           @error('upload') aria-invalid="true" aria-describedby="document-upload-error" @enderror>
                    <p wire:loading wire:target="upload" class="mt-1 text-xs text-zinc-500">{{ __('Uploading…') }}</p>
                    <p class="mt-1 text-xs text-zinc-500">{{ __('PDF, JPG, PNG or WebP, up to 10 MB. Files are stored privately.') }}</p>
                    @error('upload') <p id="document-upload-error" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <flux:select wire:model="category" :label="__('Type')">
                    @foreach (\App\Models\Document::CATEGORIES as $type)
                        <flux:select.option value="{{ $type }}">{{ \App\Models\Document::categoryLabel($type) }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="upload,save">{{ __('Upload') }}</flux:button>
            </form>
        </flux:card>
    @endcan

    @if ($this->documents->isEmpty())
        <x-ui.empty-state :title="__('No documents yet')" />
    @else
        <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-start text-sm">
                <thead class="bg-zinc-50 text-zinc-600 dark:bg-zinc-900 dark:text-zinc-400">
                    <tr>
                        <th scope="col" class="px-4 py-2 text-start font-medium">{{ __('File') }}</th>
                        <th scope="col" class="px-4 py-2 text-start font-medium">{{ __('Type') }}</th>
                        <th scope="col" class="px-4 py-2 text-start font-medium">{{ __('Added') }}</th>
                        <th scope="col" class="px-4 py-2"><span class="sr-only">{{ __('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($this->documents as $document)
                        <tr wire:key="document-{{ $document->id }}">
                            <td class="px-4 py-2">
                                <span class="font-medium" dir="auto">{{ $document->original_name }}</span>
                                <span class="block text-xs text-zinc-500">{{ \Illuminate\Support\Number::fileSize($document->size_bytes) }}</span>
                            </td>
                            <td class="px-4 py-2">{{ \App\Models\Document::categoryLabel($document->category) }}</td>
                            <td class="px-4 py-2">
                                {{ $document->created_at?->format('Y-m-d H:i') }}
                                <span class="block text-xs text-zinc-500">{{ $document->uploader?->name }}</span>
                            </td>
                            <td class="px-4 py-2 text-end">
                                <div class="flex justify-end gap-1">
                                    @if ($document->isViewableInline())
                                        <flux:button size="xs" :href="route('clinic.patients.documents.download', ['patient' => $patient, 'document' => $document->id, 'inline' => 1])" target="_blank">{{ __('View') }}</flux:button>
                                    @endif
                                    <flux:button size="xs" :href="route('clinic.patients.documents.download', ['patient' => $patient, 'document' => $document->id])">{{ __('Download') }}</flux:button>
                                    @if ($this->canRemove())
                                        <flux:button size="xs" variant="ghost" wire:click="remove({{ $document->id }})" wire:confirm="{{ __('Remove this document from the list? The file itself is kept on the server.') }}">{{ __('Remove') }}</flux:button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
