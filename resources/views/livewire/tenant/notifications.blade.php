<div wire:poll.15s class="mx-auto flex max-w-3xl flex-col gap-6">
    <header class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">Notifikasi</h1>
            <p class="mt-2 text-[#44474e]">Informasi terbaru mengenai hunian dan pembayaran Anda.</p>
        </div>
        <button wire:click="markAllAsRead" class="shrink-0 pt-2 text-sm font-semibold text-[#031636] transition hover:underline">
            Tandai semua dibaca
        </button>
    </header>

    <div class="flex gap-2">
        <button wire:click="$set('filter', 'all')" class="rounded-full px-4 py-2 text-sm font-semibold transition {{ $filter === 'all' ? 'bg-[#031636] text-white shadow-xs' : 'bg-[#eeeeec] text-[#44474e] hover:bg-[#e7e2d8]' }}">
            Semua
        </button>
        <button wire:click="$set('filter', 'unread')" class="rounded-full px-4 py-2 text-sm font-semibold transition {{ $filter === 'unread' ? 'bg-[#031636] text-white shadow-xs' : 'bg-[#eeeeec] text-[#44474e] hover:bg-[#e7e2d8]' }}">
            Belum Dibaca
        </button>
    </div>

    <div class="flex flex-col">
        @forelse ($notifications as $notification)
            <article wire:key="notification-{{ $notification->id }}" class="flex gap-4 border-b border-[#c5c6cf]/30 py-5 transition {{ $notification->read_at ? 'opacity-60' : 'bg-white/40 rounded-xl px-3 my-1' }}">
                <span class="mt-2 size-2.5 shrink-0 rounded-full {{ $notification->read_at ? 'bg-transparent' : 'bg-[#031636]' }}"></span>
                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-3">
                        <h2 class="font-medium text-[#1a1c1b] {{ $notification->read_at ? '' : 'font-semibold text-[#031636]' }}">
                            {{ $notification->data['title'] ?? 'Notifikasi' }}
                        </h2>
                        @unless ($notification->read_at)
                            <button wire:click="markAsRead('{{ $notification->id }}')" class="shrink-0 text-xs font-semibold text-[#031636] transition hover:underline">
                                Tandai dibaca
                            </button>
                        @endunless
                    </div>

                    <p class="mt-1 text-sm leading-6 text-[#44474e]">
                        {{ $notification->data['message'] ?? '' }}
                    </p>

                    @if ($notification->data['resource_unavailable'] ?? false)
                        <p class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-[#75777f]">
                            <span class="material-symbols-outlined text-sm">link_off</span>
                            Tagihan ini telah dihapus oleh admin
                        </p>
                    @endif

                    <div class="mt-2 flex items-center justify-between gap-4">
                        <p class="text-xs font-semibold text-[#44474e]/50">
                            {{ $notification->created_at->locale('id')->diffForHumans() }}
                        </p>

                        @if ($notification->data['url'] ?? null)
                            <a href="{{ $notification->data['url'] }}" wire:click.prevent="openNotification('{{ $notification->id }}')" class="inline-flex items-center gap-1 text-xs font-semibold text-[#031636] transition hover:underline">
                                Buka detail
                                <span class="material-symbols-outlined text-sm">arrow_forward</span>
                            </a>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-xl bg-[#f4f4f2] px-6 py-14 text-center">
                <span class="material-symbols-outlined text-4xl text-[#75777f]">notifications_off</span>
                <p class="mt-3 font-medium">Tidak ada notifikasi.</p>
                <p class="mt-1 text-sm text-[#44474e]">Pembaruan baru akan muncul di sini.</p>
            </div>
        @endforelse
    </div>

    {{ $notifications->links() }}
</div>
