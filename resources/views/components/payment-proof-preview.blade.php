@props(['payment'])

@php
    $extension = strtolower(pathinfo((string) $payment->proof, PATHINFO_EXTENSION));
    $previewUrl = route('tenant.payments.proof', $payment);
    $downloadUrl = route('tenant.payments.proof', ['payment' => $payment, 'download' => 1]);
    $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true);
@endphp

<div {{ $attributes->class(['space-y-4']) }}>
    <div class="flex min-h-72 items-center justify-center overflow-hidden rounded-xl bg-gray-100 dark:bg-gray-900 sm:min-h-[32rem]">
        @if ($isImage)
            <img src="{{ $previewUrl }}" alt="Bukti pembayaran {{ $payment->payment_number }}" class="max-h-[70vh] w-full object-contain" loading="lazy">
        @elseif ($extension === 'pdf')
            <iframe src="{{ $previewUrl }}" title="Bukti pembayaran {{ $payment->payment_number }}" class="h-[70vh] min-h-[32rem] w-full border-0"></iframe>
        @else
            <div class="p-8 text-center text-sm text-gray-600 dark:text-gray-300">File ini tidak dapat dipratinjau. Silakan unduh untuk melihatnya.</div>
        @endif
    </div>
    <div class="flex flex-wrap justify-end gap-2">
        <a href="{{ $previewUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
            Buka di Tab Baru
        </a>
        <a href="{{ $downloadUrl }}" class="inline-flex items-center gap-2 rounded-lg bg-[#031636] px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90">
            Unduh
        </a>
    </div>
</div>
