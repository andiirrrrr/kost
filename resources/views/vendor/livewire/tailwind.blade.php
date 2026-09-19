@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Navigasi Halaman" class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between pt-4 border-t border-[#c5c6cf]/30">
            {{-- Mobile Pagination --}}
            <div class="flex items-center justify-between gap-3 sm:hidden">
                @if ($paginator->onFirstPage())
                    <span class="inline-flex items-center gap-1.5 rounded-xl border border-[#c5c6cf]/30 bg-[#f4f4f2] px-4 py-2 text-xs font-semibold text-[#75777f]/60 cursor-not-allowed">
                        <span class="material-symbols-outlined text-base">arrow_back</span>
                        {{ __('pagination.previous') }}
                    </span>
                @else
                    <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" class="inline-flex items-center gap-1.5 rounded-xl border border-[#c5c6cf]/60 bg-white px-4 py-2 text-xs font-semibold text-[#031636] shadow-xs transition hover:bg-[#f4f4f2] active:scale-95">
                        <span class="material-symbols-outlined text-base">arrow_back</span>
                        {{ __('pagination.previous') }}
                    </button>
                @endif

                <span class="text-xs font-medium text-[#44474e]">
                    Halaman {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
                </span>

                @if ($paginator->hasMorePages())
                    <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" class="inline-flex items-center gap-1.5 rounded-xl border border-[#c5c6cf]/60 bg-white px-4 py-2 text-xs font-semibold text-[#031636] shadow-xs transition hover:bg-[#f4f4f2] active:scale-95">
                        {{ __('pagination.next') }}
                        <span class="material-symbols-outlined text-base">arrow_forward</span>
                    </button>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-xl border border-[#c5c6cf]/30 bg-[#f4f4f2] px-4 py-2 text-xs font-semibold text-[#75777f]/60 cursor-not-allowed">
                        {{ __('pagination.next') }}
                        <span class="material-symbols-outlined text-base">arrow_forward</span>
                    </span>
                @endif
            </div>

            {{-- Desktop Pagination --}}
            <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-[#44474e]">
                        Menampilkan
                        <span class="font-semibold text-[#1a1c1b]">{{ $paginator->firstItem() }}</span>
                        sampai
                        <span class="font-semibold text-[#1a1c1b]">{{ $paginator->lastItem() }}</span>
                        dari
                        <span class="font-semibold text-[#1a1c1b]">{{ $paginator->total() }}</span>
                        data
                    </p>
                </div>

                <div>
                    <div class="inline-flex items-center gap-1">
                        {{-- Previous Button --}}
                        @if ($paginator->onFirstPage())
                            <span class="inline-flex size-9 items-center justify-center rounded-lg border border-[#c5c6cf]/30 bg-[#f4f4f2] text-[#75777f]/50 cursor-not-allowed" aria-disabled="true">
                                <span class="material-symbols-outlined text-lg">chevron_left</span>
                            </span>
                        @else
                            <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" class="inline-flex size-9 items-center justify-center rounded-lg border border-[#c5c6cf]/60 bg-white text-[#031636] shadow-xs transition hover:bg-[#f4f4f2] active:scale-95" aria-label="{{ __('pagination.previous') }}">
                                <span class="material-symbols-outlined text-lg">chevron_left</span>
                            </button>
                        @endif

                        {{-- Pagination Elements --}}
                        @foreach ($elements as $element)
                            @if (is_string($element))
                                <span class="inline-flex size-9 items-center justify-center text-sm font-medium text-[#75777f]">{{ $element }}</span>
                            @endif

                            @if (is_array($element))
                                @foreach ($element as $page => $url)
                                    <span wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}">
                                        @if ($page == $paginator->currentPage())
                                            <span class="inline-flex size-9 items-center justify-center rounded-lg bg-[#031636] text-sm font-bold text-white shadow-xs" aria-current="page">
                                                {{ $page }}
                                            </span>
                                        @else
                                            <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" class="inline-flex size-9 items-center justify-center rounded-lg border border-[#c5c6cf]/50 bg-white text-sm font-semibold text-[#44474e] transition hover:border-[#031636] hover:text-[#031636] active:scale-95">
                                                {{ $page }}
                                            </button>
                                        @endif
                                    </span>
                                @endforeach
                            @endif
                        @endforeach

                        {{-- Next Button --}}
                        @if ($paginator->hasMorePages())
                            <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" class="inline-flex size-9 items-center justify-center rounded-lg border border-[#c5c6cf]/60 bg-white text-[#031636] shadow-xs transition hover:bg-[#f4f4f2] active:scale-95" aria-label="{{ __('pagination.next') }}">
                                <span class="material-symbols-outlined text-lg">chevron_right</span>
                            </button>
                        @else
                            <span class="inline-flex size-9 items-center justify-center rounded-lg border border-[#c5c6cf]/30 bg-[#f4f4f2] text-[#75777f]/50 cursor-not-allowed" aria-disabled="true">
                                <span class="material-symbols-outlined text-lg">chevron_right</span>
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </nav>
    @endif
</div>
