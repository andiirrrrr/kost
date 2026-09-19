<x-filament-widgets::widget>
    <x-filament::section heading="Aktivitas Terbaru" description="Perubahan operasional yang tercatat di sistem.">
        <div class="divide-y divide-[var(--kost-border)]">
            @forelse ($activities as $activity)
                @php
                    $activityMeta = match ($activity->action) {
                        'tenant.created' => ['Penghuni baru ditambahkan', 'heroicon-o-user-plus'],
                        'tenant.updated' => ['Data penghuni diperbarui', 'heroicon-o-pencil-square'],
                        'tenant.checked_out' => ['Penghuni selesai menyewa', 'heroicon-o-arrow-right-start-on-rectangle'],
                        'maintenance.reported' => ['Keluhan perawatan dilaporkan', 'heroicon-o-wrench-screwdriver'],
                        'maintenance.updated' => ['Status perawatan diperbarui', 'heroicon-o-check-circle'],
                        default => [str($activity->action)->replace('.', ' ')->headline(), 'heroicon-o-clock'],
                    };
                    $roomNumber = $activity->properties['room_number'] ?? null;
                @endphp
                <div class="flex gap-3 py-4 first:pt-0 last:pb-0">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-[var(--kost-surface-muted)] text-[var(--kost-navy)]">
                        <x-filament::icon :icon="$activityMeta[1]" class="size-4" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-[var(--kost-text)]">{{ $activityMeta[0] }}</p>
                        <p class="mt-1 truncate text-xs text-[var(--kost-text-muted)]">
                            {{ $activity->user?->name ?? 'Sistem' }}{{ $roomNumber ? ' · Kamar '.$roomNumber : '' }}
                        </p>
                    </div>
                    <time class="shrink-0 text-xs text-[var(--kost-text-muted)]" datetime="{{ $activity->created_at->toIso8601String() }}">{{ $activity->created_at->locale('id')->diffForHumans(short: true) }}</time>
                </div>
            @empty
                <div class="py-10 text-center">
                    <x-filament::icon icon="heroicon-o-clock" class="mx-auto size-8 text-[var(--kost-text-muted)]" />
                    <p class="mt-3 text-sm text-[var(--kost-text-muted)]">Belum ada aktivitas tercatat.</p>
                </div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
