@php
    $money = fn (?int $v) => \App\Support\Money::format($v);
    $total = (int) $items->sum('price');
@endphp
<x-clinic.billing.print-layout :title="__('Treatment estimate')">
    <x-slot:meta>
        <p class="text-sm text-zinc-600">{{ __('Date') }}: {{ \App\Support\ClinicTime::today() }}</p>
        @if ($plan) <p class="text-sm">{{ $plan->title }}</p> @endif
    </x-slot:meta>

    <section class="mb-8 text-sm">
        <p class="text-zinc-500">{{ __('Patient') }}</p>
        <p class="font-semibold">{{ $patient->full_name }}</p>
        <p class="text-zinc-600" dir="ltr">{{ $patient->patient_number }}</p>
    </section>

    @if ($items->isEmpty())
        <p class="text-sm text-zinc-600">{{ __('There is no planned treatment to estimate.') }}</p>
    @else
        <table class="w-full text-sm">
            <thead class="border-b border-zinc-300">
                <tr>
                    <th class="py-2 text-start font-medium">{{ __('Treatment') }}</th>
                    <th class="py-2 text-start font-medium">{{ __('Tooth') }}</th>
                    <th class="py-2 text-end font-medium">{{ __('Price') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200">
                @foreach ($items as $item)
                    <tr>
                        <td class="py-2">{{ $item->procedure->localizedName() }}</td>
                        <td class="py-2" dir="ltr">{{ $item->tooth_number ? $item->tooth_number.' '.$item->surfaceCodes() : __('Whole mouth') }}</td>
                        <td class="py-2 text-end" dir="ltr">{{ $item->price !== null ? $money($item->price) : __('To be confirmed') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="border-t-2 border-zinc-300">
                <tr><td colspan="2" class="pt-3 text-end font-medium">{{ __('Estimated total') }}</td><td class="pt-3 text-end font-semibold" dir="ltr">{{ $money($total) }}</td></tr>
            </tfoot>
        </table>
        <p class="mt-6 text-xs text-zinc-500">{{ __('This is an estimate based on the current treatment plan. The final cost may change if the treatment needs to change.') }}</p>
    @endif
</x-clinic.billing.print-layout>
