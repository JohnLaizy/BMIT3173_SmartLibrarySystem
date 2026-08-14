<x-layouts::app :title="__('Library Operations Dashboard')">
    @php
        /*
        |--------------------------------------------------------------------------
        | Admin Dashboard 数据准备
        |--------------------------------------------------------------------------
        |
        | 此页面只给 Librarian 使用。
        | 所有资料由 RoomDashboardController 传入，
        | Blade 只负责显示，不自行查询数据库。
        */

        $user = auth()->user();

        $totalRooms = (int) $roomStats['total'];
        $availableRooms = (int) $roomStats['available'];
        $reservedRooms = (int) $roomStats['reserved'];
        $unavailableRooms = (int) $roomStats['unavailable'];
        $maintenanceRooms = (int) $roomStats['maintenance'];

        /*
         * 总房间为 0 时，避免除以 0。
         */
        $availablePercentage = $totalRooms > 0
            ? round(($availableRooms / $totalRooms) * 100)
            : 0;

        $reservedPercentage = $totalRooms > 0
            ? round(($reservedRooms / $totalRooms) * 100)
            : 0;

        $maintenancePercentage = $totalRooms > 0
            ? round(($maintenanceRooms / $totalRooms) * 100)
            : 0;

        $unavailablePercentage = $totalRooms > 0
            ? round(($unavailableRooms / $totalRooms) * 100)
            : 0;

        /*
         * 根据目前时间显示 greeting。
         */
        $greeting = match (true) {
            now()->hour < 12 => 'Good morning',
            now()->hour < 18 => 'Good afternoon',
            default => 'Good evening',
        };

        /*
         * 使用 LibrarySetting Model 取得今天营业时间。
         * Regular 和 Exam Period 的时间由数据库设置决定。
         */
        $today = \Carbon\CarbonImmutable::today();

        $openingLabel = $librarySetting
            ->openingAt($today)
            ->format('g:i A');

        $closingLabel = $librarySetting
            ->closingAt($today)
            ->format('g:i A');

        $closingNextDay = $librarySetting
            ->closesNextDay($today);

        $upcomingMaintenanceCount = $upcomingMaintenances->count();

        $upcomingReservationCount = $upcomingReservations->count();
    @endphp

    <div
        data-page-transition
        class="mx-auto flex w-full max-w-7xl flex-1
               flex-col gap-6 px-3 py-1 sm:px-5"
    >
        {{-- 页面标题与 Admin 快速入口 --}}
        <header
            class="flex flex-col gap-5
                   lg:flex-row lg:items-center
                   lg:justify-between"
        >
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <flux:heading size="xl" level="1">
                        {{ $greeting }}, {{ $user->name }}
                    </flux:heading>

                    <span
                        class="rounded-full border border-violet-500/30
                               bg-violet-500/10 px-3 py-1
                               text-xs font-semibold uppercase
                               tracking-wide text-violet-700
                               dark:text-violet-300"
                    >
                        Librarian
                    </span>
                </div>

                <flux:text class="mt-2">
                    Monitor room operations, reservations and maintenance activity.
                </flux:text>
            </div>

            <div class="flex flex-wrap gap-3">
                <flux:button
                    :href="route('room-reservations.index')"
                    icon="bookmark-square"
                    wire:navigate
                >
                    Reservations
                </flux:button>

                <flux:button
                    :href="route('maintenances.index')"
                    icon="wrench-screwdriver"
                    wire:navigate
                >
                    Maintenance
                </flux:button>

                <flux:button
                    :href="route('rooms.create')"
                    variant="primary"
                    icon="plus"
                    wire:navigate
                >
                    Add Room
                </flux:button>
            </div>
        </header>

        {{-- Exam Period 状态与安全提醒 --}}
        @if ($examPeriodActive || $examPeriodUpcoming)
            <section
                @class([
                    'rounded-2xl border p-5',
                    'border-amber-500/30 bg-amber-500/10' =>
                        $examPeriodActive,
                    'border-blue-500/30 bg-blue-500/10' =>
                        $examPeriodUpcoming,
                ])
                role="status"
                aria-label="Exam Period status"
            >
                <div
                    class="flex flex-col gap-4
                           sm:flex-row sm:items-center
                           sm:justify-between"
                >
                    <div class="flex items-start gap-4">
                        <span
                            @class([
                                'grid size-11 shrink-0 place-items-center',
                                'rounded-xl text-lg font-bold',
                                'bg-amber-500/20 text-amber-700 dark:text-amber-300' =>
                                    $examPeriodActive,
                                'bg-blue-500/20 text-blue-700 dark:text-blue-300' =>
                                    $examPeriodUpcoming,
                            ])
                            aria-hidden="true"
                        >
                            {{ $examPeriodActive ? '!' : 'i' }}
                        </span>

                        <div>
                            <h2 class="font-bold text-zinc-900 dark:text-white">
                                {{ $examPeriodActive
                                    ? 'Exam Period is active'
                                    : 'Exam Period is scheduled' }}
                            </h2>

                            <p
                                class="mt-1 text-sm leading-6
                                       text-zinc-600 dark:text-zinc-300"
                            >
                                Library operating hours are

                                <strong>
                                    {{ $openingLabel }}
                                    –
                                    {{ $closingLabel }}

                                    @if ($closingNextDay)
                                        next day
                                    @endif
                                </strong>.

                                @if (
                                    $librarySetting->exam_period_starts_on
                                    && $librarySetting->exam_period_ends_on
                                )
                                    Schedule:

                                    <strong>
                                        {{ $librarySetting
                                            ->exam_period_starts_on
                                            ->format('d M Y') }}
                                        –
                                        {{ $librarySetting
                                            ->exam_period_ends_on
                                            ->format('d M Y') }}
                                    </strong>.
                                @endif
                            </p>

                            {{-- DashboardTest 需要的业务安全提示 --}}
                            @if ($examPeriodActive)
                                <p
                                    class="mt-2 text-sm font-medium
                                           text-amber-800
                                           dark:text-amber-200"
                                >
                                    Reservations after regular closing hours
                                    remain valid during this period.
                                </p>
                            @endif
                        </div>
                    </div>

                    <flux:button
                        :href="route('room-availability.index')"
                        variant="filled"
                        icon="calendar-days"
                        class="min-h-11 shrink-0"
                        wire:navigate
                    >
                        View Schedule
                    </flux:button>
                </div>
            </section>
        @endif

        {{-- 全馆即时统计 --}}
        <section
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
            aria-label="Library operations statistics"
        >
            @foreach ([
                [
                    'label' => 'Available Now',
                    'value' => $availableRooms,
                    'description' => 'Ready for student reservation.',
                    'symbol' => '✓',
                    'borderClass' => 'border-emerald-500/20',
                    'symbolClass' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
                    'valueClass' => 'text-emerald-600 dark:text-emerald-400',
                ],
                [
                    'label' => 'Reserved Now',
                    'value' => $reservedRooms,
                    'description' => 'Currently in a confirmed reservation.',
                    'symbol' => 'R',
                    'borderClass' => 'border-blue-500/20',
                    'symbolClass' => 'bg-blue-500/10 text-blue-600 dark:text-blue-400',
                    'valueClass' => 'text-blue-600 dark:text-blue-400',
                ],
                [
                    'label' => 'Maintenance',
                    'value' => $maintenanceRooms,
                    'description' => 'Active or scheduled maintenance work.',
                    'symbol' => 'M',
                    'borderClass' => 'border-amber-500/20',
                    'symbolClass' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
                    'valueClass' => 'text-amber-600 dark:text-amber-400',
                ],
                [
                    'label' => 'Unavailable',
                    'value' => $unavailableRooms,
                    'description' => 'Manually marked unavailable.',
                    'symbol' => '×',
                    'borderClass' => 'border-red-500/20',
                    'symbolClass' => 'bg-red-500/10 text-red-600 dark:text-red-400',
                    'valueClass' => 'text-red-600 dark:text-red-400',
                ],
            ] as $card)
                <article
                    class="rounded-2xl border bg-white p-5 shadow-sm
                           dark:bg-zinc-900 {{ $card['borderClass'] }}"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p
                                class="text-sm font-medium
                                       text-zinc-600 dark:text-zinc-400"
                            >
                                {{ $card['label'] }}
                            </p>

                            <p
                                class="mt-3 text-3xl font-bold
                                       {{ $card['valueClass'] }}"
                            >
                                {{ $card['value'] }}
                            </p>
                        </div>

                        <span
                            class="grid size-11 place-items-center rounded-xl
                                   text-lg font-bold {{ $card['symbolClass'] }}"
                            aria-hidden="true"
                        >
                            {{ $card['symbol'] }}
                        </span>
                    </div>

                    <p class="mt-4 text-sm text-zinc-500">
                        {{ $card['description'] }}
                    </p>
                </article>
            @endforeach
        </section>

        <div class="grid gap-6 xl:grid-cols-[1.25fr_0.75fr]">
            {{-- Upcoming Reservations --}}
            <section
                class="overflow-hidden rounded-2xl border
                       border-zinc-200 bg-white
                       dark:border-zinc-700 dark:bg-zinc-900"
            >
                <div
                    class="flex flex-wrap items-center justify-between
                           gap-4 border-b border-zinc-200 px-5 py-5
                           dark:border-zinc-700 sm:px-6"
                >
                    <div>
                        <div class="flex flex-wrap items-center gap-3">
                            <h2 class="font-bold text-zinc-900 dark:text-white">
                                Upcoming Reservations
                            </h2>

                            <flux:badge color="blue" size="sm">
                                {{ $upcomingReservationCount }}
                            </flux:badge>
                        </div>

                        <p
                            class="mt-1 text-sm
                                   text-zinc-600 dark:text-zinc-400"
                        >
                            Confirmed bookings that have not ended.
                        </p>
                    </div>

                    <a
                        href="{{ route('room-reservations.index') }}"
                        class="inline-flex min-h-11 items-center rounded-lg
                               px-3 text-sm font-semibold
                               text-blue-600 transition
                               hover:bg-blue-500/10 hover:text-blue-700
                               dark:text-blue-400 dark:hover:text-blue-300"
                        wire:navigate
                    >
                        Manage reservations
                    </a>
                </div>

                @forelse ($upcomingReservations as $reservation)
                    <article
                        class="grid gap-4 border-b border-zinc-200
                               px-5 py-4 last:border-b-0
                               dark:border-zinc-800
                               sm:grid-cols-[1fr_auto]
                               sm:items-center sm:px-6"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-3">
                                <p
                                    class="font-semibold
                                           text-zinc-900 dark:text-white"
                                >
                                    {{ $reservation->room?->room_number ?? 'Deleted room' }}
                                </p>

                                <flux:badge color="blue" size="sm">
                                    Confirmed
                                </flux:badge>
                            </div>

                            <p
                                class="mt-1 text-sm
                                       text-zinc-600 dark:text-zinc-400"
                            >
                                {{ $reservation->user?->name ?? 'Unknown user' }}
                                ·
                                {{ $reservation->starts_at->format('d M Y, g:i A') }}
                            </p>
                        </div>

                        <div class="text-start sm:text-end">
                            <p class="text-xs text-zinc-500">
                                Ends at
                            </p>

                            <p
                                class="mt-1 text-sm font-medium
                                       text-zinc-700 dark:text-zinc-300"
                            >
                                {{ $reservation->ends_at->format('g:i A') }}
                            </p>
                        </div>
                    </article>
                @empty
                    <div class="px-6 py-14 text-center">
                        <p class="font-semibold text-zinc-900 dark:text-white">
                            No upcoming reservations
                        </p>

                        <p
                            class="mt-2 text-sm
                                   text-zinc-600 dark:text-zinc-400"
                        >
                            Confirmed student bookings will appear here.
                        </p>

                        <flux:button
                            class="mt-6"
                            :href="route('room-reservations.index')"
                            icon="bookmark-square"
                            wire:navigate
                        >
                            View Reservations
                        </flux:button>
                    </div>
                @endforelse
            </section>

            <aside class="flex flex-col gap-6">
                {{-- Room status chart --}}
                <section
                    class="rounded-2xl border border-zinc-200
                           bg-white p-5
                           dark:border-zinc-700 dark:bg-zinc-900 sm:p-6"
                >
                    <h2 class="font-bold text-zinc-900 dark:text-white">
                        Current Room Status
                    </h2>

                    <p
                        class="mt-1 text-sm
                               text-zinc-600 dark:text-zinc-400"
                    >
                        Live room distribution across the library.
                    </p>

                    <div class="mt-6 space-y-5">
                        @foreach ([
                            [
                                'label' => 'Available',
                                'percentage' => $availablePercentage,
                                'textClass' => 'text-emerald-600 dark:text-emerald-400',
                                'barClass' => 'bg-emerald-500',
                            ],
                            [
                                'label' => 'Reserved',
                                'percentage' => $reservedPercentage,
                                'textClass' => 'text-blue-600 dark:text-blue-400',
                                'barClass' => 'bg-blue-500',
                            ],
                            [
                                'label' => 'Maintenance',
                                'percentage' => $maintenancePercentage,
                                'textClass' => 'text-amber-600 dark:text-amber-400',
                                'barClass' => 'bg-amber-500',
                            ],
                            [
                                'label' => 'Unavailable',
                                'percentage' => $unavailablePercentage,
                                'textClass' => 'text-red-600 dark:text-red-400',
                                'barClass' => 'bg-red-500',
                            ],
                        ] as $status)
                            <div>
                                <div
                                    class="mb-2 flex items-center
                                           justify-between text-sm"
                                >
                                    <span
                                        class="font-medium
                                               text-zinc-700 dark:text-zinc-300"
                                    >
                                        {{ $status['label'] }}
                                    </span>

                                    <span class="{{ $status['textClass'] }}">
                                        {{ $status['percentage'] }}%
                                    </span>
                                </div>

                                <div
                                    class="h-2 overflow-hidden rounded-full
                                           bg-zinc-200 dark:bg-zinc-800"
                                >
                                    <div
                                        class="h-full rounded-full
                                               {{ $status['barClass'] }}"
                                        @style([
                                            'width: '.$status['percentage'].'%',
                                        ])
                                    ></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                {{-- Maintenance Management 快速入口 --}}
                <section
                    class="rounded-2xl border border-amber-500/20
                           bg-amber-500/5 p-6"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p
                                class="text-sm font-bold uppercase
                                       tracking-wider text-amber-700
                                       dark:text-amber-400"
                            >
                                Maintenance
                            </p>

                            <h2
                                class="mt-2 text-xl font-bold
                                       text-zinc-900 dark:text-white"
                            >
                                {{ $upcomingMaintenanceCount }}
                                upcoming record{{ $upcomingMaintenanceCount === 1 ? '' : 's' }}
                            </h2>
                        </div>

                        <span
                            class="grid size-11 place-items-center rounded-xl
                                   bg-amber-500/10 text-lg font-bold
                                   text-amber-600 dark:text-amber-400"
                            aria-hidden="true"
                        >
                            M
                        </span>
                    </div>

                    <p
                        class="mt-3 text-sm leading-6
                               text-zinc-600 dark:text-zinc-400"
                    >
                        Review scheduled maintenance before it conflicts
                        with room reservations.
                    </p>

                    <flux:button
                        class="mt-5 min-h-11 w-full"
                        :href="route('maintenances.index')"
                        icon="wrench-screwdriver"
                        wire:navigate
                    >
                        Manage Maintenance
                    </flux:button>
                </section>
            </aside>
        </div>

        {{-- 最新 Room 资料变动 --}}
        <section
            class="overflow-hidden rounded-2xl border
                   border-zinc-200 bg-white
                   dark:border-zinc-700 dark:bg-zinc-900"
        >
            <div
                class="flex flex-wrap items-center justify-between
                       gap-4 border-b border-zinc-200 px-5 py-5
                       dark:border-zinc-700 sm:px-6"
            >
                <div>
                    <h2 class="font-bold text-zinc-900 dark:text-white">
                        Recent Room Changes
                    </h2>

                    <p
                        class="mt-1 text-sm
                               text-zinc-600 dark:text-zinc-400"
                    >
                        Latest Room Management updates.
                    </p>
                </div>

                <a
                    href="{{ route('rooms.index') }}"
                    class="inline-flex min-h-11 items-center rounded-lg
                           px-3 text-sm font-semibold
                           text-violet-600 transition
                           hover:bg-violet-500/10 hover:text-violet-700
                           dark:text-violet-400 dark:hover:text-violet-300"
                    wire:navigate
                >
                    Manage Rooms
                </a>
            </div>

            <div class="grid divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($recentRooms as $room)
                    @php
                        $statusColor = match ($room->status) {
                            'available' => 'green',
                            'unavailable' => 'red',
                            'maintenance' => 'amber',
                            default => 'zinc',
                        };

                        $statusLabel = match ($room->status) {
                            'maintenance' => 'Under Maintenance',
                            default => str($room->status)->headline(),
                        };
                    @endphp

                    <a
                        href="{{ route('rooms.show', $room) }}"
                        class="grid gap-4 px-5 py-4 transition
                               hover:bg-zinc-50
                               dark:hover:bg-zinc-800/70
                               sm:grid-cols-[1fr_auto]
                               sm:items-center sm:px-6"
                        wire:navigate
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-3">
                                <p
                                    class="font-semibold
                                           text-zinc-900 dark:text-white"
                                >
                                    {{ $room->room_number }}
                                </p>

                                <flux:badge
                                    :color="$statusColor"
                                    size="sm"
                                >
                                    {{ $statusLabel }}
                                </flux:badge>
                            </div>

                            <p
                                class="mt-1 truncate text-sm
                                       text-zinc-600 dark:text-zinc-400"
                            >
                                {{ str($room->name)->title() }}
                                · {{ str($room->location)->title() }}
                                · {{ $room->capacity }} people
                            </p>
                        </div>

                        <p
                            class="text-sm text-zinc-500
                                   sm:text-end"
                        >
                            Updated {{ $room->updated_at->diffForHumans() }}
                        </p>
                    </a>
                @empty
                    <div class="px-6 py-14 text-center">
                        <p class="font-semibold text-zinc-900 dark:text-white">
                            No rooms found
                        </p>

                        <p
                            class="mt-2 text-sm
                                   text-zinc-600 dark:text-zinc-400"
                        >
                            Create the first room to begin managing library spaces.
                        </p>

                        <flux:button
                            class="mt-6"
                            :href="route('rooms.create')"
                            variant="primary"
                            icon="plus"
                            wire:navigate
                        >
                            Add First Room
                        </flux:button>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts::app>
