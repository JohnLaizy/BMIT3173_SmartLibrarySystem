<x-layouts::app :title="__('Student Dashboard')">
    @php
        /*
        |--------------------------------------------------------------------------
        | Student Dashboard 数值准备
        |--------------------------------------------------------------------------
        |
        | 全部数字由 RoomDashboardController 提供。
        | 页面不自行查询数据库，也没有 Hard Code 房间数量。
        */

        $user = auth()->user();

        $totalRooms = (int) $roomStats['total'];

        $availableRooms = (int) $roomStats['available'];

        $reservedRooms = (int) $roomStats['reserved'];

        $myReservationCount = $upcomingReservations->count();

        /*
         * 如果系统没有 Room，
         * 避免 division by zero。
         */
        $availabilityPercentage = $totalRooms > 0
            ? round(($availableRooms / $totalRooms) * 100)
            : 0;

        /*
         * 根据现在的时间显示 greeting。
         */
        $greeting = match (true) {
            now()->hour < 12 => 'Good morning',
            now()->hour < 18 => 'Good afternoon',
            default => 'Good evening',
        };

        /*
         * 从 LibrarySetting 读取当天营业时间。
         * regular / exam period 都由 Model 自动决定。
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
    @endphp

    <div
        data-page-transition
        class="mx-auto flex w-full max-w-5xl flex-1
               flex-col gap-6 px-3 py-1 sm:px-5"
    >
        {{-- 页面标题与主要操作 --}}
        <header
            class="flex flex-col gap-5
                   sm:flex-row sm:items-center
                   sm:justify-between"
        >
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <flux:heading size="xl" level="1">
                        {{ $greeting }}, {{ $user->name }}
                    </flux:heading>

                    <span
                        class="rounded-full border border-blue-500/30
                               bg-blue-500/10 px-3 py-1
                               text-xs font-semibold uppercase
                               tracking-wide text-blue-700
                               dark:text-blue-300"
                    >
                        Student
                    </span>
                </div>

                <flux:text class="mt-2">
                    Check available study spaces and manage your room reservations.
                </flux:text>
            </div>

            <div class="flex flex-wrap gap-3">
                <flux:button
                    :href="route('room-availability.index')"
                    icon="calendar-days"
                    wire:navigate
                >
                    Browse Availability
                </flux:button>

                <flux:button
                    :href="route('room-reservations.index')"
                    variant="primary"
                    icon="bookmark-square"
                    wire:navigate
                >
                    My Reservations
                </flux:button>
            </div>
        </header>

        {{-- Exam Period / Opening Hours 提示 --}}
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
            >
                <div class="flex gap-4">
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
                            Library opening hours are

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
                                The Exam Period schedule applies from

                                <strong>
                                    {{ $librarySetting
                                        ->exam_period_starts_on
                                        ->format('d M Y') }}
                                </strong>

                                until

                                <strong>
                                    {{ $librarySetting
                                        ->exam_period_ends_on
                                        ->format('d M Y') }}.
                                </strong>
                            @endif
                        </p>
                        @if ($examPeriodActive)
    <p
        class="mt-2 text-sm font-medium
               text-amber-800 dark:text-amber-200"
    >
        Reservations after regular closing hours
        remain valid during this period.
    </p>
@endif
                    </div>
                </div>
            </section>
        @endif

        {{-- Student 重点统计 --}}
        <section
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
            aria-label="Student room overview"
        >
            {{-- Available Rooms --}}
            <article
                class="rounded-2xl border border-emerald-500/20
                       bg-white p-5 shadow-sm
                       dark:bg-zinc-900"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p
                            class="text-sm font-medium
                                   text-zinc-600 dark:text-zinc-400"
                        >
                            Available Rooms
                        </p>

                        <p
                            class="mt-3 text-3xl font-bold
                                   text-emerald-600 dark:text-emerald-400"
                        >
                            {{ $availableRooms }}
                        </p>
                    </div>

                    <span
                        class="grid size-11 place-items-center rounded-xl
                               bg-emerald-500/10 text-lg font-bold
                               text-emerald-600 dark:text-emerald-400"
                        aria-hidden="true"
                    >
                        ✓
                    </span>
                </div>

                <p class="mt-4 text-sm text-zinc-500">
                    {{ $availabilityPercentage }}% of library rooms are available now.
                </p>
            </article>

            {{-- My Reservations --}}
            <article
                class="rounded-2xl border border-blue-500/20
                       bg-white p-5 shadow-sm
                       dark:bg-zinc-900"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p
                            class="text-sm font-medium
                                   text-zinc-600 dark:text-zinc-400"
                        >
                            My Upcoming Reservations
                        </p>

                        <p
                            class="mt-3 text-3xl font-bold
                                   text-blue-600 dark:text-blue-400"
                        >
                            {{ $myReservationCount }}
                        </p>
                    </div>

                    <span
                        class="grid size-11 place-items-center rounded-xl
                               bg-blue-500/10 text-lg font-bold
                               text-blue-600 dark:text-blue-400"
                        aria-hidden="true"
                    >
                        R
                    </span>
                </div>

                <p class="mt-4 text-sm text-zinc-500">
                    Confirmed room bookings that have not ended.
                </p>
            </article>

            {{-- Library Hours --}}
            <article
                class="rounded-2xl border border-amber-500/20
                       bg-white p-5 shadow-sm
                       dark:bg-zinc-900 sm:col-span-2 lg:col-span-1"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p
                            class="text-sm font-medium
                                   text-zinc-600 dark:text-zinc-400"
                        >
                            Library Hours Today
                        </p>

                        <p
                            class="mt-3 text-xl font-bold
                                   text-zinc-900 dark:text-white"
                        >
                            {{ $openingLabel }} – {{ $closingLabel }}
                        </p>
                    </div>

                    <span
                        class="grid size-11 place-items-center rounded-xl
                               bg-amber-500/10 text-lg font-bold
                               text-amber-600 dark:text-amber-400"
                        aria-hidden="true"
                    >
                        ◷
                    </span>
                </div>

                <p class="mt-4 text-sm text-zinc-500">
                    @if ($closingNextDay)
                        Closing time is on the following day.
                    @elseif ($examPeriodActive)
                        Exam Period operating hours are active.
                    @else
                        Regular library operating hours.
                    @endif
                </p>
            </article>
        </section>

        <div class="grid gap-6 lg:grid-cols-[1.25fr_0.75fr]">
            {{-- Student 自己的 upcoming reservations --}}
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
                            My Upcoming Reservations
                        </h2>

                        <p
                            class="mt-1 text-sm
                                   text-zinc-600 dark:text-zinc-400"
                        >
                            Your confirmed room reservations.
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
                        View all
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
                                    {{ $reservation->room->room_number }}
                                </p>

                                <flux:badge color="blue" size="sm">
                                    Confirmed
                                </flux:badge>
                            </div>

                            <p
                                class="mt-1 text-sm
                                       text-zinc-600 dark:text-zinc-400"
                            >
                                {{ str($reservation->room->name)->title() }}
                                · {{ $reservation->starts_at->format('d M Y, g:i A') }}
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
                            Browse the room schedule to find an available time slot.
                        </p>

                        <flux:button
                            class="mt-6"
                            :href="route('room-availability.index')"
                            variant="primary"
                            icon="calendar-days"
                            wire:navigate
                        >
                            Browse Availability
                        </flux:button>
                    </div>
                @endforelse
            </section>

            {{-- Student Quick Actions --}}
            <aside class="flex flex-col gap-6">
                <section
                    class="rounded-2xl border border-blue-500/20
                           bg-blue-500/5 p-6
                           dark:bg-blue-500/5"
                >
                    <p
                        class="text-sm font-bold uppercase tracking-wider
                               text-blue-700 dark:text-blue-400"
                    >
                        Quick Access
                    </p>

                    <h2
                        class="mt-3 text-xl font-bold
                               text-zinc-900 dark:text-white"
                    >
                        Find a study space
                    </h2>

                    <p
                        class="mt-3 text-sm leading-6
                               text-zinc-600 dark:text-zinc-400"
                    >
                        View room capacity, facilities, current availability
                        and reservation time slots.
                    </p>

                    <flux:button
                        class="mt-6 min-h-11 w-full"
                        :href="route('room-availability.index')"
                        variant="primary"
                        icon="calendar-days"
                        wire:navigate
                    >
                        Browse Rooms
                    </flux:button>
                </section>

                <section
                    class="rounded-2xl border border-zinc-200
                           bg-white p-6
                           dark:border-zinc-700 dark:bg-zinc-900"
                >
                    <h2 class="font-bold text-zinc-900 dark:text-white">
                        Reservation reminder
                    </h2>

                    <p
                        class="mt-2 text-sm leading-6
                               text-zinc-600 dark:text-zinc-400"
                    >
                        Select an available time slot from Room Availability
                        before creating a reservation.
                    </p>

                    <a
                        href="{{ route('room-reservations.index') }}"
                        class="mt-4 inline-flex min-h-11 items-center
                               text-sm font-semibold text-blue-600
                               transition hover:text-blue-700
                               dark:text-blue-400 dark:hover:text-blue-300"
                        wire:navigate
                    >
                        Manage my reservations →
                    </a>
                </section>
            </aside>
        </div>
    </div>
</x-layouts::app>