<x-layouts::app :title="__('Maintenance Management')">
    <div
        data-page-transition
        class="mx-auto flex w-full max-w-7xl
               flex-col gap-7 px-2 sm:px-4"
    >
        {{-- 页面标题 --}}
        <header
            class="flex flex-col justify-between gap-5
                   sm:flex-row sm:items-end"
        >
            <div>
                <div
                    class="mb-3 inline-flex items-center gap-2
                           rounded-full border border-amber-500/20
                           bg-amber-500/10 px-3 py-1
                           text-xs font-bold uppercase tracking-wider
                           text-amber-700 dark:text-amber-300"
                >
                    <span
                        class="size-2 rounded-full bg-amber-500"
                        aria-hidden="true"
                    ></span>

                    Room Operations
                </div>

                <flux:heading size="xl" level="1">
                    Maintenance Management
                </flux:heading>

                <flux:text class="mt-2 max-w-2xl">
                    Schedule room maintenance and prevent conflicts
                    with existing reservations.
                </flux:text>
            </div>

            <flux:button
                :href="route('maintenances.create')"
                variant="primary"
                icon="plus"
                class="min-h-11 w-full sm:w-auto"
                wire:navigate
            >
                Schedule Maintenance
            </flux:button>
        </header>

        {{-- 操作成功提示 --}}
        @if (session('success'))
            <div
                role="status"
                class="flex items-center gap-3 rounded-xl
                       border border-emerald-500/30
                       bg-emerald-500/10 px-4 py-3
                       text-sm font-medium text-emerald-700
                       dark:text-emerald-300"
            >
                <span
                    class="flex size-6 shrink-0 items-center
                           justify-center rounded-full
                           bg-emerald-500/20"
                    aria-hidden="true"
                >
                    ✓
                </span>

                {{ session('success') }}
            </div>
        @endif

        {{-- Maintenance Records --}}
        <section
            class="overflow-hidden rounded-2xl
                   border border-zinc-200 bg-white shadow-sm
                   dark:border-zinc-700 dark:bg-zinc-900"
        >
            {{-- Card 标题 --}}
            <div
                class="flex flex-wrap items-center justify-between
                       gap-3 border-b border-zinc-200
                       px-5 py-4 dark:border-zinc-700"
            >
                <div>
                    <h2
                        class="font-semibold
                               text-zinc-900 dark:text-white"
                    >
                        Maintenance Schedule
                    </h2>

                    <p
                        class="mt-1 text-sm
                               text-zinc-500 dark:text-zinc-400"
                    >
                        Review upcoming, active and completed
                        room maintenance records.
                    </p>
                </div>

                <span
                    class="rounded-full bg-zinc-100
                           px-3 py-1 text-sm font-medium
                           text-zinc-600
                           dark:bg-zinc-800 dark:text-zinc-300"
                >
                    {{ $maintenances->total() }}

                    {{ Str::plural(
                        'record',
                        $maintenances->total()
                    ) }}
                </span>
            </div>

            @if ($maintenances->isEmpty())
                {{--
                    没有 Maintenance 时不使用 min-w-[1000px] Table。

                    这样 Empty State 会根据目前可见的 Card 宽度居中，
                    不会在手机或平板上偏向右边。
                --}}
                <div
                    class="flex min-h-[340px] flex-col
                           items-center justify-center
                           px-6 py-12 text-center sm:py-16"
                >
                    <div
                        class="flex size-14 items-center
                               justify-center rounded-2xl
                               bg-amber-500/10 text-2xl"
                        aria-hidden="true"
                    >
                        🛠
                    </div>

                    <h3
                        class="mt-4 font-semibold
                               text-zinc-900 dark:text-white"
                    >
                        No maintenance scheduled
                    </h3>

                    <p
                        class="mt-2 max-w-sm text-sm leading-6
                               text-zinc-500 dark:text-zinc-400"
                    >
                        Schedule maintenance to block unavailable
                        rooms and avoid reservation conflicts.
                    </p>

                    <flux:button
                        :href="route('maintenances.create')"
                        variant="primary"
                        icon="plus"
                        class="mt-5 min-h-11"
                        wire:navigate
                    >
                        Schedule Maintenance
                    </flux:button>
                </div>
            @else
                {{--
                    有记录时才启用横向滚动及最小 Table 宽度，
                    避免手机端的资料栏被压缩。
                --}}
                <div class="overflow-x-auto">
                    <table
                        class="w-full min-w-[1000px]
                               table-fixed text-sm"
                    >
                        <thead
                            class="bg-zinc-100 text-left
                                   text-zinc-700
                                   dark:bg-zinc-800
                                   dark:text-zinc-200"
                        >
                            <tr>
                                <th
                                    class="w-[18%] px-6 py-4
                                           font-semibold"
                                >
                                    Room
                                </th>

                                <th
                                    class="w-[24%] px-6 py-4
                                           font-semibold"
                                >
                                    Maintenance
                                </th>

                                <th
                                    class="w-[28%] px-6 py-4
                                           font-semibold"
                                >
                                    Period
                                </th>

                                <th
                                    class="w-[14%] px-6 py-4
                                           font-semibold"
                                >
                                    Status
                                </th>

                                <th
                                    class="w-[16%] px-6 py-4
                                           text-right font-semibold"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody
                            class="divide-y divide-zinc-200
                                   dark:divide-zinc-800"
                        >
                            @foreach ($maintenances as $maintenance)
                                @php
                                    /*
                                     * 根据状态选择 Badge 颜色。
                                     */
                                    $statusColor = match (
                                        $maintenance->status
                                    ) {
                                        'scheduled' => 'amber',
                                        'in_progress' => 'blue',
                                        'completed' => 'green',
                                        default => 'zinc',
                                    };
                                @endphp

                                <tr
                                    class="transition-colors duration-150
                                           hover:bg-zinc-50
                                           dark:hover:bg-zinc-800/70"
                                >
                                    {{-- Room --}}
                                    <td class="px-6 py-5 align-middle">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="flex size-10 shrink-0
                                                       items-center justify-center
                                                       rounded-xl bg-amber-500/10
                                                       font-bold text-amber-700
                                                       dark:text-amber-300"
                                                aria-hidden="true"
                                            >
                                                {{ substr(
                                                    $maintenance->room
                                                        ->room_number,
                                                    0,
                                                    1
                                                ) }}
                                            </div>

                                            <div class="min-w-0">
                                                <p
                                                    class="truncate font-bold
                                                           text-zinc-900
                                                           dark:text-white"
                                                >
                                                    {{ $maintenance->room
                                                        ->room_number }}
                                                </p>

                                                <p
                                                    class="mt-1 truncate
                                                           text-xs text-zinc-500
                                                           dark:text-zinc-400"
                                                >
                                                    {{ $maintenance->room
                                                        ->name }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Maintenance --}}
                                    <td class="px-6 py-5 align-middle">
                                        <p
                                            class="truncate font-semibold
                                                   text-zinc-900
                                                   dark:text-white"
                                        >
                                            {{ $maintenance->title }}
                                        </p>

                                        <p
                                            class="mt-1 truncate
                                                   text-zinc-500
                                                   dark:text-zinc-400"
                                            title="{{ $maintenance
                                                ->description }}"
                                        >
                                            {{ $maintenance->description
                                                ?: 'No description provided' }}
                                        </p>
                                    </td>

                                    {{-- Period --}}
                                    <td class="px-6 py-5 align-middle">
                                        <div class="flex items-start gap-3">
                                            <div
                                                class="mt-0.5 flex size-8
                                                       shrink-0 items-center
                                                       justify-center rounded-lg
                                                       bg-zinc-100 text-zinc-500
                                                       dark:bg-zinc-800
                                                       dark:text-zinc-300"
                                                aria-hidden="true"
                                            >
                                                ◷
                                            </div>

                                            <div>
                                                <p
                                                    class="font-medium
                                                           text-zinc-900
                                                           dark:text-white"
                                                >
                                                    {{ $maintenance
                                                        ->starts_at
                                                        ->format(
                                                            'd M Y, h:i A'
                                                        ) }}
                                                </p>

                                                <p
                                                    class="mt-1
                                                           text-zinc-500
                                                           dark:text-zinc-400"
                                                >
                                                    Until

                                                    {{ $maintenance
                                                        ->ends_at
                                                        ->format(
                                                            'd M Y, h:i A'
                                                        ) }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Status --}}
                                    <td class="px-6 py-5 align-middle">
                                        <flux:badge
                                            :color="$statusColor"
                                        >
                                            {{ str(
                                                $maintenance->status
                                            )->headline() }}
                                        </flux:badge>
                                    </td>

                                    {{-- Actions --}}
                                    <td
                                        class="px-6 py-5
                                               text-right align-middle"
                                    >
                                        <div
                                            class="flex items-center
                                                   justify-end gap-2"
                                        >
                                            <flux:button
                                                size="sm"
                                                variant="ghost"
                                                class="min-h-10
                                                       hover:!bg-zinc-500/10"
                                                :href="route(
                                                    'maintenances.edit',
                                                    $maintenance
                                                )"
                                                wire:navigate
                                            >
                                                Edit
                                            </flux:button>

                                            <form
                                                method="POST"
                                                action="{{ route(
                                                    'maintenances.destroy',
                                                    $maintenance
                                                ) }}"
                                                onsubmit="return confirm(
                                                    'Delete this maintenance record?'
                                                )"
                                            >
                                                @csrf
                                                @method('DELETE')

                                                <flux:button
                                                    type="submit"
                                                    size="sm"
                                                    variant="danger"
                                                    class="min-h-10"
                                                >
                                                    Delete
                                                </flux:button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Pagination --}}
            @if ($maintenances->hasPages())
                <div
                    class="border-t border-zinc-200
                           px-5 py-4 dark:border-zinc-700"
                >
                    {{ $maintenances->links() }}
                </div>
            @endif
        </section>
    </div>
</x-layouts::app>