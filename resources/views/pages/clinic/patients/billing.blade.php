<?php

use App\Actions\Billing\CreateInvoice;
use App\Actions\Billing\RecordPayment;
use App\Actions\Billing\VoidBillingRecord;
use App\Enums\PaymentMethod;
use App\Enums\Permission;
use App\Enums\TreatmentStatus;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\TreatmentItem;
use App\Models\User;
use App\Support\ClinicTime;
use App\Support\Money;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Billing')] class extends Component {
    public Patient $patient;

    #[Locked]
    public bool $embedded = false;

    public bool $creatingInvoice = false;

    /** Treatment item ids ticked for the new invoice. @var list<int|string> */
    public array $selectedItems = [];

    /** Price per ticked treatment, as typed. @var array<int|string, string> */
    public array $itemPrices = [];

    /** @var list<array{description: string, quantity: int|string, price: string}> */
    public array $extraLines = [];

    public string $issuedOn = '';

    public string $invoiceNotes = '';

    public bool $recordingPayment = false;

    /** @var array{amount: string, method: string, paid_on: string, invoice_id: string, reference: string, notes: string} */
    public array $payment = ['amount' => '', 'method' => 'cash', 'paid_on' => '', 'invoice_id' => '', 'reference' => '', 'notes' => ''];

    #[Locked]
    public ?string $voiding = null; // "invoice:12" or "payment:7"

    public string $voidReason = '';

    public function mount(Patient $patient, bool $embedded = false): void
    {
        $this->authorize('view', $patient);
        Gate::authorize(Permission::BillingManage->value);
        $this->patient = $patient;
        $this->embedded = $embedded;
    }

    /**
     * @return array{invoiced: int, paid: int, balance: int}
     */
    #[Computed]
    public function summary(): array
    {
        return $this->patient->billingSummary();
    }

    /**
     * @return Collection<int, Invoice>
     */
    #[Computed]
    public function invoices(): Collection
    {
        return $this->patient->invoices()->with(['lines', 'payments', 'creator:id,name'])->latest('issued_on')->latest('id')->get();
    }

    /**
     * @return Collection<int, Payment>
     */
    #[Computed]
    public function payments(): Collection
    {
        return $this->patient->payments()->with(['invoice:id,number', 'receiver:id,name'])->latest('paid_on')->latest('id')->get();
    }

    /**
     * Completed or started treatments that are not yet on a valid invoice.
     *
     * @return Collection<int, TreatmentItem>
     */
    #[Computed]
    public function billable(): Collection
    {
        return $this->patient->treatmentItems()->with(['procedure', 'surfaces'])
            ->whereIn('status', [TreatmentStatus::Completed->value, TreatmentStatus::InProgress->value])
            ->whereDoesntHave('invoiceLines', fn ($q) => $q->whereHas('invoice', fn ($i) => $i->whereNull('voided_at')))
            ->orderBy('completed_at')->get();
    }

    public function lineLabel(TreatmentItem $item): string
    {
        return $item->procedure->localizedName()
            .($item->tooth_number ? ' · '.__('tooth :number', ['number' => $item->tooth_number]).($item->surfaces->isNotEmpty() ? ' '.$item->surfaceCodes() : '') : '');
    }

    public function openInvoice(): void
    {
        $this->resetValidation();
        $completed = $this->billable->where('status', TreatmentStatus::Completed);
        $this->selectedItems = $completed->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->itemPrices = $this->billable->mapWithKeys(fn (TreatmentItem $i) => [$i->id => Money::plain($i->price)])->all();
        $this->extraLines = [];
        $this->issuedOn = ClinicTime::today();
        $this->invoiceNotes = '';
        $this->creatingInvoice = true;
    }

    public function addLine(): void
    {
        $this->extraLines[] = ['description' => '', 'quantity' => 1, 'price' => ''];
    }

    public function removeLine(int $index): void
    {
        unset($this->extraLines[$index]);
        $this->extraLines = array_values($this->extraLines);
    }

    /**
     * Running total of the invoice being prepared, for the preview.
     */
    public function draftTotal(): int
    {
        $total = 0;
        foreach ($this->selectedItems as $id) {
            $total += $this->safeAmount($this->itemPrices[$id] ?? '');
        }
        foreach ($this->extraLines as $line) {
            $total += max(1, (int) $line['quantity']) * $this->safeAmount($line['price']);
        }

        return $total;
    }

    private function safeAmount(string $typed): int
    {
        try {
            return Money::parse(Money::normalise($typed));
        } catch (\InvalidArgumentException) {
            return 0;
        }
    }

    public function createInvoice(CreateInvoice $create): void
    {
        Gate::authorize(Permission::BillingManage->value);
        $billableIds = $this->billable->pluck('id')->map(fn ($id) => (string) $id)->all();
        foreach ($this->itemPrices as $id => $price) {
            $this->itemPrices[$id] = Money::normalise($price);
        }
        foreach ($this->extraLines as $i => $line) {
            $this->extraLines[$i]['price'] = Money::normalise($line['price']);
        }

        $rules = [
            'selectedItems' => ['array'],
            'selectedItems.*' => [Rule::in($billableIds)],
            'extraLines' => ['array'],
            'extraLines.*.description' => ['required', 'string', 'max:255'],
            'extraLines.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'extraLines.*.price' => ['required', Money::rule()],
            'issuedOn' => ['required', 'date_format:Y-m-d'],
            'invoiceNotes' => ['nullable', 'string', 'max:1000'],
        ];
        foreach ($this->selectedItems as $id) {
            $rules["itemPrices.{$id}"] = ['required', Money::rule()];
        }
        $this->validate($rules, ['itemPrices.*.required' => __('Enter a price.'), 'itemPrices.*.regex' => __('Enter an amount like 25 or 25.500.'), 'extraLines.*.price.regex' => __('Enter an amount like 25 or 25.500.')]);

        $items = $this->billable->keyBy('id');
        $lines = [];
        foreach ($this->selectedItems as $id) {
            $item = $items->get((int) $id);
            $lines[] = ['treatment_item_id' => (int) $id, 'description' => $this->lineLabel($item), 'quantity' => 1, 'unit_price' => Money::parse($this->itemPrices[$id])];
        }
        foreach ($this->extraLines as $line) {
            $lines[] = ['treatment_item_id' => null, 'description' => $line['description'], 'quantity' => (int) $line['quantity'], 'unit_price' => Money::parse($line['price'])];
        }

        try {
            $invoice = $create->handle($this->patient, $lines, $this->issuedOn, trim($this->invoiceNotes) ?: null, $this->actor());
        } catch (ValidationException $e) {
            $this->addError('lines', collect($e->errors())->flatten()->first());

            return;
        }

        $this->creatingInvoice = false;
        unset($this->summary, $this->invoices, $this->billable);
        Flux::toast(variant: 'success', text: __('Invoice :number issued.', ['number' => $invoice->number]));
    }

    public function openPayment(?int $invoiceId = null): void
    {
        $this->resetValidation();
        $invoice = $invoiceId ? $this->invoices->firstWhere('id', $invoiceId) : null;
        $owed = $invoice ? max(0, $invoice->total - $invoice->paidAmount()) : max(0, $this->summary['balance']);
        $this->payment = [
            'amount' => $owed > 0 ? Money::plain($owed) : '',
            'method' => PaymentMethod::Cash->value,
            'paid_on' => ClinicTime::today(),
            'invoice_id' => $invoice ? (string) $invoice->id : '',
            'reference' => '',
            'notes' => '',
        ];
        $this->recordingPayment = true;
    }

    public function recordPayment(RecordPayment $record): void
    {
        Gate::authorize(Permission::BillingManage->value);
        $this->payment['amount'] = Money::normalise($this->payment['amount']);
        $validInvoiceIds = $this->invoices->reject->isVoid()->pluck('id')->map(fn ($id) => (string) $id)->all();

        $this->validate([
            'payment.amount' => ['required', Money::rule(), 'not_in:0,0.0,0.00,0.000'],
            'payment.method' => ['required', Rule::enum(PaymentMethod::class)],
            'payment.paid_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.ClinicTime::now()->addDay()->format('Y-m-d')],
            'payment.invoice_id' => ['nullable', Rule::in(['', ...$validInvoiceIds])],
            'payment.reference' => ['nullable', 'string', 'max:100'],
            'payment.notes' => ['nullable', 'string', 'max:500'],
        ], ['payment.amount.regex' => __('Enter an amount like 25 or 25.500.'), 'payment.amount.not_in' => __('Enter an amount above zero.')]);

        $record->handle(
            $this->patient,
            Money::parse($this->payment['amount']),
            PaymentMethod::from($this->payment['method']),
            $this->payment['paid_on'],
            $this->payment['invoice_id'] !== '' ? $this->invoices->firstWhere('id', (int) $this->payment['invoice_id']) : null,
            trim($this->payment['reference']) ?: null,
            trim($this->payment['notes']) ?: null,
            $this->actor(),
        );

        $this->recordingPayment = false;
        unset($this->summary, $this->invoices, $this->payments);
        Flux::toast(variant: 'success', text: __('Payment recorded.'));
    }

    public function startVoid(string $type, int $id): void
    {
        abort_unless(in_array($type, ['invoice', 'payment'], true), 404);
        $this->record($type, $id);
        $this->voiding = "{$type}:{$id}";
        $this->voidReason = '';
        $this->resetValidation();
    }

    public function cancelVoid(): void
    {
        $this->voiding = null;
    }

    public function confirmVoid(VoidBillingRecord $void): void
    {
        Gate::authorize(Permission::BillingManage->value);
        abort_if($this->voiding === null, 422);
        [$type, $id] = explode(':', $this->voiding);
        $this->validate(['voidReason' => ['required', 'string', 'max:255']], [], ['voidReason' => __('reason')]);

        $void->handle($this->record($type, (int) $id), $this->voidReason, $this->actor());

        $this->voiding = null;
        unset($this->summary, $this->invoices, $this->payments, $this->billable);
        Flux::toast(variant: 'success', text: __('Voided. It stays in the list but no longer counts.'));
    }

    /**
     * An invoice or payment of this patient (never another patient's).
     */
    private function record(string $type, int $id): Invoice|Payment
    {
        return $type === 'invoice'
            ? $this->patient->invoices()->findOrFail($id)
            : $this->patient->payments()->findOrFail($id);
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
        <x-ui.page-header :title="__('Billing')" :description="$patient->full_name.' · '.$patient->patient_number" />
    @endunless

    @php
        $summary = $this->summary;
        $money = fn (?int $v) => \App\Support\Money::format($v);
    @endphp

    {{-- Balance --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <p class="text-sm text-zinc-500">{{ __('Invoiced') }}</p>
            <p class="mt-1 text-2xl font-semibold" dir="ltr">{{ $money($summary['invoiced']) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <p class="text-sm text-zinc-500">{{ __('Paid') }}</p>
            <p class="mt-1 text-2xl font-semibold" dir="ltr">{{ $money($summary['paid']) }}</p>
        </div>
        <div @class(['rounded-xl border p-4', 'border-amber-300 bg-amber-50 dark:border-amber-700 dark:bg-amber-950' => $summary['balance'] > 0, 'border-zinc-200 dark:border-zinc-700' => $summary['balance'] <= 0])>
            <p class="text-sm text-zinc-500">{{ $summary['balance'] < 0 ? __('Credit') : __('Balance due') }}</p>
            <p class="mt-1 text-2xl font-semibold" dir="ltr">{{ $money(abs($summary['balance'])) }}</p>
        </div>
    </div>

    <div class="mb-6 flex flex-wrap gap-2">
        <flux:button variant="primary" icon="document-plus" wire:click="openInvoice">{{ __('New invoice') }}</flux:button>
        <flux:button icon="banknotes" wire:click="openPayment">{{ __('Record payment') }}</flux:button>
        @if ($this->billable->where('status', \App\Enums\TreatmentStatus::Completed)->isNotEmpty())
            <span class="self-center text-sm text-amber-700 dark:text-amber-400">{{ trans_choice(':count completed treatment is not invoiced yet.|:count completed treatments are not invoiced yet.', $this->billable->where('status', \App\Enums\TreatmentStatus::Completed)->count()) }}</span>
        @endif
    </div>

    {{-- Invoices --}}
    <h3 class="mb-2 text-lg font-semibold">{{ __('Invoices') }}</h3>
    @if ($this->invoices->isEmpty())
        <p class="mb-6 text-sm text-zinc-500">{{ __('No invoices yet.') }}</p>
    @else
        <div class="mb-6 overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 text-zinc-600 dark:bg-zinc-900 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-2 text-start font-medium">{{ __('Invoice') }}</th>
                        <th class="px-4 py-2 text-start font-medium">{{ __('Date') }}</th>
                        <th class="px-4 py-2 text-end font-medium">{{ __('Total') }}</th>
                        <th class="px-4 py-2 text-end font-medium">{{ __('Paid') }}</th>
                        <th class="px-4 py-2 text-start font-medium">{{ __('Status') }}</th>
                        <th class="px-4 py-2"><span class="sr-only">{{ __('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($this->invoices as $invoice)
                        @php $paid = $invoice->payments->whereNull('voided_at')->sum('amount'); @endphp
                        <tr wire:key="inv-{{ $invoice->id }}" @class(['text-zinc-400' => $invoice->isVoid()])>
                            <td class="px-4 py-2 font-medium" dir="ltr">{{ $invoice->number }}</td>
                            <td class="px-4 py-2">{{ $invoice->issued_on->format('Y-m-d') }}</td>
                            <td @class(['px-4 py-2 text-end', 'line-through' => $invoice->isVoid()]) dir="ltr">{{ $money($invoice->total) }}</td>
                            <td class="px-4 py-2 text-end" dir="ltr">{{ $money((int) $paid) }}</td>
                            <td class="px-4 py-2">
                                @if ($invoice->isVoid())
                                    <flux:badge size="sm" color="zinc">{{ __('Void') }}</flux:badge>
                                    <span class="block text-xs">{{ $invoice->void_reason }}</span>
                                @elseif ($paid >= $invoice->total)
                                    <flux:badge size="sm" color="green">{{ __('Paid') }}</flux:badge>
                                @elseif ($paid > 0)
                                    <flux:badge size="sm" color="amber">{{ __('Partly paid') }}</flux:badge>
                                @else
                                    <flux:badge size="sm" color="red">{{ __('Unpaid') }}</flux:badge>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-end">
                                <div class="flex justify-end gap-1">
                                    <flux:button size="xs" icon="printer" :href="route('clinic.patients.invoices.print', ['patient' => $patient, 'invoice' => $invoice->id])" target="_blank">{{ __('Print') }}</flux:button>
                                    @unless ($invoice->isVoid())
                                        @if ($paid < $invoice->total)
                                            <flux:button size="xs" wire:click="openPayment({{ $invoice->id }})">{{ __('Pay') }}</flux:button>
                                        @endif
                                        <flux:button size="xs" variant="ghost" wire:click="startVoid('invoice', {{ $invoice->id }})">{{ __('Void') }}</flux:button>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                        @if ($voiding === 'invoice:'.$invoice->id)
                            <tr>
                                <td colspan="6" class="bg-zinc-50 px-4 py-3 dark:bg-zinc-900">
                                    <form wire:submit="confirmVoid" class="flex flex-wrap items-end gap-2">
                                        <flux:input size="sm" wire:model="voidReason" :label="__('Reason for voiding :number', ['number' => $invoice->number])" class="min-w-64 flex-1" />
                                        <flux:button size="sm" type="submit" variant="danger">{{ __('Void invoice') }}</flux:button>
                                        <flux:button size="sm" wire:click="cancelVoid">{{ __('Keep it') }}</flux:button>
                                    </form>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Payments --}}
    <h3 class="mb-2 text-lg font-semibold">{{ __('Payments') }}</h3>
    @if ($this->payments->isEmpty())
        <p class="text-sm text-zinc-500">{{ __('No payments yet.') }}</p>
    @else
        <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 text-zinc-600 dark:bg-zinc-900 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-2 text-start font-medium">{{ __('Date') }}</th>
                        <th class="px-4 py-2 text-end font-medium">{{ __('Amount') }}</th>
                        <th class="px-4 py-2 text-start font-medium">{{ __('Method') }}</th>
                        <th class="px-4 py-2 text-start font-medium">{{ __('For') }}</th>
                        <th class="px-4 py-2 text-start font-medium">{{ __('Received by') }}</th>
                        <th class="px-4 py-2"><span class="sr-only">{{ __('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($this->payments as $payment)
                        <tr wire:key="pay-{{ $payment->id }}" @class(['text-zinc-400' => $payment->isVoid()])>
                            <td class="px-4 py-2">{{ $payment->paid_on->format('Y-m-d') }}</td>
                            <td @class(['px-4 py-2 text-end font-medium', 'line-through' => $payment->isVoid()]) dir="ltr">{{ $money($payment->amount) }}</td>
                            <td class="px-4 py-2">{{ $payment->method->label() }}@if ($payment->reference) <span class="block text-xs text-zinc-500" dir="ltr">{{ $payment->reference }}</span>@endif</td>
                            <td class="px-4 py-2" dir="ltr">{{ $payment->invoice?->number ?? __('Account') }}</td>
                            <td class="px-4 py-2">{{ $payment->receiver?->name }}@if ($payment->isVoid()) <span class="block text-xs">{{ __('Void') }}: {{ $payment->void_reason }}</span>@endif</td>
                            <td class="px-4 py-2 text-end">
                                @unless ($payment->isVoid())
                                    <flux:button size="xs" variant="ghost" wire:click="startVoid('payment', {{ $payment->id }})">{{ __('Void') }}</flux:button>
                                @endunless
                            </td>
                        </tr>
                        @if ($voiding === 'payment:'.$payment->id)
                            <tr>
                                <td colspan="6" class="bg-zinc-50 px-4 py-3 dark:bg-zinc-900">
                                    <form wire:submit="confirmVoid" class="flex flex-wrap items-end gap-2">
                                        <flux:input size="sm" wire:model="voidReason" :label="__('Reason for voiding this payment')" class="min-w-64 flex-1" />
                                        <flux:button size="sm" type="submit" variant="danger">{{ __('Void payment') }}</flux:button>
                                        <flux:button size="sm" wire:click="cancelVoid">{{ __('Keep it') }}</flux:button>
                                    </form>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- New invoice --}}
    <flux:modal wire:model.self="creatingInvoice" variant="flyout" class="w-full max-w-xl">
        <form wire:submit="createInvoice" class="space-y-5">
            <flux:heading size="lg">{{ __('New invoice') }}</flux:heading>

            <div>
                <p class="mb-2 text-sm font-medium">{{ __('Treatments to bill') }}</p>
                @if ($this->billable->isEmpty())
                    <p class="text-sm text-zinc-500">{{ __('There are no completed treatments waiting to be invoiced. You can still add lines below.') }}</p>
                @else
                    <ul class="space-y-2">
                        @foreach ($this->billable as $item)
                            <li class="flex items-center gap-3" wire:key="bill-{{ $item->id }}">
                                <input type="checkbox" wire:model.live="selectedItems" value="{{ $item->id }}" id="bill-{{ $item->id }}" class="size-4 rounded border-zinc-300">
                                <label for="bill-{{ $item->id }}" class="min-w-0 flex-1 text-sm">
                                    {{ $this->lineLabel($item) }}
                                    @if ($item->status === \App\Enums\TreatmentStatus::InProgress) <span class="text-xs text-amber-600">({{ __('in progress') }})</span> @endif
                                </label>
                                <input type="text" inputmode="decimal" wire:model.live.debounce.400ms="itemPrices.{{ $item->id }}" aria-label="{{ __('Price') }}" class="h-8 w-28 rounded-md border border-zinc-200 px-2 text-end text-sm dark:border-zinc-600 dark:bg-zinc-800" dir="ltr">
                            </li>
                            @error('itemPrices.'.$item->id) <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="space-y-2">
                <p class="text-sm font-medium">{{ __('Other lines') }}</p>
                @foreach ($extraLines as $i => $line)
                    <div class="flex items-start gap-2" wire:key="extra-{{ $i }}">
                        <flux:input size="sm" wire:model="extraLines.{{ $i }}.description" :placeholder="__('Description')" class="flex-1" />
                        <input type="number" min="1" wire:model.live="extraLines.{{ $i }}.quantity" aria-label="{{ __('Quantity') }}" class="h-8 w-16 rounded-md border border-zinc-200 px-2 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                        <input type="text" inputmode="decimal" wire:model.live.debounce.400ms="extraLines.{{ $i }}.price" placeholder="{{ __('Price') }}" aria-label="{{ __('Price') }}" class="h-8 w-28 rounded-md border border-zinc-200 px-2 text-end text-sm dark:border-zinc-600 dark:bg-zinc-800" dir="ltr">
                        <flux:button size="xs" variant="ghost" icon="x-mark" wire:click="removeLine({{ $i }})" :aria-label="__('Remove')" />
                    </div>
                    @error('extraLines.'.$i.'.description') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    @error('extraLines.'.$i.'.price') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                @endforeach
                <flux:button size="xs" variant="ghost" icon="plus" wire:click="addLine">{{ __('Add a line') }}</flux:button>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input type="date" wire:model="issuedOn" :label="__('Invoice date')" />
                <div class="self-end text-end">
                    <p class="text-sm text-zinc-500">{{ __('Total') }}</p>
                    <p class="text-2xl font-semibold" dir="ltr">{{ $money($this->draftTotal()) }}</p>
                </div>
            </div>
            <flux:textarea wire:model="invoiceNotes" :label="__('Notes on the invoice')" rows="2" />
            <flux:error name="lines" />
            <flux:text class="text-xs">{{ __('An invoice cannot be changed after it is issued. If something is wrong, void it and issue a new one.') }}</flux:text>

            <div class="flex gap-2">
                <flux:button type="submit" variant="primary">{{ __('Issue invoice') }}</flux:button>
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
            </div>
        </form>
    </flux:modal>

    {{-- Record payment --}}
    <flux:modal wire:model.self="recordingPayment" variant="flyout" class="w-full max-w-md">
        <form wire:submit="recordPayment" class="space-y-5">
            <flux:heading size="lg">{{ __('Record payment') }}</flux:heading>
            <flux:input wire:model="payment.amount" inputmode="decimal" :label="__('Amount (:currency)', ['currency' => config('clinic.currency.code')])" dir="ltr" />
            <flux:select wire:model="payment.method" :label="__('Method')">
                @foreach (\App\Enums\PaymentMethod::cases() as $method)
                    <flux:select.option value="{{ $method->value }}">{{ $method->label() }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input type="date" wire:model="payment.paid_on" :label="__('Date')" />
            <flux:select wire:model="payment.invoice_id" :label="__('For invoice')">
                <flux:select.option value="">{{ __('Not linked (on account)') }}</flux:select.option>
                @foreach ($this->invoices->reject->isVoid() as $invoice)
                    <flux:select.option value="{{ $invoice->id }}">{{ $invoice->number }} · {{ $money($invoice->total) }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="payment.reference" :label="__('Reference')" :description="__('Optional, e.g. card slip or transfer number.')" dir="ltr" />
            <flux:textarea wire:model="payment.notes" :label="__('Notes')" rows="2" />
            <div class="flex gap-2">
                <flux:button type="submit" variant="primary">{{ __('Record payment') }}</flux:button>
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
            </div>
        </form>
    </flux:modal>
</section>
