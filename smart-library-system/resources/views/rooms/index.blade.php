<x-layouts::app :title="__('Room Management')">
    <div class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-8 px-2 sm:px-4">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <flux:heading size="xl" level="1">
                    Room Management
                </flux:heading>

                <flux:text class="mt-2">
                    Manage library rooms, capacity and availability.
                </flux:text>
            </div>

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

        @if (session('success'))
            <div
                role="status"
                class="rounded-lg border border-green-200 bg-green-50
                       px-4 py-3 text-sm text-green-800
                       dark:border-green-800 dark:bg-green-950
                       dark:text-green-200"
            >
                {{ session('success') }}
            </div>
        @endif

        @if ($rooms->isEmpty())
            <div
                class="rounded-xl border border-dashed border-zinc-300
                       px-6 py-16 text-center dark:border-zinc-700"
            >
                <flux:heading>No rooms found</flux:heading>

                <flux:text class="mt-2">
                    Create your first room to get started.
                </flux:text>
            
            @can('create', \App\Models\Room::class)
                <flux:button
                    class="mt-6"
                    :href="route('rooms.create')"
                    variant="primary"
                >
                    Add Room
                </flux:button>
            @endcan
            </div>
        @else
            <div
                class="rounded-xl border border-zinc-200 bg-white p-5
                       dark:border-zinc-700 dark:bg-zinc-900"
            >
                <flux:table>
                   <flux:table.columns>
                        <flux:table.column class="w-64">
                            <span class="ps-4">Room</span>
                        </flux:table.column>

                        <flux:table.column class="w-28">
                            Type
                        </flux:table.column>

                        <flux:table.column class="w-24">
                            Capacity
                        </flux:table.column>

                        <flux:table.column class="w-44">
                            Location
                        </flux:table.column>

                        <flux:table.column class="w-32">
                            Status
                        </flux:table.column>

                        <flux:table.column class="w-32" align="end">
                            <span class="pe-4">Actions</span>
                        </flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($rooms as $room)
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
                            <flux:table.row :key="$room->id">
                                <flux:table.cell variant="strong">
                                    <div class="ps-4">
                                        <div>{{ $room->room_number }}</div>

                                        <div class="max-w-52 truncate text-sm font-medium font-normal text-zinc-500">
                                            {{ str($room->name)->title() }}
                                        </div>
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ ucfirst($room->type) }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ $room->capacity }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ str($room->location)->title() }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:badge
                                        :color="$statusColor"
                                        size="sm"
                                    >
                                        {{ $statusLabel }}
                                    </flux:badge>
                                </flux:table.cell>

                                <flux:table.cell align="end">
                                    <div class="flex justify-end gap-1 pe-4">
                                        <flux:button
                                            :href="route('rooms.show', $room)"
                                            size="sm"
                                            variant="ghost"
                                            icon="eye"
                                            tooltip="View room"
                                        />
                                        @can('update', $room)
                                        <flux:button
                                            :href="route('rooms.edit', $room)"
                                            size="sm"
                                            variant="ghost"
                                            icon="pencil"
                                            tooltip="Edit room"
                                        />
                                        @endcan

                                        @can('delete', $room)
                                        <form
                                            method="POST"
                                            action="{{ route('rooms.destroy', $room) }}"
                                            onsubmit="return confirm(
                                                'Are you sure you want to delete this room?'
                                            )"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <flux:button
                                                type="submit"
                                                size="sm"
                                                variant="danger"
                                                icon="trash"
                                                tooltip="Delete room"
                                            />
                                        </form>
                                        @endcan
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
                    <div
    class="flex flex-col gap-3 border-t border-zinc-200
           px-4 py-3 sm:flex-row sm:items-center
           sm:justify-between dark:border-zinc-700"
>
    <p class="text-xs text-zinc-500 dark:text-zinc-400">
        Showing {{ $rooms->firstItem() }}
        to {{ $rooms->lastItem() }}
        of {{ $rooms->total() }} results
    </p>

    @if ($rooms->hasPages())
        @php
            $startPage = max(1, $rooms->currentPage() - 1);
            $endPage = min(
                $rooms->lastPage(),
                $rooms->currentPage() + 1
            );
        @endphp

        <nav
            class="flex items-center gap-1"
            aria-label="Room pagination"
        >
            @if ($rooms->onFirstPage())
                <span
                    class="grid size-11 place-items-center rounded-lg
                           text-lg text-zinc-400 opacity-40 sm:size-10"
                    aria-disabled="true"
                >
                    ‹
                </span>
            @else
                <a
                    href="{{ $rooms->previousPageUrl() }}"
                    class="grid size-11 place-items-center rounded-lg
                           text-lg text-zinc-500 hover:bg-zinc-100
                           focus-visible:outline-2
                           focus-visible:outline-offset-2
                           focus-visible:outline-blue-500 sm:size-10
                           dark:text-zinc-400 dark:hover:bg-zinc-800"
                    aria-label="Previous page"
                    rel="prev"
                >
                    ‹
                </a>
            @endif

            @foreach ($rooms->getUrlRange($startPage, $endPage) as $page => $url)
                <a
                    href="{{ $url }}"
                    @if ($page === $rooms->currentPage())
                        aria-current="page"
                    @endif
                    class="grid size-11 place-items-center rounded-lg
                           text-sm font-medium sm:size-10
                           focus-visible:outline-2
                           focus-visible:outline-offset-2
                           focus-visible:outline-blue-500
                           {{ $page === $rooms->currentPage()
                               ? 'bg-zinc-200 text-zinc-900 dark:bg-zinc-700 dark:text-white'
                               : 'text-zinc-500 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800' }}"
                >
                    {{ $page }}
                </a>
            @endforeach

            @if ($rooms->hasMorePages())
                <a
                    href="{{ $rooms->nextPageUrl() }}"
                    class="grid size-11 place-items-center rounded-lg
                           text-lg text-zinc-500 hover:bg-zinc-100
                           focus-visible:outline-2
                           focus-visible:outline-offset-2
                           focus-visible:outline-blue-500 sm:size-10
                           dark:text-zinc-400 dark:hover:bg-zinc-800"
                    aria-label="Next page"
                    rel="next"
                >
                    ›
                </a>
            @else
                <span
                    class="grid size-11 place-items-center rounded-lg
                           text-lg text-zinc-400 opacity-40 sm:size-10"
                    aria-disabled="true"
                >
                    ›
                </span>
            @endif
        </nav>
    @endif
</div>
            </div>
        @endif
    </div>
</x-layouts::app>