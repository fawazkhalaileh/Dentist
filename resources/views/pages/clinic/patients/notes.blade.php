<?php

use App\Actions\Patients\RecordClinicalNote;
use App\Models\ClinicalNote;
use App\Models\Patient;
use App\Models\User;
use App\Support\Audit;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Clinical notes')] class extends Component {
    public Patient $patient;

    /** Inside the patient page: no page header. */
    #[Locked]
    public bool $embedded = false;

    public string $body = '';

    /** Note being corrected, if any. */
    #[Locked]
    public ?int $amendingId = null;

    public string $amendment = '';

    /** Notes whose earlier versions are expanded. @var list<int> */
    #[Locked]
    public array $expanded = [];

    public function mount(Patient $patient, bool $embedded = false): void
    {
        $this->embedded = $embedded;
        $this->authorize('viewClinical', $patient);
        $this->patient = $patient;

        Audit::record('clinical.notes_viewed', $patient);
    }

    /**
     * Every note for the patient, newest first.
     *
     * @return Collection<int, ClinicalNote>
     */
    #[Computed]
    public function notes(): Collection
    {
        return $this->patient->clinicalNotes()->with('author:id,name')->latest('id')->get();
    }

    /**
     * Notes that have not been replaced by a correction: what the record says now.
     *
     * @return Collection<int, ClinicalNote>
     */
    #[Computed]
    public function currentNotes(): Collection
    {
        $replaced = $this->notes->pluck('amends_id')->filter()->all();

        return $this->notes->reject(fn (ClinicalNote $note) => in_array($note->id, $replaced, true))->values();
    }

    /**
     * The versions a note replaced, newest first.
     *
     * @return list<ClinicalNote>
     */
    public function earlierVersions(ClinicalNote $note): array
    {
        $byId = $this->notes->keyBy('id');
        $versions = [];
        while ($note->amends_id !== null && ($previous = $byId->get($note->amends_id)) !== null) {
            $versions[] = $previous;
            $note = $previous;
        }

        return $versions;
    }

    public function add(RecordClinicalNote $record): void
    {
        $this->authorize('create', [ClinicalNote::class, $this->patient]);
        $this->validate(['body' => ['required', 'string', 'max:10000']], [], ['body' => __('note')]);

        $record->handle($this->patient, $this->body, $this->actor());

        $this->body = '';
        unset($this->notes, $this->currentNotes);
        Flux::toast(variant: 'success', text: __('Note added.'));
    }

    public function startAmend(int $id): void
    {
        $note = $this->ownNote($id);
        $this->authorize('amend', $note);

        $this->amendingId = $note->id;
        $this->amendment = $note->body;
        $this->resetValidation();
    }

    public function cancelAmend(): void
    {
        $this->amendingId = null;
        $this->amendment = '';
    }

    public function saveAmendment(RecordClinicalNote $record): void
    {
        abort_if($this->amendingId === null, 422);
        $note = $this->ownNote($this->amendingId);
        $this->authorize('amend', $note);

        $this->validate(['amendment' => ['required', 'string', 'max:10000', function (string $attribute, mixed $value, \Closure $fail) use ($note) {
            if (trim((string) $value) === trim($note->body)) {
                $fail(__('The correction is the same as the original note.'));
            }
        }]], [], ['amendment' => __('corrected note')]);

        $record->handle($this->patient, $this->amendment, $this->actor(), $note);

        $this->cancelAmend();
        unset($this->notes, $this->currentNotes);
        Flux::toast(variant: 'success', text: __('Correction saved. The original stays in the record.'));
    }

    public function toggleVersions(int $id): void
    {
        $this->ownNote($id);
        $this->expanded = in_array($id, $this->expanded, true)
            ? array_values(array_diff($this->expanded, [$id]))
            : [...$this->expanded, $id];
    }

    /**
     * A note of this patient (never another patient's, whatever id the browser sends).
     */
    private function ownNote(int $id): ClinicalNote
    {
        return $this->patient->clinicalNotes()->findOrFail($id);
    }

    private function actor(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}; ?>

<section @class(['w-full', 'max-w-4xl' => ! $embedded])>
    @unless ($embedded)
        <x-ui.page-header :title="__('Clinical notes')" :description="$patient->full_name.' · '.$patient->patient_number">
            <x-slot:actions>
                <flux:button :href="route('clinic.patients.show', $patient)" wire:navigate>{{ __('Open patient record') }}</flux:button>
            </x-slot:actions>
        </x-ui.page-header>
    @endunless

    @can('create', [\App\Models\ClinicalNote::class, $patient])
        <flux:card class="mb-6">
            <form wire:submit="add" class="space-y-3">
                <flux:textarea wire:model="body" :label="__('New note')" rows="4" dir="auto" />
                <flux:text class="text-xs">{{ __('Notes cannot be edited or deleted. Mistakes are fixed with a correction, and the original stays in the record.') }}</flux:text>
                <flux:button type="submit" variant="primary">{{ __('Add note') }}</flux:button>
            </form>
        </flux:card>
    @endcan

    @if ($this->currentNotes->isEmpty())
        <x-ui.empty-state :title="__('No clinical notes yet')" />
    @else
        <ol class="space-y-4">
            @foreach ($this->currentNotes as $note)
                @php $versions = $this->earlierVersions($note); @endphp
                <li wire:key="note-{{ $note->id }}">
                    <flux:card class="space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                            <span>
                                <span class="font-medium">{{ $note->author?->name }}</span>
                                <span class="text-zinc-500">· {{ $note->created_at?->format('Y-m-d H:i') }}</span>
                                @if ($versions !== [])
                                    <flux:badge size="sm" color="amber" class="ms-1">{{ __('Corrected') }}</flux:badge>
                                @endif
                            </span>
                            @can('amend', $note)
                                @if ($amendingId !== $note->id)
                                    <flux:button size="sm" variant="ghost" wire:click="startAmend({{ $note->id }})">{{ __('Correct') }}</flux:button>
                                @endif
                            @endcan
                        </div>

                        @if ($amendingId === $note->id)
                            <form wire:submit="saveAmendment" class="space-y-3">
                                <flux:textarea wire:model="amendment" :label="__('Corrected note')" rows="4" dir="auto" />
                                <div class="flex gap-2">
                                    <flux:button type="submit" variant="primary">{{ __('Save correction') }}</flux:button>
                                    <flux:button wire:click="cancelAmend">{{ __('Cancel') }}</flux:button>
                                </div>
                            </form>
                        @else
                            <p class="whitespace-pre-line text-sm" dir="auto">{{ $note->body }}</p>
                        @endif

                        @if ($versions !== [])
                            <button type="button" wire:click="toggleVersions({{ $note->id }})" class="text-xs underline">
                                {{ in_array($note->id, $expanded, true) ? __('Hide earlier versions') : trans_choice('Show :count earlier version|Show :count earlier versions', count($versions)) }}
                            </button>
                            @if (in_array($note->id, $expanded, true))
                                <ol class="space-y-2 border-s-2 border-zinc-200 ps-3 dark:border-zinc-700">
                                    @foreach ($versions as $version)
                                        <li class="text-xs text-zinc-600 dark:text-zinc-400">
                                            <span class="font-medium">{{ $version->author?->name }} · {{ $version->created_at?->format('Y-m-d H:i') }}</span>
                                            <p class="mt-1 whitespace-pre-line line-through decoration-zinc-400" dir="auto">{{ $version->body }}</p>
                                        </li>
                                    @endforeach
                                </ol>
                            @endif
                        @endif
                    </flux:card>
                </li>
            @endforeach
        </ol>
    @endif
</section>
