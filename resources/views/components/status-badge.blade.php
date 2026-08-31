@props(['status'])
@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $label = method_exists($status, 'label') ? $status->label() : ucfirst($value);
    $classes = match ($value) {
        'paid', 'verified' => 'bg-emerald-100 text-emerald-800',
        'pending' => 'bg-sky-100 text-sky-800',
        'overdue', 'rejected', 'failed' => 'bg-red-100 text-red-800',
        default => 'bg-amber-100 text-amber-800',
    };
@endphp
<span {{ $attributes->merge(['class' => "inline-flex rounded-full px-2.5 py-1 text-xs font-bold {$classes}"]) }}>{{ $label }}</span>
