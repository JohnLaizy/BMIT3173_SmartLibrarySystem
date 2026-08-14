<x-layouts::app :title="__('Room Reservations')">
    @php
        $isLibrarian = auth()->user()->isLibrarian();
    @endphp

    <div
        data-page-transition
        class="mx-auto flex w-full max-w-7xl flex-1
               flex-col gap-6 px-2 sm:px-4"
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

                    Reservation Records
                </div>

                <flux:heading size="xl" level="1">
                    {{ $isLibrarian
                        ? 'Room Reservations'
                        : 'My Reservations' }}
                </flux:heading>

                <flux:text class="mt-2 max-w-2xl">
                    @if ($isLibrarian)
                        View and manage upcoming room reservations
                        for all students.
                    @else
                        View your upcoming reservations and
                        previous reservation history.
                    @endif
                </flux:text>
            </div>

            <div class="flex flex-wrap gap-3">
                <flux:button
                    :href="route('room-availability.index')"
                    variant="ghost"
                    icon="calendar-days"
                    wire:navigate
                >
                    Availability
                </flux:button>

                <flux:button
                    :href="route('room-reservations.create')"
                    variant="primary"
                    icon="plus"
                    wire:navigate
                >
                    New Reservation
                </flux:button>
            </div>
        </header>

        {{-- 成功信息 --}}
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
                    class="flex size-6 items-center justify-center
                           rounded-full bg-emerald-500/20"
                    aria-hidden="true"
                >
                    ✓
                </span>

                {{ session('success') }}
            </div>
        @endif

        {{-- 即将到来的预约 --}}
        <section
            class="overflow-hidden rounded-2xl
                   border border-zinc-200 bg-white shadow-sm
                   dark:border-zinc-700 dark:bg-zinc-900"
        >
            <div
                class="flex flex-wrap items-center
                       justify-between gap-4
                       border-b border-zinc-200 px-6 py-5
                       dark:border-zinc-700"
            >
                <div>
                    <h2
                        class="text-lg font-bold
                               text-zinc-900 dark:text-white"
                    >
                        Upcoming Reservations
                    </h2>

                    <p
                        class="mt-1 text-sm
                               text-zinc-500 dark:text-zinc-400"
                    >
                        Confirmed reservations that have not ended.
                    </p>
                </div>

                <span
                    class="rounded-full bg-blue-500/10
                           px-3 py-1 text-sm font-semibold
                           text-blue-700 dark:text-blue-300"
                >
                    {{ $upcomingReservations->total() }}
                    {{ \Illuminate\Support\Str::plural(
                        'reservation',
                        $upcomingReservations->total()
                    ) }}
                </span>
            </div>

            @if ($upcomingReservations->isEmpty())
                <div class="px-6 py-16 text-center">
                    <div
                        class="mx-auto flex size-14 items-center
                               justify-center rounded-2xl
                               bg-blue-500/10 text-2xl
                               text-blue-500"
                        aria-hidden="true"
                    >
                        ◷
                    </div>

                    <h3
                        class="mt-4 font-bold
                               text-zinc-900 dark:text-white"
                    >
                        No upcoming reservations
                    </h3>

                    <p
                        class="mt-2 text-sm
                               text-zinc-500 dark:text-zinc-400"
                    >
                        Select an available room and time
                        to create a reservation.
                    </p>

                    <flux:button
                        :href="route('room-availability.index')"
                        variant="primary"
                        class="mt-5"
                        wire:navigate
                    >
                        View Availability
                    </flux:button>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[950px] text-sm">
                        <thead
                            class="bg-zinc-100 text-zinc-700
                                   dark:bg-zinc-800
                                   dark:text-zinc-200"
                        >
                            <tr>
                                <th class="px-6 py-4 text-left">
                                    Room
                                </th>

                                @if ($isLibrarian)
                                    <th class="px-6 py-4 text-left">
                                        Student
                                    </th>
                                @endif

                                <th class="px-6 py-4 text-left">
                                    Period
                                </th>

                                <th class="px-6 py-4 text-left">
                                    Purpose
                                </th>

                                <th class="px-6 py-4 text-left">
                                    Status
                                </th>

                                <th class="px-6 py-4 text-right">
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody
                            class="divide-y divide-zinc-200
                                   dark:divide-zinc-800"
                        >
                            @foreach ($upcomingReservations as $reservation)
                                <tr
                                    class="transition-colors
                                           hover:bg-zinc-500/5"
                                >
                                    <td class="px-6 py-5">
                                        <p
                                            class="font-bold
                                                   text-zinc-900
                                                   dark:text-white"
                                        >
                                            {{ $reservation->room->room_number }}
                                        </p>

                                        <p
                                            class="mt-1 text-sm
                                                   text-zinc-500
                                                   dark:text-zinc-400"
                                        >
                                            {{ $reservation->room->name }}
                                        </p>
                                    </td>

                                    @if ($isLibrarian)
                                        <td class="px-6 py-5">
                                            <p
                                                class="font-semibold
                                                       text-zinc-900
                                                       dark:text-white"
                                            >
                                                {{ $reservation->user->name }}
                                            </p>

                                            <p
                                                class="mt-1 text-sm
                                                       text-zinc-500
                                                       dark:text-zinc-400"
                                            >
                                                {{ $reservation->user->email }}
                                            </p>
                                        </td>
                                    @endif

                                    <td class="px-6 py-5">
                                        <p
                                            class="font-semibold
                                                   text-zinc-900
                                                   dark:text-white"
                                        >
                                            {{ $reservation->starts_at
                                                ->format('d M Y') }}
                                        </p>

                                        <p
                                            class="mt-1 text-sm
                                                   text-zinc-500
                                                   dark:text-zinc-400"
                                        >
                                            {{ $reservation->starts_at
                                                ->format('h:i A') }}

                                            –

                                            {{ $reservation->ends_at
                                                ->format('h:i A') }}
                                        </p>
                                    </td>

                                    <td
                                        class="max-w-64 px-6 py-5
                                               text-zinc-600
                                               dark:text-zinc-400"
                                    >
                                        {{ $reservation->purpose
                                            ?: 'No purpose provided' }}
                                    </td>

                                    <td class="px-6 py-5">
                                        <span
                                            class="inline-flex rounded-full
                                                   bg-emerald-500/10
                                                   px-3 py-1
                                                   text-xs font-bold
                                                   text-emerald-700
                                                   dark:text-emerald-300"
                                        >
                                            Confirmed
                                        </span>
                                    </td>

                                    <td class="px-6 py-5 text-right">
                                        @php
                                            $canUpdate = auth()
                                                ->user()
                                                ->can(
                                                    'update',
                                                    $reservation
                                                );

                                            $canCancel = auth()
                                                ->user()
                                                ->can(
                                                    'cancel',
                                                    $reservation
                                                );
                                        @endphp

                                        @if ($canUpdate || $canCancel)
                                            <div
                                                class="flex items-center
                                                       justify-end gap-2"
                                            >
                                                @if ($canUpdate)
                                                    <a
                                                        href="{{ route(
                                                            'room-reservations.edit',
                                                            $reservation
                                                        ) }}"
                                                        class="inline-flex min-h-10
                                                               items-center
                                                               justify-center
                                                               rounded-xl
                                                               border border-blue-500/30
                                                               bg-blue-500/10 px-4
                                                               font-semibold
                                                               text-blue-700
                                                               transition-colors
                                                               hover:bg-blue-500/20
                                                               focus:outline-none
                                                               focus:ring-2
                                                               focus:ring-blue-500
                                                               dark:text-blue-300"
                                                    >
                                                        Edit
                                                    </a>
                                                @endif

                                                @if ($canCancel)
                                                    <form
                                                        method="POST"
                                                        action="{{ route(
                                                            'room-reservations.cancel',
                                                            $reservation
                                                        ) }}"
                                                        class="inline"
                                                        onsubmit="return confirm(
                                                            'Cancel this reservation?'
                                                        )"
                                                    >
                                                        @csrf
                                                        @method('PATCH')

                                                        <button
                                                            type="submit"
                                                            class="min-h-10
                                                                   rounded-xl
                                                                   border
                                                                   border-red-500/30
                                                                   bg-red-500/10
                                                                   px-4
                                                                   font-semibold
                                                                   text-red-700
                                                                   transition-colors
                                                                   hover:bg-red-500/20
                                                                   focus:outline-none
                                                                   focus:ring-2
                                                                   focus:ring-red-500
                                                                   dark:text-red-300"
                                                        >
                                                            Cancel
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        @else
                                            <span
                                                class="text-sm text-zinc-400"
                                            >
                                                No actions
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($upcomingReservations->hasPages())
                    <div
                        class="border-t border-zinc-200
                               px-6 py-4 dark:border-zinc-700"
                    >
                        {{ $upcomingReservations->links() }}
                    </div>
                @endif
            @endif
        </section>

        {{-- 预约历史 --}}
        <section
            class="overflow-hidden rounded-2xl
                   border border-zinc-200 bg-white shadow-sm
                   dark:border-zinc-700 dark:bg-zinc-900"
        >
            <div
                class="flex flex-wrap items-center
                       justify-between gap-4
                       border-b border-zinc-200 px-6 py-5
                       dark:border-zinc-700"
            >
                <div>
                    <h2
                        class="text-lg font-bold
                               text-zinc-900 dark:text-white"
                    >
                        Reservation History
                    </h2>

                    <p
                        class="mt-1 text-sm
                               text-zinc-500 dark:text-zinc-400"
                    >
                        Completed and cancelled room reservations.
                    </p>
                </div>

                <span
                    class="rounded-full bg-zinc-100 px-3 py-1
                           text-sm font-semibold text-zinc-600
                           dark:bg-zinc-800 dark:text-zinc-300"
                >
                    {{ $reservationHistory->total() }}
                    {{ \Illuminate\Support\Str::plural(
                        'record',
                        $reservationHistory->total()
                    ) }}
                </span>
            </div>

            @if ($reservationHistory->isEmpty())
                <div class="px-6 py-12 text-center">
                    <p
                        class="font-semibold
                               text-zinc-900 dark:text-white"
                    >
                        No reservation history
                    </p>

                    <p
                        class="mt-2 text-sm
                               text-zinc-500 dark:text-zinc-400"
                    >
                        Completed or cancelled reservations
                        will appear here.
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[850px] text-sm">
                        <thead
                            class="bg-zinc-100 text-zinc-700
                                   dark:bg-zinc-800
                                   dark:text-zinc-200"
                        >
                            <tr>
                                <th class="px-6 py-4 text-left">
                                    Room
                                </th>

                                @if ($isLibrarian)
                                    <th class="px-6 py-4 text-left">
                                        Student
                                    </th>
                                @endif

                                <th class="px-6 py-4 text-left">
                                    Period
                                </th>

                                <th class="px-6 py-4 text-left">
                                    Purpose
                                </th>

                                <th class="px-6 py-4 text-left">
                                    Result
                                </th>
                            </tr>
                        </thead>

                        <tbody
                            class="divide-y divide-zinc-200
                                   dark:divide-zinc-800"
                        >
                            @foreach ($reservationHistory as $reservation)
                                @php
                                    $wasCancelled =
                                        $reservation->status === 'cancelled';
                                @endphp

                                <tr
                                    class="transition-colors
                                           hover:bg-zinc-500/5"
                                >
                                    <td class="px-6 py-5">
                                        <p
                                            class="font-bold
                                                   text-zinc-900
                                                   dark:text-white"
                                        >
                                            {{ $reservation->room->room_number }}
                                        </p>

                                        <p
                                            class="mt-1 text-zinc-500
                                                   dark:text-zinc-400"
                                        >
                                            {{ $reservation->room->name }}
                                        </p>
                                    </td>

                                    @if ($isLibrarian)
                                        <td class="px-6 py-5">
                                            <p
                                                class="font-semibold
                                                       text-zinc-900
                                                       dark:text-white"
                                            >
                                                {{ $reservation->user->name }}
                                            </p>

                                            <p
                                                class="mt-1 text-zinc-500
                                                       dark:text-zinc-400"
                                            >
                                                {{ $reservation->user->email }}
                                            </p>
                                        </td>
                                    @endif

                                    <td class="px-6 py-5">
                                        <p
                                            class="font-semibold
                                                   text-zinc-900
                                                   dark:text-white"
                                        >
                                            {{ $reservation->starts_at
                                                ->format('d M Y') }}
                                        </p>

                                        <p
                                            class="mt-1 text-zinc-500
                                                   dark:text-zinc-400"
                                        >
                                            {{ $reservation->starts_at
                                                ->format('h:i A') }}

                                            –

                                            {{ $reservation->ends_at
                                                ->format('h:i A') }}
                                        </p>
                                    </td>

                                    <td
                                        class="max-w-64 px-6 py-5
                                               text-zinc-600
                                               dark:text-zinc-400"
                                    >
                                        {{ $reservation->purpose
                                            ?: 'No purpose provided' }}
                                    </td>

                                    <td class="px-6 py-5">
                                        <span @class([
                                            'inline-flex rounded-full px-3 py-1 text-xs font-bold',
                                            'bg-red-500/10 text-red-700 dark:text-red-300' =>
                                                $wasCancelled,
                                            'bg-zinc-500/10 text-zinc-700 dark:text-zinc-300' =>
                                                !$wasCancelled,
                                        ])>
                                            {{ $wasCancelled
                                                ? 'Cancelled'
                                                : 'Completed' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($reservationHistory->hasPages())
                    <div
                        class="border-t border-zinc-200
                               px-6 py-4 dark:border-zinc-700"
                    >
                        {{ $reservationHistory->links() }}
                    </div>
                @endif
            @endif
        </section>
    </div>
</x-layouts::app>