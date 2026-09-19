<div wire:poll.15s="updateCount" class="relative inline-flex items-center justify-center">
    <a href="{{ route('tenant.notifications') }}" class="relative flex size-10 items-center justify-center text-[#44474e] transition hover:text-[#031636]" aria-label="Notifikasi">
        <span class="material-symbols-outlined">notifications</span>
        @if ($unreadCount > 0)
            <span class="absolute right-0 top-0 min-w-5 rounded-full bg-red-600 px-1 text-center text-xs font-bold leading-5 text-white shadow-xs animate-in fade-in">
                {{ min($unreadCount, 99) }}
            </span>
        @endif
    </a>
</div>
