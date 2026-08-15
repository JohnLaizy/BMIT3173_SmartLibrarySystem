<x-layouts::app :title="__('Room Management')">
    <div
         data-page-transition
         class="mx-auto flex w-full max-w-6xl flex-1
             flex-col gap-8 px-2 sm:px-4"
    >
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

       @if (session('error'))
    {{-- 删除失败提示：保留预约历史，不能删除有关联预约的房间 --}}
    <div
        role="alert"
        class="rounded-xl border border-red-500/30
               bg-red-500/10 px-4 py-4
               text-red-800 dark:text-red-200"
    >
        <div class="flex items-start gap-3">
            {{-- 红色警告图示 --}}
            <div
                class="flex size-6 shrink-0 items-center
                       justify-center rounded-full
                       bg-red-500/20 text-sm font-bold
                       text-red-700 dark:text-red-300"
                aria-hidden="true"
            >
                !
            </div>

            <div>
                <p class="font-semibold">
                    Room cannot be deleted
                </p>

                <p class="mt-1 text-sm leading-6
                          text-red-700 dark:text-red-300">
                    {{ session('error') }}
                </p>
            </div>
        </div>
    </div>
        @endif

        @php
            /*
             * 所有筛选值都来自 RoomController；不把楼层、设施或人数写死。
             * Capacity 只有在 Floor + Facility 已选择时才开放，避免显示无效人数。
             */
            $selectedFacilities = $filters['facilities'] ?? [];
            $selectedFacilityLabel = collect($selectedFacilities)
                ->map(
                    fn (string $facility): string => (string) str($facility)
                        ->replace('_', ' ')
                        ->title()
                )
                ->implode(', ');
            $canChooseCapacity = filled($filters['location'] ?? null)
                && $selectedFacilities !== [];
        @endphp

        <section
            class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm
                   dark:border-zinc-700 dark:bg-zinc-900"
        >
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-semibold text-zinc-900 dark:text-white">
                        Find a room
                    </h2>

                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        Results update automatically after each selection.
                    </p>
                </div>

                <flux:button
                    :href="route('rooms.index')"
                    size="sm"
                    variant="ghost"
                    icon="arrow-path"
                    class="min-h-10 w-full sm:w-auto"
                    wire:navigate
                >
                    Reset filters
                </flux:button>
            </div>

            <form
                method="GET"
                action="{{ route('rooms.index') }}"
                data-room-management-filter-form
                data-location-types='@json($locationTypes)'
                data-type-locations='@json($typeLocations)'
                class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6 xl:items-end"
            >
                <div class="xl:col-span-2">
                    <label
                        for="room-search"
                        class="mb-1 block text-sm font-semibold text-zinc-700 dark:text-zinc-200"
                    >
                        Search rooms
                    </label>

                    <input
                        id="room-search"
                        name="search"
                        type="search"
                        data-room-management-filter-input
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="Room number or name"
                        class="min-h-11 w-full rounded-xl border border-zinc-300 bg-white px-3
                               text-sm outline-none placeholder:text-zinc-400 focus:border-blue-500
                               focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700
                               dark:bg-zinc-800 dark:text-white"
                    >
                </div>

                <div>
                    <label for="room-location" class="mb-1 block text-sm font-semibold text-zinc-700 dark:text-zinc-200">
                        Floor / location
                    </label>

                    <div class="relative">
                        <select
                            id="room-location"
                            name="location"
                            data-room-management-filter-select
                            class="min-h-11 w-full appearance-none rounded-xl border border-zinc-300 bg-white px-3 pe-11 text-sm
                                   dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                        >
                            <option value="">All locations</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location }}" @selected(($filters['location'] ?? '') === $location)>
                                    {{ str($location)->title() }}
                                </option>
                            @endforeach
                        </select>

                        <svg class="pointer-events-none absolute end-4 top-1/2 size-4 -translate-y-1/2 text-zinc-500 dark:text-zinc-400" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="m5 7 5 5 5-5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" />
                        </svg>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-zinc-700 dark:text-zinc-200">
                        Facility
                    </label>

                    <details class="group relative">
                        <summary class="flex min-h-11 w-full cursor-pointer list-none items-center justify-between gap-3 rounded-xl border border-zinc-300 bg-white px-3 text-sm text-zinc-900 outline-none transition hover:border-zinc-400 focus-visible:ring-2 focus-visible:ring-blue-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white [&::-webkit-details-marker]:hidden">
                            <span class="truncate">
                                {{ $selectedFacilityLabel !== '' ? $selectedFacilityLabel : 'All facilities' }}
                            </span>

                            <svg class="size-4 shrink-0 text-zinc-500 transition-transform group-open:rotate-180 dark:text-zinc-400" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <path d="m5 7 5 5 5-5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" />
                            </svg>
                        </summary>

                        <div class="absolute z-30 mt-2 w-72 max-w-[calc(100vw-3rem)] rounded-xl border border-zinc-200 bg-white p-2 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
                            <p class="px-2 pb-2 text-xs text-zinc-500 dark:text-zinc-400">
                                Select one or more facilities
                            </p>

                            @foreach (\App\Models\Room::ALLOWED_FACILITIES as $facility)
                                <label class="flex min-h-10 cursor-pointer items-center gap-3 rounded-lg px-2 text-sm text-zinc-700 transition hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                    <input
                                        type="checkbox"
                                        name="facilities[]"
                                        value="{{ $facility }}"
                                        @checked(in_array($facility, $selectedFacilities, true))
                                        data-room-management-filter-change
                                        class="size-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800"
                                    >

                                    {{ str($facility)->replace('_', ' ')->title() }}
                                </label>
                            @endforeach
                        </div>
                    </details>
                </div>

                <div>
                    <label for="room-type" class="mb-1 block text-sm font-semibold text-zinc-700 dark:text-zinc-200">
                        Room type
                    </label>

                    <div class="relative">
                        <select
                            id="room-type"
                            name="type"
                            data-room-management-filter-select
                            class="min-h-11 w-full appearance-none rounded-xl border border-zinc-300 bg-white px-3 pe-11 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                        >
                            <option value="">All types</option>
                            @foreach (\App\Models\Room::ALLOWED_TYPES as $roomType)
                                <option value="{{ $roomType }}" @selected(($filters['type'] ?? '') === $roomType)>
                                    {{ str($roomType)->headline() }}
                                </option>
                            @endforeach
                        </select>

                        <svg class="pointer-events-none absolute end-4 top-1/2 size-4 -translate-y-1/2 text-zinc-500 dark:text-zinc-400" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="m5 7 5 5 5-5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" />
                        </svg>
                    </div>
                </div>

                <div>
                    <label for="room-capacity" class="mb-1 block text-sm font-semibold text-zinc-700 dark:text-zinc-200">
                        At least people
                    </label>

                    <div class="relative">
                        <select
                            id="room-capacity"
                            name="capacity"
                            data-room-management-filter-select
                            @disabled(! $canChooseCapacity)
                            class="min-h-11 w-full appearance-none rounded-xl border border-zinc-300 bg-white px-3 pe-11 text-sm disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                        >
                            <option value="">
                                {{ $canChooseCapacity ? 'Any size' : 'Choose floor and facility first' }}
                            </option>

                            @foreach ($capacityOptions as $capacityOption)
                                <option value="{{ $capacityOption }}" @selected((string) ($filters['capacity'] ?? '') === (string) $capacityOption)>
                                    {{ $capacityOption }}+ {{ \Illuminate\Support\Str::plural('person', $capacityOption) }}
                                </option>
                            @endforeach
                        </select>

                        <svg class="pointer-events-none absolute end-4 top-1/2 size-4 -translate-y-1/2 text-zinc-500 dark:text-zinc-400" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="m5 7 5 5 5-5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" />
                        </svg>
                    </div>
                </div>
            </form>
        </section>

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

                        <flux:table.column class="w-56">
                            Facilities
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

                                $listedFacilities = collect(
                                    $room->facilities ?? []
                                )
                                    ->filter(
                                        fn ($facility): bool => is_string($facility)
                                            && $facility !== ''
                                    )
                                    ->values();
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
                                    @if ($listedFacilities->isEmpty())
                                        <span class="text-sm text-zinc-500 dark:text-zinc-400">
                                            No facilities
                                        </span>
                                    @else
                                        <div class="flex max-w-56 flex-wrap gap-1">
                                            @foreach ($listedFacilities as $facility)
                                                <span
                                                    class="rounded-full bg-blue-500/10 px-2 py-1 text-xs font-medium text-blue-700 dark:text-blue-300"
                                                >
                                                    {{ str($facility)->headline() }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
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
                                             :href="route('rooms.edit', array_merge(
                                                 request()->query(),
                                                 [
                                                     'room' => $room,
                                                     'page' => $rooms->currentPage(),
                                                 ]
                                             ))"
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
                                                'Delete this room? Rooms with reservation records cannot be deleted.'
                                            )"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <input
                                                type="hidden"
                                                name="page"
                                                value="{{ $rooms->currentPage() }}"
                                            >

                                            @foreach (request()->except('page') as $filterName => $filterValue)
                                                @if (is_array($filterValue))
                                                    @foreach ($filterValue as $filterItem)
                                                        <input
                                                            type="hidden"
                                                            name="{{ $filterName }}[]"
                                                            value="{{ $filterItem }}"
                                                        >
                                                    @endforeach
                                                @else
                                                    <input
                                                        type="hidden"
                                                        name="{{ $filterName }}"
                                                        value="{{ $filterValue }}"
                                                    >
                                                @endif
                                            @endforeach

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

    <script data-navigate-once>
        (() => {
            if (window.smartLibraryRoomManagementFilterInitialised) {
                return;
            }

            window.smartLibraryRoomManagementFilterInitialised = true;

            let inputTimer;

            const submitFilterForm = (form, delay = 0) => {
                window.clearTimeout(inputTimer);

                inputTimer = window.setTimeout(() => {
                    form.requestSubmit();
                }, delay);
            };

            document.addEventListener('input', (event) => {
                const input = event.target;

                if (
                    !(input instanceof HTMLInputElement)
                    || !input.matches('[data-room-management-filter-input]')
                ) {
                    return;
                }

                const form = input.closest(
                    'form[data-room-management-filter-form]'
                );

                if (form instanceof HTMLFormElement) {
                    submitFilterForm(form, 350);
                }
            });

            document.addEventListener('change', (event) => {
                const control = event.target;

                if (
                    !(
                        control instanceof HTMLSelectElement
                        || control instanceof HTMLInputElement
                    )
                    || !control.matches(
                        '[data-room-management-filter-select], [data-room-management-filter-change]'
                    )
                ) {
                    return;
                }

                const form = control.closest(
                    'form[data-room-management-filter-form]'
                );

                if (!(form instanceof HTMLFormElement)) {
                    return;
                }

                /*
                 * 由数据库真实房间建立的 location -> type mapping。
                 * 只有该楼层有唯一房型时才自动带出，例如 First Floor -> Study。
                 */
                if (
                    control instanceof HTMLSelectElement
                    && control.id === 'room-location'
                ) {
                    const typesByLocation = JSON.parse(
                        form.dataset.locationTypes || '{}'
                    );
                    const matchingTypes =
                        typesByLocation[control.value] || [];
                    const typeSelect = form.querySelector('#room-type');

                    if (
                        typeSelect instanceof HTMLSelectElement
                        && matchingTypes.length === 1
                    ) {
                        typeSelect.value = matchingTypes[0];
                    }
                }

                /*
                 * 同样支援从唯一房型反向带出唯一楼层。
                 * 所有对应关系都由 Controller 从 rooms table 建立。
                 */
                if (
                    control instanceof HTMLSelectElement
                    && control.id === 'room-type'
                ) {
                    const locationsByType = JSON.parse(
                        form.dataset.typeLocations || '{}'
                    );
                    const matchingLocations =
                        locationsByType[control.value] || [];
                    const locationSelect = form.querySelector(
                        '#room-location'
                    );

                    if (
                        locationSelect instanceof HTMLSelectElement
                        && matchingLocations.length === 1
                    ) {
                        locationSelect.value = matchingLocations[0];
                    }
                }

                submitFilterForm(form);
            });
        })();
    </script>
</x-layouts::app>
