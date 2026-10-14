@php
    $money = fn (?int $v) => \App\Support\Money::format($v);
    $paid = (int) $invoice->payments->sum('amount');
@endphp
<x-clinic.billing.print-layout :title="__('Invoice')">
    <x-slot:meta>
        <p class="text-sm" dir="ltr">{{ $invoice->number }}</p>
        <p class="text-sm text-zinc-600">{{ __('Date') }}: {{ $invoice->issued_on->format('Y-m-d') }}</p>
        @if ($invoice->isVoid()) <p class="mt-1 font-semibold text-red-600">{{ __('VOID') }}: {{ $invoice->void_reason }}</p> @endif
    </x-slot:meta>

    <section class="mb-8 text-sm">
        <p class="text-zinc-500">{{ __('Patient') }}</p>
        <p class="font-semibold">{{ $patient->full_name }}</p>
        <p class="text-zinc-600" dir="ltr">{{ $patient->patient_number }}@if ($patient->phone) · {{ $patient->phone }}@endif</p>
    </section>

    <table class="w-full text-sm">
        <thead class="border-b border-zinc-300">
            <tr>
                <th class="py-2 text-start font-medium">{{ __('Description') }}</th>
                <th class="py-2 text-end font-medium">{{ __('Qty') }}</th>
                <th class="py-2 text-end font-medium">{{ __('Price') }}</th>
                <th class="py-2 text-end font-medium">{{ __('Amount') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-200">
            @foreach ($invoice->lines as $line)
                <tr>
                    <td class="py-2">{{ $line->description }}</td>
                    <td class="py-2 text-end">{{ $line->quantity }}</td>
                    <td class="py-2 text-end" dir="ltr">{{ $money($line->unit_price) }}</td>
                    <td class="py-2 text-end" dir="ltr">{{ $money($line->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="border-t-2 border-zinc-300">
            <tr><td colspan="3" class="pt-3 text-end font-medium">{{ __('Total') }}</td><td class="pt-3 text-end font-semibold" dir="ltr">{{ $money($invoice->total) }}</td></tr>
            <tr><td colspan="3" class="text-end text-zinc-600">{{ __('Paid') }}</td><td class="text-end" dir="ltr">{{ $money($paid) }}</td></tr>
            <tr><td colspan="3" class="text-end font-medium">{{ __('Amount due') }}</td><td class="text-end font-semibold" dir="ltr">{{ $money(max(0, $invoice->total - $paid)) }}</td></tr>
        </tfoot>
    </table>

    @if ($invoice->payments->isNotEmpty())
        <section class="mt-8 text-sm">
            <p class="mb-1 font-medium">{{ __('Payments received') }}</p>
            <ul class="text-zinc-600">
                @foreach ($invoice->payments as $payment)
                    <li>{{ $payment->paid_on->format('Y-m-d') }} · {{ $payment->method->label() }} · <span dir="ltr">{{ $money($payment->amount) }}</span></li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($invoice->notes) <p class="mt-8 whitespace-pre-line text-sm" dir="auto">{{ $invoice->notes }}</p> @endif
    <p class="mt-12 text-center text-xs text-zinc-500">{{ __('Thank you for choosing :clinic.', ['clinic' => \App\Support\ClinicDetails::name()]) }}</p>
</x-clinic.billing.print-layout>
