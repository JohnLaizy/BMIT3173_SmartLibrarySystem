@props([
    'paginator',
    'ariaLabel' => 'Pagination',
    'darkCard' => false,
])

@php
    /*
     * 每个 listing card 共用的分页 footer。
     *
     * - 左侧显示目前可见记录范围，例如 Showing 1 to 5 of 15 results。
     * - 右侧只在有多页资料时显示上一页、页码和下一页。
     * - Controller 已为各 paginator 使用 withQueryString()，所以换页时
     *   会继续保留用户正在使用的 search / filter 条件。
     */
    $startPage = max(1, $paginator->currentPage() - 1);
    $endPage = min($paginator->lastPage(), $paginator->currentPage() + 1);
@endphp

@if ($paginator->total() > 0)
    <div
        @class([
            'flex flex-col gap-3 border-t px-5 py-4 sm:flex-row sm:items-center sm:justify-between',
            'border-zinc-800 bg-zinc-950' => $darkCard,
            'border-zinc-200 dark:border-zinc-700' => ! $darkCard,
        ])
    >
        <p
            @class([
                'text-xs',
                'text-zinc-400' => $darkCard,
                'text-zinc-500 dark:text-zinc-400' => ! $darkCard,
            ])
        >
            Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }} results
        </p>

        @if ($paginator->hasPages())
            <nav class="flex items-center gap-1" aria-label="{{ $ariaLabel }}">
                @if ($paginator->onFirstPage())
                    <span
                        @class([
                            'grid size-11 place-items-center rounded-lg text-lg opacity-40 sm:size-10',
                            'text-zinc-600' => $darkCard,
                            'text-zinc-400' => ! $darkCard,
                        ])
                        aria-disabled="true"
                    >
                        ‹
                    </span>
                @else
                    <a
                        href="{{ $paginator->previousPageUrl() }}"
                        @class([
                            'grid size-11 place-items-center rounded-lg text-lg transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 sm:size-10',
                            'text-zinc-400 hover:bg-zinc-800' => $darkCard,
                            'text-zinc-500 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800' => ! $darkCard,
                        ])
                        aria-label="Previous page"
                        rel="prev"
                        wire:navigate
                    >
                        ‹
                    </a>
                @endif

                @foreach ($paginator->getUrlRange($startPage, $endPage) as $page => $url)
                    <a
                        href="{{ $url }}"
                        @if ($page === $paginator->currentPage())
                            aria-current="page"
                        @endif
                        @class([
                            'grid size-11 place-items-center rounded-lg text-sm font-medium transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 sm:size-10',
                            'bg-zinc-700 text-white' => $darkCard && $page === $paginator->currentPage(),
                            'text-zinc-400 hover:bg-zinc-800' => $darkCard && $page !== $paginator->currentPage(),
                            'bg-zinc-200 text-zinc-900 dark:bg-zinc-700 dark:text-white' => ! $darkCard && $page === $paginator->currentPage(),
                            'text-zinc-500 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800' => ! $darkCard && $page !== $paginator->currentPage(),
                        ])
                        wire:navigate
                    >
                        {{ $page }}
                    </a>
                @endforeach

                @if ($paginator->hasMorePages())
                    <a
                        href="{{ $paginator->nextPageUrl() }}"
                        @class([
                            'grid size-11 place-items-center rounded-lg text-lg transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 sm:size-10',
                            'text-zinc-400 hover:bg-zinc-800' => $darkCard,
                            'text-zinc-500 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800' => ! $darkCard,
                        ])
                        aria-label="Next page"
                        rel="next"
                        wire:navigate
                    >
                        ›
                    </a>
                @else
                    <span
                        @class([
                            'grid size-11 place-items-center rounded-lg text-lg opacity-40 sm:size-10',
                            'text-zinc-600' => $darkCard,
                            'text-zinc-400' => ! $darkCard,
                        ])
                        aria-disabled="true"
                    >
                        ›
                    </span>
                @endif
            </nav>
        @endif
    </div>
@endif
