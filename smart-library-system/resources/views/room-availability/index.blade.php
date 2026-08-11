<x-layouts::app :title="__('Room Availability')">
    @php
        /*
         |--------------------------------------------------------------------------
         | 页面显示数据
         |--------------------------------------------------------------------------
         */

        // 当天全部房间时段的数量。
        $totalSlots = (int) array_sum($summary);

        // 从时间表动态取得开放及关闭时间。
        $firstSlot = $slots->first();
        $lastSlot = $slots->last();
        $closingAt = $lastSlot?->addHour();

        $operatingHoursLabel =
            $firstSlot && $closingAt
                ? $firstSlot->format('g:i A')
                    .' – '.
                    $closingAt->format('g:i A')
                : 'Operating hours unavailable';

        $closesNextDay = $closingAt
            ? ! $closingAt->isSameDay($selectedDate)
            : false;

        /*
         * 判断目前查看的日期是否属于 Exam Period。
         *
         * exam_period_enabled 代表 Exam Period 功能已开启，
         * 但当前选择的日期不一定在 Exam Period 范围内。
         */
        $examActiveForSelectedDate =
            $librarySetting->isExamPeriodActiveFor(
                $selectedDate
            );

        // Summary Cards 显示资料，实际数量来自 Controller。
        $summaryCards = [
            'available' => [
                'label' => 'Available Slots',
                'description' => 'Open for reservation',
                'letter' => 'A',
            ],
            'reserved' => [
                'label' => 'Reserved Slots',
                'description' => 'Confirmed reservations',
                'letter' => 'R',
            ],
            'maintenance' => [
                'label' => 'Maintenance Slots',
                'description' => 'Blocked for maintenance',
                'letter' => 'M',
            ],
            'unavailable' => [
                'label' => 'Unavailable Slots',
                'description' => 'Not available for use',
                'letter' => 'U',
            ],
        ];
    @endphp

    <div
        data-page-transition
        class="mx-auto flex w-full max-w-7xl flex-1
               flex-col gap-5 px-2 sm:px-4"
    >
        {{-- 页面标题 --}}
        <header
            class="flex flex-col gap-4
                   sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <div
                    class="mb-3 inline-flex items-center gap-2
                           rounded-full border border-blue-500/20
                           bg-blue-500/10 px-3 py-1
                           text-xs font-bold uppercase tracking-wider
                           text-blue-700 dark:text-blue-300"
                >
                    <span
                        class="size-2 rounded-full bg-blue-500"
                        aria-hidden="true"
                    ></span>

                    Live Room Schedule
                </div>

                <flux:heading size="xl" level="1">
                    Room Availability
                </flux:heading>

                <flux:text class="mt-2 max-w-2xl">
                    Check room availability, maintenance periods
                    and reservations by time.
                </flux:text>
            </div>

            <flux:button
                :href="route('room-reservations.index')"
                variant="filled"
                icon="calendar-days"
                class="w-full sm:w-auto"
                wire:navigate
            >
                Reservations
            </flux:button>
        </header>

        {{-- 日期选择工具栏 --}}
        <section
            class="flex flex-col gap-5 rounded-2xl
                   border border-zinc-200 bg-white p-5 shadow-sm
                   dark:border-zinc-700 dark:bg-zinc-900
                   lg:flex-row lg:items-center
                   lg:justify-between"
        >
            <div class="flex min-w-0 items-center gap-4">
                <div
                    class="flex size-12 shrink-0 items-center
                           justify-center rounded-xl bg-blue-500/10
                           text-xl font-bold text-blue-600
                           dark:text-blue-400"
                    aria-hidden="true"
                >
                    {{ $selectedDate->format('d') }}
                </div>

                <div class="min-w-0">
                    <p
                        class="text-sm font-medium
                               text-zinc-500 dark:text-zinc-400"
                    >
                        Viewing schedule for
                    </p>

                    <p
                        class="mt-1 text-lg font-bold
                               text-zinc-900 dark:text-white"
                    >
                        {{ $selectedDate->format('l, d F Y') }}
                    </p>

                    <p
                        class="mt-1 text-sm
                               text-zinc-500 dark:text-zinc-400"
                    >
                        {{ $rooms->count() }}

                        {{ \Illuminate\Support\Str::plural(
                            'room',
                            $rooms->count()
                        ) }}

                        · {{ $slots->count() }} time periods
                        · {{ number_format($totalSlots) }} total slots
                    </p>
                </div>
            </div>

            <form
                method="GET"
                action="{{ route('room-availability.index') }}"
                class="flex w-full flex-col gap-3
                       sm:flex-row sm:flex-wrap sm:items-end
                       lg:w-auto lg:justify-end"
            >
                <div class="w-full sm:w-auto">
                    <label
                        for="date"
                        class="mb-1 block whitespace-nowrap
                               text-sm font-semibold
                               text-zinc-700 dark:text-zinc-200"
                    >
                        Select date
                    </label>

                    <input
                        id="date"
                        name="date"
                        type="date"
                        required
                        value="{{ $selectedDate->format('Y-m-d') }}"
                        class="min-h-11 w-full rounded-xl
                               border border-zinc-300 bg-white px-3
                               text-zinc-900
                               focus:border-blue-500
                               focus:ring-blue-500
                               dark:border-zinc-700
                               dark:bg-zinc-800 dark:text-white
                               sm:w-auto"
                    >
                </div>

                @unless ($selectedDate->isToday())
                    <flux:button
                        :href="route('room-availability.index', [
                            'date' => now()->format('Y-m-d'),
                        ])"
                        variant="ghost"
                        class="min-h-11 w-full
                               hover:!bg-zinc-500/10 sm:w-auto"
                        wire:navigate
                    >
                        Today
                    </flux:button>
                @endunless

                <flux:button
                    type="submit"
                    variant="primary"
                    class="min-h-11 w-full sm:w-auto"
                >
                    View Schedule
                </flux:button>
            </form>
        </section>

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

        {{-- 验证错误 --}}
        @if ($errors->any())
            <div
                role="alert"
                class="rounded-xl border border-red-500/30
                       bg-red-500/10 px-4 py-3"
            >
                <p
                    class="font-semibold
                           text-red-700 dark:text-red-300"
                >
                    Please correct the following:
                </p>

                <ul
                    class="mt-2 list-disc space-y-1 ps-5
                           text-sm text-red-700
                           dark:text-red-300"
                >
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- 动态时段统计 --}}
        <section
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
            aria-label="Room availability summary"
        >
            @foreach ($summaryCards as $status => $card)
                @php
                    $slotCount = (int) (
                        $summary[$status] ?? 0
                    );

                    $percentage = $totalSlots > 0
                        ? (int) round(
                            ($slotCount / $totalSlots) * 100
                        )
                        : 0;
                @endphp

                <article
                    class="rounded-2xl border border-zinc-200
                           bg-white p-5 shadow-sm
                           transition-colors hover:bg-zinc-500/5
                           dark:border-zinc-700 dark:bg-zinc-900"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p
                                class="text-sm font-medium
                                       text-zinc-500 dark:text-zinc-400"
                            >
                                {{ $card['label'] }}
                            </p>

                            <p
                                @class([
                                    'mt-2 text-3xl font-bold',

                                    'text-emerald-600 dark:text-emerald-400' =>
                                        $status === 'available',

                                    'text-blue-600 dark:text-blue-400' =>
                                        $status === 'reserved',

                                    'text-amber-600 dark:text-amber-400' =>
                                        $status === 'maintenance',

                                    'text-red-600 dark:text-red-400' =>
                                        $status === 'unavailable',
                                ])
                            >
                                {{ number_format($slotCount) }}
                            </p>
                        </div>

                        <div
                            @class([
                                'flex size-11 items-center justify-center',
                                'rounded-xl text-sm font-bold',

                                'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' =>
                                    $status === 'available',

                                'bg-blue-500/10 text-blue-600 dark:text-blue-400' =>
                                    $status === 'reserved',

                                'bg-amber-500/10 text-amber-600 dark:text-amber-400' =>
                                    $status === 'maintenance',

                                'bg-red-500/10 text-red-600 dark:text-red-400' =>
                                    $status === 'unavailable',
                            ])
                            aria-hidden="true"
                        >
                            {{ $card['letter'] }}
                        </div>
                    </div>

                    <div
                        class="mt-4 flex items-end
                               justify-between gap-3"
                    >
                        <p
                            class="text-sm
                                   text-zinc-500 dark:text-zinc-400"
                        >
                            {{ $card['description'] }}
                        </p>

                        <span
                            class="shrink-0 rounded-full
                                   bg-zinc-100 px-2.5 py-1
                                   text-xs font-bold text-zinc-600
                                   dark:bg-zinc-800 dark:text-zinc-300"
                        >
                            {{ $percentage }}%
                        </span>
                    </div>
                </article>
            @endforeach
        </section>

        {{-- Room × Time 时间表 --}}
        <section
            class="overflow-hidden rounded-2xl
                   border border-zinc-200 bg-white shadow-sm
                   dark:border-zinc-700 dark:bg-zinc-900"
        >
            {{-- 时间表标题 --}}
            <div
                class="flex flex-col gap-4
                       border-b border-zinc-200 px-5 py-4
                       dark:border-zinc-700
                       sm:flex-row sm:items-center
                       sm:justify-between"
            >
                <div>
                    <h2
                        class="font-bold
                               text-zinc-900 dark:text-white"
                    >
                        {{ $selectedDate->format('l, d F Y') }}
                    </h2>

                    <p
                        class="mt-1 text-sm
                               text-zinc-500 dark:text-zinc-400"
                    >
                        Select an available time slot
                        to prepare a reservation.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <flux:button
                        size="sm"
                        variant="ghost"
                        class="min-h-10 flex-1
                               hover:!bg-zinc-500/10 sm:flex-none"
                        :href="route('room-availability.index', [
                            'date' => $selectedDate
                                ->subDay()
                                ->format('Y-m-d'),
                        ])"
                        wire:navigate
                    >
                        ← Previous
                    </flux:button>

                    <flux:button
                        size="sm"
                        variant="ghost"
                        class="min-h-10 flex-1
                               hover:!bg-zinc-500/10 sm:flex-none"
                        :href="route('room-availability.index', [
                            'date' => $selectedDate
                                ->addDay()
                                ->format('Y-m-d'),
                        ])"
                        wire:navigate
                    >
                        Next →
                    </flux:button>
                </div>
            </div>

            {{-- 开放时间、营业模式及 Exam Period 设置 --}}
            <div
                class="flex flex-col gap-4
                       border-b border-blue-500/20
                       bg-blue-500/5 px-5 py-4
                       2xl:flex-row 2xl:items-center
                       2xl:justify-between"
            >
                {{-- Library Operating Hours --}}
                <div class="flex shrink-0 items-center gap-3">
                    <span
                        class="flex size-10 shrink-0 items-center
                               justify-center rounded-xl
                               bg-blue-500/10 font-bold
                               text-blue-600 dark:text-blue-400"
                        aria-hidden="true"
                    >
                        ◷
                    </span>

                    <div class="min-w-0">
                        <p
                            class="text-xs font-bold uppercase
                                   tracking-wider
                                   text-blue-700 dark:text-blue-300"
                        >
                            Library Operating Hours
                        </p>

                        <div
                            class="mt-0.5 flex flex-wrap
                                   items-center gap-x-2 gap-y-1"
                        >
                            {{--
                                时间使用 whitespace-nowrap，
                                保证 8:00 AM – 8:00 PM
                                永远保持在同一行。
                            --}}
                            <p
                                class="whitespace-nowrap font-semibold
                                       text-zinc-900 dark:text-white"
                            >
                                {{ $operatingHoursLabel }}
                            </p>

                            @if ($closesNextDay)
                                <span
                                    class="whitespace-nowrap text-sm
                                           text-zinc-500
                                           dark:text-zinc-400"
                                >
                                    (next day)
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- 营业状态与 Exam Period --}}
                <div
                    class="flex w-full flex-col gap-3
                           2xl:w-auto 2xl:items-end"
                >
                    {{-- 所有用户都能查看营业模式 --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            @class([
                                'inline-flex items-center gap-2',
                                'rounded-full px-3 py-1.5',
                                'text-xs font-bold',

                                'bg-amber-500/10 text-amber-700 dark:text-amber-300' =>
                                    $examActiveForSelectedDate,

                                'bg-blue-500/10 text-blue-700 dark:text-blue-300' =>
                                    $librarySetting->exam_period_enabled
                                    && ! $examActiveForSelectedDate,

                                'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300' =>
                                    ! $librarySetting->exam_period_enabled,
                            ])
                        >
                            <span
                                @class([
                                    'size-2 rounded-full',

                                    'bg-amber-500' =>
                                        $examActiveForSelectedDate,

                                    'bg-blue-500' =>
                                        $librarySetting
                                            ->exam_period_enabled
                                        && ! $examActiveForSelectedDate,

                                    'bg-emerald-500' =>
                                        ! $librarySetting
                                            ->exam_period_enabled,
                                ])
                            ></span>

                            @if ($examActiveForSelectedDate)
                                Exam Period Active
                            @elseif (
                                $librarySetting->exam_period_enabled
                            )
                                Exam Period Scheduled
                            @else
                                Regular Hours
                            @endif
                        </span>

                        {{-- Exam Period 日期范围 --}}
                        @if (
                            $librarySetting->exam_period_enabled
                            && $librarySetting
                                ->exam_period_starts_on
                            && $librarySetting
                                ->exam_period_ends_on
                        )
                            <span
                                class="inline-flex items-center
                                       rounded-full bg-zinc-100
                                       px-3 py-1.5 text-xs font-medium
                                       text-zinc-600
                                       dark:bg-zinc-800
                                       dark:text-zinc-300"
                            >
                                {{ $librarySetting
                                    ->exam_period_starts_on
                                    ->format('d M Y') }}

                                <span
                                    class="mx-1.5"
                                    aria-hidden="true"
                                >
                                    →
                                </span>

                                {{ $librarySetting
                                    ->exam_period_ends_on
                                    ->format('d M Y') }}
                            </span>
                        @endif

                        {{-- 横向滚动提醒 --}}
                        <span
                            class="inline-flex items-center gap-2
                                   rounded-full bg-zinc-100
                                   px-3 py-1.5 text-xs font-medium
                                   text-zinc-600
                                   dark:bg-zinc-800
                                   dark:text-zinc-300"
                        >
                            <span aria-hidden="true">↔</span>
                            Scroll to view later times
                        </span>
                    </div>

                    {{-- 只有 Librarian 可以设置 Exam Period --}}
                    @can('update', $librarySetting)
                        <div
                            class="w-full rounded-xl border
                                   border-zinc-200 bg-white/70 p-3
                                   dark:border-zinc-700
                                   dark:bg-zinc-900/70
                                   2xl:w-auto"
                        >
                            {{-- 开启或更新 Exam Period --}}
                            <form
                                method="POST"
                                action="{{ route(
                                    'library-settings.exam-period.update'
                                ) }}"
                                class="flex flex-col gap-3
                                       lg:flex-row lg:items-end"
                            >
                                @csrf
                                @method('PATCH')

                                <input
                                    type="hidden"
                                    name="enabled"
                                    value="1"
                                >

                                <div class="w-full lg:w-auto">
                                    <label
                                        for="exam_period_starts_on"
                                        class="mb-1 block
                                               text-xs font-semibold
                                               text-zinc-600
                                               dark:text-zinc-300"
                                    >
                                        Starts on
                                    </label>

                                    <input
                                        id="exam_period_starts_on"
                                        name="exam_period_starts_on"
                                        type="date"
                                        required
                                        value="{{ old(
                                            'exam_period_starts_on',
                                            $librarySetting
                                                ->exam_period_starts_on
                                                ?->format('Y-m-d')
                                            ?? now()->format('Y-m-d')
                                        ) }}"
                                        class="min-h-10 w-full
                                               rounded-lg border
                                               border-zinc-300
                                               bg-white px-3 text-sm
                                               text-zinc-900
                                               focus:border-amber-500
                                               focus:ring-amber-500
                                               dark:border-zinc-700
                                               dark:bg-zinc-800
                                               dark:text-white
                                               lg:w-40"
                                    >
                                </div>

                                <div class="w-full lg:w-auto">
                                    <label
                                        for="exam_period_ends_on"
                                        class="mb-1 block
                                               text-xs font-semibold
                                               text-zinc-600
                                               dark:text-zinc-300"
                                    >
                                        Ends on
                                    </label>

                                    <input
                                        id="exam_period_ends_on"
                                        name="exam_period_ends_on"
                                        type="date"
                                        required
                                        value="{{ old(
                                            'exam_period_ends_on',
                                            $librarySetting
                                                ->exam_period_ends_on
                                                ?->format('Y-m-d')
                                            ?? now()
                                                ->addWeeks(2)
                                                ->format('Y-m-d')
                                        ) }}"
                                        class="min-h-10 w-full
                                               rounded-lg border
                                               border-zinc-300
                                               bg-white px-3 text-sm
                                               text-zinc-900
                                               focus:border-amber-500
                                               focus:ring-amber-500
                                               dark:border-zinc-700
                                               dark:bg-zinc-800
                                               dark:text-white
                                               lg:w-40"
                                    >
                                </div>

                                <button
                                    type="submit"
                                    class="min-h-10 w-full rounded-lg
                                           border border-amber-500/30
                                           bg-amber-500/10 px-4
                                           text-sm font-semibold
                                           text-amber-700
                                           transition-colors
                                           hover:bg-amber-500/20
                                           focus:outline-none
                                           focus:ring-2
                                           focus:ring-amber-500
                                           dark:text-amber-300
                                           lg:w-auto"
                                >
                                    {{ $librarySetting
                                        ->exam_period_enabled
                                            ? 'Update Period'
                                            : 'Enable Exam Period' }}
                                </button>
                            </form>

                            {{-- Exam Period 日期说明 --}}
                            <p
                                class="mt-3 flex items-start gap-2
                                       text-xs leading-5
                                       text-zinc-500
                                       dark:text-zinc-400"
                            >
                                <span
                                    class="mt-0.5 font-bold
                                           text-blue-600
                                           dark:text-blue-400"
                                    aria-hidden="true"
                                >
                                    i
                                </span>

                                <span>
                                    Each date represents a library
                                    business day. The last exam date
                                    remains open until

                                    <strong
                                        class="text-zinc-700
                                               dark:text-zinc-200"
                                    >
                                        {{
                                            \Carbon\CarbonImmutable::today()
                                                ->setTime(
                                                    $librarySetting
                                                        ->exam_closing_hour,
                                                    0
                                                )
                                                ->format('g:i A')
                                        }}

                                        @if (
                                            $librarySetting
                                                ->exam_closes_next_day
                                        )
                                            the following day
                                        @endif
                                    </strong>.
                                </span>
                            </p>

                            {{-- 关闭 Exam Period --}}
                            @if (
                                $librarySetting->exam_period_enabled
                            )
                                <form
                                    method="POST"
                                    action="{{ route(
                                        'library-settings.exam-period.update'
                                    ) }}"
                                    class="mt-3 border-t
                                           border-zinc-200 pt-3
                                           dark:border-zinc-700"
                                    onsubmit="return confirm(
                                        'Disable Exam Period? The system will first check for confirmed reservations after 8:00 PM.'
                                    )"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <input
                                        type="hidden"
                                        name="enabled"
                                        value="0"
                                    >

                                    <div
                                        class="flex flex-col gap-3
                                               sm:flex-row
                                               sm:items-center
                                               sm:justify-between"
                                    >
                                        <p
                                            class="text-xs leading-5
                                                   text-zinc-500
                                                   dark:text-zinc-400"
                                        >
                                            After-hours reservations
                                            must be cancelled or
                                            rescheduled before Exam
                                            Period can be disabled.
                                        </p>

                                        <button
                                            type="submit"
                                            class="min-h-10 w-full
                                                   shrink-0 rounded-lg
                                                   border border-red-500/30
                                                   bg-red-500/10 px-4
                                                   text-sm font-semibold
                                                   text-red-700
                                                   transition-colors
                                                   hover:bg-red-500/20
                                                   focus:outline-none
                                                   focus:ring-2
                                                   focus:ring-red-500
                                                   dark:text-red-300
                                                   sm:w-auto"
                                        >
                                            Disable Exam Period
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    @endcan
                </div>
            </div>

            {{-- 时间表状态说明 --}}
            <div
                class="flex flex-wrap items-center
                       gap-x-6 gap-y-3
                       border-b border-zinc-200
                       px-5 py-3 text-sm
                       dark:border-zinc-700"
                aria-label="Schedule status legend"
            >
                @foreach ([
                    ['Available', 'bg-emerald-500'],
                    ['Reserved', 'bg-blue-500'],
                    ['Maintenance', 'bg-amber-500'],
                    ['Unavailable', 'bg-red-500'],
                    ['Past', 'bg-zinc-500'],
                ] as [$label, $dotColor])
                    <span class="inline-flex items-center gap-2">
                        <span
                            class="size-3 rounded-full
                                   {{ $dotColor }}"
                        ></span>

                        {{ $label }}
                    </span>
                @endforeach

                <span
                    class="ms-auto hidden items-center gap-2
                           text-xs text-zinc-500
                           dark:text-zinc-400 md:inline-flex"
                >
                    <span aria-hidden="true">↔</span>
                    Place cursor over schedule and use mouse wheel
                </span>
            </div>

       {{-- 横向滚动时间表 --}}
{{-- 横向滚动时间表 --}}
<div
    data-horizontal-scroll
    tabindex="0"
    onwheel="
    const container = this;
    const currentTime = Date.now();

    const maximumScrollLeft =
        container.scrollWidth -
        container.clientWidth;

    const rawScrollDistance =
        event.deltaY !== 0
            ? event.deltaY
            : event.deltaX;

    /*
     * 没有正在执行动画时，
     * 从当前真实位置开始计算。
     */
    if (!container._scrollFrame) {
        container._scrollTarget =
            container.scrollLeft;
    }

    const movingRight =
        rawScrollDistance > 0;

    const canScrollRight =
        container._scrollTarget <
        maximumScrollLeft - 1;

    const canScrollLeft =
        container._scrollTarget > 1;

    const canMove =
        movingRight
            ? canScrollRight
            : canScrollLeft;

    const lockUntil = Number(
        container.dataset.scrollLockUntil || 0
    );

    if (canMove) {
        event.preventDefault();
        event.stopPropagation();

        /*
         * 计算新的目标位置。
         * 0.8 控制每次滚轮的移动距离。
         */
        container._scrollTarget = Math.min(
            maximumScrollLeft,
            Math.max(
                0,
                container._scrollTarget +
                rawScrollDistance * 0.8,
            ),
        );

        /*
         * 横向滚动结束后保留 550ms 缓冲，
         * 避免马上切换成网页上下滚动。
         */
        container.dataset.scrollLockUntil =
            String(currentTime + 550);

        /*
         * 已有动画时只更新目标位置，
         * 不重复创建 requestAnimationFrame。
         */
        if (!container._scrollFrame) {
            const animateScroll = () => {
                const difference =
                    container._scrollTarget -
                    container.scrollLeft;

                /*
                 * 已经非常接近目标位置，
                 * 直接结束动画。
                 */
                if (Math.abs(difference) < 0.5) {
                    container.scrollLeft =
                        container._scrollTarget;

                    container._scrollFrame = null;

                    return;
                }

                /*
                 * 每一帧移动剩余距离的 22%。
                 * requestAnimationFrame 会配合屏幕刷新率。
                 */
                container.scrollLeft +=
                    difference * 0.22;

                container._scrollFrame =
                    requestAnimationFrame(
                        animateScroll,
                    );
            };

            container._scrollFrame =
                requestAnimationFrame(
                    animateScroll,
                );
        }
    } else if (currentTime < lockUntil) {
        /*
         * 到达左右边界后的短暂缓冲期。
         */
        event.preventDefault();
        event.stopPropagation();
    }
"
    aria-label="Room availability schedule. Place the cursor over the schedule and use the mouse wheel to scroll horizontally."
    class="overflow-x-auto overscroll-x-contain
           touch-pan-x
           focus:outline-none focus:ring-2
           focus:ring-inset focus:ring-blue-500"
>
    <table
        class="w-full min-w-[1500px]
               border-collapse text-sm"
    >
    <table
        class="w-full min-w-[1500px]
               border-collapse text-sm"
    >
    <table
        class="w-full min-w-[1500px]
               border-collapse text-sm"
    >
                <table
                    class="w-full min-w-[1500px]
                           border-collapse text-sm"
                >
                    <thead
                        class="bg-zinc-100 text-zinc-700
                               dark:bg-zinc-800 dark:text-zinc-200"
                    >
                        <tr>
                            <th
                                class="sticky left-0 z-20
                                       min-w-72 border-e
                                       border-zinc-200 bg-zinc-100
                                       px-5 py-4 text-left font-semibold
                                       dark:border-zinc-700
                                       dark:bg-zinc-800"
                            >
                                Room
                            </th>

                            @foreach ($slots as $slot)
                                <th
                                    class="min-w-28 px-3 py-4
                                           text-center font-semibold"
                                >
                                    <span class="block whitespace-nowrap">
                                        {{ $slot->format('h:i A') }}
                                    </span>

                                    @if (
                                        ! $slot->isSameDay(
                                            $selectedDate
                                        )
                                    )
                                        <span
                                            class="mt-1 block text-[10px]
                                                   font-bold uppercase
                                                   tracking-wider
                                                   text-amber-600
                                                   dark:text-amber-400"
                                        >
                                            Next Day
                                        </span>
                                    @endif
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody
                        class="divide-y divide-zinc-200
                               dark:divide-zinc-800"
                    >
                        @forelse ($rooms as $room)
                            <tr>
                                {{-- Sticky 房间资料 --}}
                                <th
                                    class="sticky left-0 z-10
                                           min-w-72 border-e
                                           border-zinc-200 bg-white
                                           px-5 py-4 text-left
                                           dark:border-zinc-700
                                           dark:bg-zinc-900"
                                >
                                    <p
                                        class="font-bold
                                               text-zinc-900
                                               dark:text-white"
                                    >
                                        {{ $room->room_number }}
                                    </p>

                                    <p
                                        class="mt-1 max-w-48 truncate
                                               font-normal text-zinc-500
                                               dark:text-zinc-400"
                                        title="{{ $room->name }}"
                                    >
                                        {{ $room->name }}
                                    </p>

                                    <p
                                        class="mt-1 text-xs font-normal
                                               text-zinc-500
                                               dark:text-zinc-400"
                                    >
                                        {{ $room->capacity }}

                                        {{ \Illuminate\Support\Str::plural(
                                            'person',
                                            $room->capacity
                                        ) }}
                                    </p>

                                    {{-- 房间设施 --}}
                                    <div
                                        class="mt-3 flex max-w-64
                                               flex-wrap gap-1.5"
                                    >
                                        @forelse (
                                            $room->facilities ?? []
                                            as $facility
                                        )
                                            <span
                                                class="inline-flex
                                                       rounded-full border
                                                       border-blue-500/20
                                                       bg-blue-500/10
                                                       px-2 py-1
                                                       text-[10px]
                                                       font-semibold
                                                       text-blue-700
                                                       dark:text-blue-300"
                                            >
                                                {{ str(
                                                    $facility
                                                )->headline() }}
                                            </span>
                                        @empty
                                            <span
                                                class="text-xs font-normal
                                                       text-zinc-400
                                                       dark:text-zinc-500"
                                            >
                                                No facilities listed
                                            </span>
                                        @endforelse
                                    </div>
                                </th>

                                {{-- 每个时间段 --}}
                                @foreach ($slots as $slot)
                                    @php
                                        $timeKey =
                                            $slot->format('H:i');

                                        $cell =
                                            $schedule[$room->id][$timeKey]
                                            ?? [
                                                'status' => 'unavailable',
                                                'reservation' => null,
                                            ];

                                        $cellStatus = $cell['status'];

                                        $cellReservation =
                                            $cell['reservation']
                                            ?? null;

                                        $isPast = $slot->isPast();
                                    @endphp

                                    <td class="px-1.5 py-2 text-center">
                                        @if (
                                            $cellStatus === 'available'
                                            && ! $isPast
                                        )
                                            <a
                                                href="{{ route(
                                                    'room-reservations.create',
                                                    [
                                                        /*
                                                         * 直接使用 Slot 日期。
                                                         * 跨午夜时会自动使用第二天。
                                                         */
                                                        'date' =>
                                                            $slot->format(
                                                                'Y-m-d'
                                                            ),

                                                        'room_id' =>
                                                            $room->id,

                                                        'start' =>
                                                            $slot->format(
                                                                'H:i'
                                                            ),
                                                    ]
                                                ) }}"
                                                class="flex min-h-12
                                                       items-center
                                                       justify-center
                                                       rounded-lg border
                                                       border-emerald-500/30
                                                       bg-emerald-500/10
                                                       px-2 font-semibold
                                                       text-emerald-700
                                                       transition-colors
                                                       hover:bg-emerald-500/20
                                                       focus:outline-none
                                                       focus:ring-2
                                                       focus:ring-emerald-500
                                                       dark:text-emerald-300"
                                                wire:navigate
                                            >
                                                Available
                                            </a>
                                        @elseif (
                                            $cellStatus === 'reserved'
                                        )
                                            <span
                                                class="flex min-h-12
                                                       items-center
                                                       justify-center
                                                       rounded-lg border
                                                       border-blue-500/30
                                                       bg-blue-500/10
                                                       px-2 font-semibold
                                                       text-blue-700
                                                       dark:text-blue-300"
                                                @if (
                                                    auth()->user()
                                                        ->isLibrarian()
                                                    && $cellReservation
                                                )
                                                    title="Reserved by {{ $cellReservation->user->name }}"
                                                @endif
                                            >
                                                Reserved
                                            </span>
                                        @elseif (
                                            $cellStatus === 'maintenance'
                                        )
                                            <span
                                                class="flex min-h-12
                                                       items-center
                                                       justify-center
                                                       rounded-lg border
                                                       border-amber-500/30
                                                       bg-amber-500/10
                                                       px-2 font-semibold
                                                       text-amber-700
                                                       dark:text-amber-300"
                                            >
                                                Maintenance
                                            </span>
                                        @elseif (
                                            $cellStatus === 'unavailable'
                                        )
                                            <span
                                                class="flex min-h-12
                                                       items-center
                                                       justify-center
                                                       rounded-lg border
                                                       border-red-500/30
                                                       bg-red-500/10
                                                       px-2 font-semibold
                                                       text-red-700
                                                       dark:text-red-300"
                                            >
                                                Unavailable
                                            </span>
                                        @else
                                            <span
                                                class="flex min-h-12
                                                       items-center
                                                       justify-center
                                                       rounded-lg
                                                       bg-zinc-100 px-2
                                                       text-zinc-500
                                                       dark:bg-zinc-800
                                                       dark:text-zinc-400"
                                            >
                                                Past
                                            </span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="{{ $slots->count() + 1 }}"
                                    class="px-6 py-16 text-center"
                                >
                                    <p
                                        class="font-semibold
                                               text-zinc-900
                                               dark:text-white"
                                    >
                                        No rooms available
                                    </p>

                                    <p
                                        class="mt-2 text-sm
                                               text-zinc-500
                                               dark:text-zinc-400"
                                    >
                                        Rooms added to the system
                                        will appear here.
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts::app>