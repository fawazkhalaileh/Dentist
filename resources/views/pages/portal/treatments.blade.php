<?php

use App\Enums\TreatmentStatus;
use App\Livewire\PortalComponent;
use App\Models\TreatmentItem;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;

new #[Title('My treatments')] class extends PortalComponent {
    /**
     * The signed-in patient's treatments: procedure, tooth, status and dates.
     * Clinical notes and cancellation reasons are staff-only and not loaded.
     *
     * @return Collection<int, TreatmentItem>
     */
    #[Computed]
    public function items(): Collection
    {
        return $this->patient()?->treatmentItems()
            ->with(['procedure', 'surfaces', 'dentist:id,name'])
            ->where('status', '!=', TreatmentStatus::Cancelled->value)
            ->orderByRaw("FIELD(status, 'in_progress', 'planned', 'completed')")
            ->latest('id')
            ->get() ?? new Collection;
    }
}; ?>

<section class="w-full max-w-3xl">
    <x-ui.page-header :title="__('My treatments')" :description="__('Treatments planned or carried out at the clinic. Ask your dentist if you have questions.')" />

    @if (! auth()->user()->patient)
        <x-ui.empty-state :title="__('Your account is not linked to a patient record yet')" :description="__('The clinic links your account to your patient record after verifying your identity. Contact reception if you are already a patient.')" />
    @elseif ($this->items->isEmpty())
        <x-ui.empty-state :title="__('No treatments recorded')" />
    @else
        <ul class="divide-y divide-zinc-200 rounded-lg border border-zinc-200 text-sm dark:divide-zinc-700 dark:border-zinc-700">
            @foreach ($this->items as $item)
                <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-3" wire:key="t-{{ $item->id }}">
                    <span>
                        <span class="font-medium">{{ $item->procedure->localizedName() }}</span>
                        @if ($item->tooth_number)
                            <span class="block text-xs text-zinc-500">{{ __('Tooth :number', ['number' => $item->tooth_number]) }} · {{ __(\App\Support\Fdi::name($item->tooth_number)) }}@if ($item->surfaces->isNotEmpty()) ({{ $item->surfaceCodes() }})@endif</span>
                        @endif
                        <span class="block text-xs text-zinc-500">
                            {{ $item->dentist?->name }}
                            @if ($item->completed_at) · {{ __('Completed :date', ['date' => \App\Support\ClinicTime::local($item->completed_at)->format('Y-m-d')]) }}
                            @elseif ($item->planned_for) · {{ __('Planned for :date', ['date' => $item->planned_for->format('Y-m-d')]) }} @endif
                        </span>
                    </span>
                    <x-ui.status-badge :status="$item->status" />
                </li>
            @endforeach
        </ul>
    @endif
</section>
