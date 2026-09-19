<x-filament-widgets::widget class="kost-daily-briefing">
    <div class="overflow-hidden rounded-2xl border border-[#dddcd7] bg-white shadow-[0_12px_35px_rgba(28,39,51,.06)]">
        <div class="grid xl:grid-cols-[1.55fr_.85fr]">
            <section class="p-6 sm:p-8">
                <div class="mb-6 flex items-end justify-between gap-4">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[.2em] text-[#9a7b4f]">Briefing hari ini</p>
                        <h2 class="mt-2 text-xl font-semibold tracking-tight text-[#1c2733]">Yang membutuhkan keputusan Anda</h2>
                    </div>
                    <span class="hidden text-xs font-medium text-[#7b828a] sm:block">Diperbarui {{ now()->format('H:i') }}</span>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($tasks as $task)
                        <a href="{{ $task['url'] }}" class="group flex items-center gap-4 rounded-xl border border-[#e5e3dd] bg-[#faf9f6] p-4 transition hover:-translate-y-0.5 hover:border-[#c9c4b8] hover:bg-white hover:shadow-sm">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-[#e9edf1] text-[#19324d]"><x-filament::icon :icon="$task['icon']" class="size-5" /></span>
                            <span class="min-w-0 flex-1"><span class="block text-2xl font-semibold tracking-tight text-[#1c2733]">{{ $task['count'] }}</span><span class="block truncate text-sm text-[#65707a]">{{ $task['label'] }}</span></span>
                            <x-filament::icon icon="heroicon-m-arrow-up-right" class="size-4 text-[#a4a8ac] transition group-hover:text-[#9a7b4f]" />
                        </a>
                    @endforeach
                </div>
            </section>

            <aside class="border-t border-[#e5e3dd] bg-[#f4f2ed] p-6 sm:p-8 xl:border-l xl:border-t-0">
                <p class="text-[11px] font-bold uppercase tracking-[.2em] text-[#7b7368]">Akses cepat</p>
                <div class="mt-4 grid gap-1">
                    @foreach ($quickActions as $action)
                        <a href="{{ $action['url'] }}" class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-[#49545e] transition hover:bg-white hover:text-[#19324d] hover:shadow-sm">
                            <x-filament::icon :icon="$action['icon']" class="size-5 text-[#9a7b4f]" />
                            <span class="flex-1">{{ $action['label'] }}</span>
                            <x-filament::icon icon="heroicon-m-chevron-right" class="size-4 text-[#a4a8ac] transition group-hover:translate-x-0.5 group-hover:text-[#19324d]" />
                        </a>
                    @endforeach
                </div>
            </aside>
        </div>
    </div>
</x-filament-widgets::widget>
