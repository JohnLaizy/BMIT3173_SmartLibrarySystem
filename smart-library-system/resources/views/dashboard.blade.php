<x-layouts::app :title="__('Dashboard')">
    @php
        $totalRooms = $roomStats['total'];

        $availablePercentage = $totalRooms > 0
            ? round(($roomStats['available'] / $totalRooms) * 100)
            : 0;

        $unavailablePercentage = $totalRooms > 0
            ? round(($roomStats['unavailable'] / $totalRooms) * 100)
            : 0;

        $maintenancePercentage = $totalRooms > 0
            ? round(($roomStats['maintenance'] / $totalRooms) * 100)
            : 0;

        $greeting = match (true) {
            now()->hour < 12 => 'Good morning',
            now()->hour < 18 => 'Good afternoon',
            default => 'Good evening',
        };
    @endphp

    <div
        data-page-transition
        class="mx-auto flex w-full max-w-6xl flex-1
              flex-col gap-8 px-2 sm:px-4"
    >
        <!-- Page Header -->
        <header
            class="flex flex-col justify-between gap-5
                   sm:flex-row sm:items-center"
        >
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <flux:heading size="xl" level="1">
                        {{ $greeting }}, {{ auth()->user()->name }}
                    </flux:heading>

                    <span
                        class="rounded-full border border-emerald-500/30
                               bg-emerald-500/10 px-3 py-1
                               text-xs font-semibold uppercase
                               tracking-wide text-emerald-700 dark:text-emerald-300"
                    >
                        {{ auth()->user()->isLibrarian()
                            ? 'Librarian'
                            : 'Student' }}
                    </span>
                </div>

                <flux:text class="mt-2">
                    Here is the latest overview of library rooms.
                </flux:text>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <flux:button
                    :href="route('rooms.index')"
                    icon="building-office-2"
                >
                    View Rooms
                </flux:button>

                @can('create', \App\Models\Room::class)
                    <flux:button
                        :href="route('rooms.create')"
                        variant="primary"
                        icon="plus"
                    >
                        Add Room
                    </flux:button>
                @endcan
            </div>
        </header>

        <!-- Statistics -->
        <section
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
            aria-label="Room statistics"
        >
            <!-- Total Rooms -->
            <article
                class="rounded-2xl border border-zinc-200 dark:border-zinc-700
                       bg-white dark:bg-zinc-900 p-5 shadow-sm"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p
                            class="text-sm font-medium text-zinc-600 dark:text-zinc-400"
                        >
                            Total Rooms
                        </p>

                        <p
                            class="mt-3 text-3xl font-bold text-zinc-900 dark:text-white"
                        >
                            {{ $roomStats['total'] }}
                        </p>
                    </div>

                    <span
                        class="grid size-11 place-items-center
                               rounded-xl bg-sky-500/10
                               text-lg font-bold text-sky-600 dark:text-sky-400"
                    >
                        #
                    </span>
                </div>

                <p class="mt-4 text-sm text-zinc-500">
                    Registered library rooms
                </p>
            </article>

            <!-- Available -->
            <article
                class="rounded-2xl border border-emerald-500/20
                       bg-white dark:bg-zinc-900 p-5 shadow-sm"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">
                            Available
                        </p>

                        <p
                            class="mt-3 text-3xl font-bold
                                   text-emerald-600 dark:text-emerald-400"
                        >
                            {{ $roomStats['available'] }}
                        </p>
                    </div>

                    <span
                        class="grid size-11 place-items-center
                               rounded-xl bg-emerald-500/10
                               text-lg font-bold text-emerald-600 dark:text-emerald-400"
                    >
                        ✓
                    </span>
                </div>

                <p class="mt-4 text-sm text-zinc-500">
                    Ready for student use
                </p>
            </article>

            <!-- Unavailable -->
            <article
                class="rounded-2xl border border-red-500/20
                       bg-white dark:bg-zinc-900 p-5 shadow-sm"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">
                            Unavailable
                        </p>

                        <p
                            class="mt-3 text-3xl font-bold text-red-600 dark:text-red-400"
                        >
                            {{ $roomStats['unavailable'] }}
                        </p>
                    </div>

                    <span
                        class="grid size-11 place-items-center
                               rounded-xl bg-red-500/10
                               text-lg font-bold text-red-600 dark:text-red-400"
                    >
                        ×
                    </span>
                </div>

                <p class="mt-4 text-sm text-zinc-500">
                    Currently unavailable
                </p>
            </article>

            <!-- Maintenance -->
            <article
                class="rounded-2xl border border-amber-500/20
                       bg-white dark:bg-zinc-900 p-5 shadow-sm"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">
                            Maintenance
                        </p>

                        <p
                            class="mt-3 text-3xl font-bold
                                   text-amber-600 dark:text-amber-400"
                        >
                            {{ $roomStats['maintenance'] }}
                        </p>
                    </div>

                    <span
                        class="grid size-11 place-items-center
                               rounded-xl bg-amber-500/10
                               text-lg font-bold text-amber-600 dark:text-amber-400"
                    >
                        !
                    </span>
                </div>

                <p class="mt-4 text-sm text-zinc-500">
                    Under maintenance
                </p>
            </article>
        </section>

        <!-- Main Dashboard Content -->
        <div class="grid gap-6 lg:grid-cols-[1.45fr_0.75fr]">
            <!-- Recently Updated Rooms -->
            <section
                class="overflow-hidden rounded-2xl border
                       border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900"
            >
                <div
                    class="flex items-center justify-between gap-4
                           border-b border-zinc-200 dark:border-zinc-700 px-5 py-5 sm:px-6"
                >
                    <div>
                        <h2 class="font-bold text-zinc-900 dark:text-white">
                            Recently Updated Rooms
                        </h2>

                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                            Latest changes from Room Management.
                        </p>
                    </div>

                    <a
                        href="{{ route('rooms.index') }}"
                        class="inline-flex min-h-11 items-center
                               rounded-lg px-3 text-sm font-semibold
                               text-emerald-600 dark:text-emerald-400 transition
                               hover:bg-emerald-500/10
                               hover:text-emerald-700 dark:hover:text-emerald-300"
                    >
                        View all
                    </a>
                </div>

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
                        class="grid gap-4 border-b border-zinc-200 dark:border-zinc-800
                               px-5 py-4 transition last:border-b-0
                               hover:bg-zinc-50 dark:hover:bg-zinc-800/70
                               sm:grid-cols-[1fr_auto]
                               sm:items-center sm:px-6"
                    >
                        <div class="min-w-0">
                            <div
                                class="flex flex-wrap items-center gap-3"
                            >
                                <p class="font-semibold text-zinc-900 dark:text-white">
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

                        <div class="text-start sm:text-end">
                            <p class="text-xs text-zinc-500">
                                Updated
                            </p>

                            <p class="mt-1 text-sm text-zinc-700 dark:text-zinc-300">
                                {{ $room->updated_at->diffForHumans() }}
                            </p>
                        </div>
                    </a>
                @empty
                    <div class="px-6 py-14 text-center">
                        <p class="font-semibold text-zinc-900 dark:text-white">
                            No rooms found
                        </p>

                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                            Room records will appear here after they
                            are created.
                        </p>

                        @can('create', \App\Models\Room::class)
                            <flux:button
                                class="mt-6"
                                :href="route('rooms.create')"
                                variant="primary"
                                icon="plus"
                            >
                                Add First Room
                            </flux:button>
                        @endcan
                    </div>
                @endforelse
            </section>

            <!-- Right column -->
            <aside class="flex flex-col gap-6">
                <!-- Status Overview -->
                <section
                    class="rounded-2xl border border-zinc-200 dark:border-zinc-700
                           bg-white dark:bg-zinc-900 p-5 sm:p-6"
                >
                    <h2 class="font-bold text-zinc-900 dark:text-white">
                        Status Overview
                    </h2>

                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        Current room distribution.
                    </p>

                    <div class="mt-6 space-y-6">
                        <!-- Available -->
                        <div>
                            <div
                                class="mb-2 flex items-center
                                       justify-between text-sm"
                            >
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">
                                    Available
                                </span>

                                <span class="text-emerald-600 dark:text-emerald-400">
                                    {{ $availablePercentage }}%
                                </span>
                            </div>

                            <div
                                class="h-2 overflow-hidden rounded-full
                                       bg-zinc-200 dark:bg-zinc-800"
                            >
                                <div
                                    class="h-full rounded-full
                                           bg-emerald-500"
                                    @style(['width: '.$availablePercentage.'%'])
                                ></div>
                            </div>
                        </div>

                        <!-- Unavailable -->
                        <div>
                            <div
                                class="mb-2 flex items-center
                                       justify-between text-sm"
                            >
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">
                                    Unavailable
                                </span>

                                <span class="text-red-600 dark:text-red-400">
                                    {{ $unavailablePercentage }}%
                                </span>
                            </div>

                            <div
                                class="h-2 overflow-hidden rounded-full
                                       bg-zinc-200 dark:bg-zinc-800"
                            >
                                <div
                                    class="h-full rounded-full bg-red-500"
                                    @style(['width: '.$unavailablePercentage.'%'])
                                ></div>
                            </div>
                        </div>

                        <!-- Maintenance -->
                        <div>
                            <div
                                class="mb-2 flex items-center
                                       justify-between text-sm"
                            >
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">
                                    Maintenance
                                </span>

                                <span class="text-amber-600 dark:text-amber-400">
                                    {{ $maintenancePercentage }}%
                                </span>
                            </div>

                            <div
                                class="h-2 overflow-hidden rounded-full
                                       bg-zinc-200 dark:bg-zinc-800"
                            >
                                <div
                                    class="h-full rounded-full
                                           bg-amber-500"
                                    @style(['width: '.$maintenancePercentage.'%'])
                                ></div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Quick Access -->
                <section
                    class="rounded-2xl border border-emerald-200
                           bg-emerald-50/70 p-6 shadow-sm
                           dark:border-emerald-500/20
                           dark:bg-emerald-500/5 sm:p-7"
                >
                    <p
                        class="text-sm font-bold uppercase tracking-wider
                               text-emerald-700 dark:text-emerald-400"
                    >
                        Quick Access
                    </p>

                    @if (auth()->user()->isLibrarian())
                        <h2
                            class="mt-3 text-xl font-bold
                                   text-zinc-900 dark:text-white"
                        >
                            Manage library rooms
                        </h2>

                        <p
                            class="mt-3 max-w-sm text-sm leading-6
                                   text-zinc-600 dark:text-zinc-400"
                        >
                            Add new rooms or update room status,
                            capacity and facilities.
                        </p>

                        <flux:button
                            class="mt-6 min-h-11 w-full"
                            :href="route('rooms.create')"
                            variant="primary"
                            icon="plus"
                            wire:navigate.hover
                        >
                            Add Room
                        </flux:button>
                    @else
                        <h2
                            class="mt-3 text-xl font-bold
                                   text-zinc-900 dark:text-white"
                        >
                            Find an available room
                        </h2>

                        <p
                            class="mt-3 max-w-sm text-sm leading-6
                                   text-zinc-600 dark:text-zinc-400"
                        >
                            View room capacity, location, status
                            and available facilities.
                        </p>

                        <flux:button
                            class="mt-6 min-h-11 w-full"
                            :href="route('rooms.index')"
                            variant="primary"
                            icon="building-office-2"
                            wire:navigate.hover
                        >
                            Browse Rooms
                        </flux:button>
                    @endif
                </section>
            </aside>
        </div>
    </div>
</x-layouts::app>

