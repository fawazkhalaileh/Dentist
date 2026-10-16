@props(['title'])
{{-- A plain A4 page for printing invoices and estimates. --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ \App\Support\Locales::dir() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} · {{ \App\Support\ClinicDetails::name() }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @page { size: A4; margin: 16mm; }
        @media print { .no-print { display: none !important; } body { background: #fff !important; } }
    </style>
</head>
<body class="bg-zinc-100 text-zinc-900 print:bg-white">
    <div class="no-print mx-auto flex max-w-3xl justify-end gap-2 px-4 pt-4">
        <button type="button" onclick="window.print()" class="rounded-lg bg-brand px-4 py-2 text-sm font-medium text-brand-contrast">{{ __('Print') }}</button>
    </div>
    <main class="mx-auto my-4 max-w-3xl bg-white p-10 shadow-sm print:my-0 print:p-0 print:shadow-none">
        <header class="mb-8 flex items-start justify-between gap-6 border-b border-zinc-200 pb-6">
            <div class="flex items-center gap-3">
                <x-app-logo-icon class="size-12 shrink-0 text-brand" />
                <div>
                    <p class="text-lg font-semibold">{{ \App\Support\ClinicDetails::name() }}</p>
                    <p class="whitespace-pre-line text-sm text-zinc-600">{{ \App\Support\ClinicDetails::get('address') }}</p>
                    @if ($phone = \App\Support\ClinicDetails::get('phone')) <p class="text-sm text-zinc-600" dir="ltr">{{ $phone }}</p> @endif
                </div>
            </div>
            <div class="text-end">
                <p class="text-2xl font-semibold">{{ $title }}</p>
                {{ $meta ?? '' }}
            </div>
        </header>
        {{ $slot }}
    </main>
</body>
</html>
