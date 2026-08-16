<x-layouts::app :title="__('Reserve Room')">
    <div
        data-page-transition
        class="mx-auto flex w-full max-w-5xl flex-1
               flex-col gap-6 px-2 sm:px-4"
    >
        {{-- 页面标题 --}}
        <header
            class="flex flex-col gap-4
                   sm:flex-row sm:items-start sm:justify-between"
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

                    Room Reservation
                </div>

                <flux:heading size="xl" level="1">
                    Reserve a Room
                </flux:heading>

                <flux:text class="mt-2 max-w-2xl">
                    Select a room and reservation period. Reservations
                    cannot overlap maintenance or existing bookings.
                </flux:text>
            </div>

            <div class="flex flex-wrap gap-3">
                <flux:button
                    :href="route('room-availability.index')"
                    variant="ghost"
                    icon="arrow-left"
                    wire:navigate
                >
                    Availability
                </flux:button>

                <flux:button
                    :href="route('room-reservations.index')"
                    variant="filled"
                    icon="calendar-days"
                    wire:navigate
                >
                    Reservations
                </flux:button>
            </div>
        </header>

        {{-- 显示所有表单错误 --}}
        @if ($errors->any())
            <div
                role="alert"
                class="rounded-2xl border border-red-500/30
                       bg-red-500/10 px-5 py-4 text-red-700
                       dark:text-red-300"
            >
                <p class="font-semibold">
                    Please check the reservation details.
                </p>

                <ul class="mt-2 list-inside list-disc space-y-1 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
            <div class="space-y-6">

                {{--
                |--------------------------------------------------------------------------
                | Room Selection Strategy
                |--------------------------------------------------------------------------
                |
                | Strategy Pattern:
                | User can interchange between Capacity-Based and
                | Facility-Based room selection algorithms.
                |
                --}}
                <section
                    class="rounded-2xl border border-purple-500/20
                           bg-purple-500/5 p-6 shadow-sm"
                >
                    <div class="mb-5">
                        <div
                            class="mb-2 inline-flex items-center gap-2
                                   rounded-full bg-purple-500/10
                                   px-3 py-1 text-xs font-bold
                                   uppercase tracking-wider
                                   text-purple-700 dark:text-purple-300"
                        >
                            Strategy Pattern
                        </div>

                        <h2
                            class="text-lg font-bold
                                   text-zinc-900 dark:text-white"
                        >
                            Room Selection Strategy
                        </h2>

                        <p
                            class="mt-1 text-sm
                                   text-zinc-600 dark:text-zinc-400"
                        >
                            Choose how the system should recommend
                            suitable rooms.
                        </p>
                    </div>

                    <form
                        method="GET"
                        action="{{ route('room-reservations.create') }}"
                        class="space-y-5"
                    >
                        {{-- Preserve Availability-page parameters --}}
                        @if (request('date'))
                            <input
                                type="hidden"
                                name="date"
                                value="{{ request('date') }}"
                            >
                        @endif

                        @if (request('start'))
                            <input
                                type="hidden"
                                name="start"
                                value="{{ request('start') }}"
                            >
                        @endif

                        {{-- Strategy Selection --}}
                        <div>
                            <label
                                for="selection_strategy"
                                class="mb-2 block text-sm font-semibold
                                       text-zinc-800 dark:text-zinc-200"
                            >
                                Selection Method
                            </label>

                            <select
                                id="selection_strategy"
                                name="selection_strategy"
                                required
                                class="min-h-12 w-full rounded-xl
                                       border border-zinc-300 bg-white
                                       px-4 text-zinc-900
                                       focus:border-purple-500
                                       focus:outline-none focus:ring-2
                                       focus:ring-purple-500/30
                                       dark:border-zinc-600
                                       dark:bg-zinc-800 dark:text-white"
                            >
                                <option value="">
                                    Select a strategy
                                </option>

                                <option
                                    value="capacity"
                                    @selected(
                                        $selectionStrategy === 'capacity'
                                    )
                                >
                                    Capacity-Based Selection
                                </option>

                                <option
                                    value="facility"
                                    @selected(
                                        $selectionStrategy === 'facility'
                                    )
                                >
                                    Facility-Based Selection
                                </option>
                            </select>
                        </div>

                        {{-- Capacity-Based Criteria --}}
                        <div>
                            <label
                                for="required_capacity"
                                class="mb-2 block text-sm font-semibold
                                       text-zinc-800 dark:text-zinc-200"
                            >
                                Required Capacity
                            </label>

                            <input
                                id="required_capacity"
                                type="number"
                                name="required_capacity"
                                min="1"
                                value="{{ $requiredCapacity }}"
                                class="min-h-12 w-full rounded-xl
                                       border border-zinc-300 bg-white
                                       px-4 text-zinc-900
                                       focus:border-purple-500
                                       focus:outline-none focus:ring-2
                                       focus:ring-purple-500/30
                                       dark:border-zinc-600
                                       dark:bg-zinc-800 dark:text-white"
                            >

                            <p
                                class="mt-1 text-xs
                                       text-zinc-500 dark:text-zinc-400"
                            >
                                Used when Capacity-Based Selection is chosen.
                            </p>
                        </div>

                        {{-- Facility-Based Criteria --}}
                        <div>
                            <p
                                class="mb-2 text-sm font-semibold
                                       text-zinc-800 dark:text-zinc-200"
                            >
                                Required Facilities
                            </p>

                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach ($availableFacilities as $facility)
                                    <label
                                        class="flex items-center gap-2
                                               rounded-xl border
                                               border-zinc-200 bg-white
                                               px-3 py-2 text-sm
                                               text-zinc-700
                                               dark:border-zinc-700
                                               dark:bg-zinc-900
                                               dark:text-zinc-300"
                                    >
                                        <input
                                            type="checkbox"
                                            name="required_facilities[]"
                                            value="{{ $facility }}"
                                            @checked(
                                                in_array(
                                                    $facility,
                                                    $requiredFacilities,
                                                    true
                                                )
                                            )
                                            class="rounded border-zinc-300"
                                        >

                                        <span>
                                            {{
                                                ucwords(
                                                    str_replace(
                                                        '_',
                                                        ' ',
                                                        $facility
                                                    )
                                                )
                                            }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>

                            <p
                                class="mt-2 text-xs
                                       text-zinc-500 dark:text-zinc-400"
                            >
                                Used when Facility-Based Selection is chosen.
                            </p>
                        </div>

                        <div class="flex justify-end">
                            <flux:button
                                type="submit"
                                variant="primary"
                            >
                                Apply Strategy
                            </flux:button>
                        </div>
                    </form>

                    {{-- 显示当前正在使用的 Strategy --}}
                    @if ($selectionStrategy)
                        <div
                            class="mt-5 rounded-xl
                                   border border-purple-500/20
                                   bg-purple-500/10
                                   px-4 py-3 text-sm
                                   text-purple-700
                                   dark:text-purple-300"
                        >
                            @if ($selectionStrategy === 'capacity')
                                <strong>
                                    Active Strategy:
                                    Capacity-Based Room Selection
                                </strong>

                                <p class="mt-1">
                                    Showing rooms that can accommodate at least
                                    {{ $requiredCapacity }}
                                    {{
                                        \Illuminate\Support\Str::plural(
                                            'person',
                                            $requiredCapacity
                                        )
                                    }},
                                    ordered by the closest suitable capacity.
                                </p>
                            @elseif ($selectionStrategy === 'facility')
                                <strong>
                                    Active Strategy:
                                    Facility-Based Room Selection
                                </strong>

                                <p class="mt-1">
                                    Showing rooms that contain the selected
                                    required facilities.
                                </p>
                            @endif
                        </div>
                    @endif
                </section>

                {{--
                |--------------------------------------------------------------------------
                | Reservation Form
                |--------------------------------------------------------------------------
                --}}
                <section
                    class="rounded-2xl border border-zinc-200
                           bg-white p-6 shadow-sm
                           dark:border-zinc-700 dark:bg-zinc-900"
                >
                    <div class="mb-6">
                        <h2
                            class="text-lg font-bold
                                   text-zinc-900 dark:text-white"
                        >
                            Reservation Details
                        </h2>

                        <p
                            class="mt-1 text-sm
                                   text-zinc-600 dark:text-zinc-400"
                        >
                            Complete the information below to reserve a room.
                        </p>
                    </div>

                    <form
                        method="POST"
                        action="{{ route('room-reservations.store') }}"
                        class="space-y-6"
                    >
                        @csrf

                        {{-- Librarian 可以替 Student 进行预约 --}}
                        @if (auth()->user()->isLibrarian())
                            <div>
                                <label
                                    for="user_id"
                                    class="mb-2 block text-sm font-semibold
                                           text-zinc-800 dark:text-zinc-200"
                                >
                                    Student
                                </label>

                                <select
                                    id="user_id"
                                    name="user_id"
                                    required
                                    class="min-h-12 w-full rounded-xl
                                           border border-zinc-300 bg-white
                                           px-4 text-zinc-900
                                           focus:border-blue-500
                                           focus:outline-none focus:ring-2
                                           focus:ring-blue-500/30
                                           dark:border-zinc-600
                                           dark:bg-zinc-800 dark:text-white"
                                >
                                    <option value="">
                                        Select a student
                                    </option>

                                    @foreach ($students as $student)
                                        <option
                                            value="{{ $student->id }}"
                                            @selected(
                                                (string) old('user_id') ===
                                                (string) $student->id
                                            )
                                        >
                                            {{ $student->name }}
                                            — {{ $student->email }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        {{-- 房间选择 --}}
                        <div>
                            <label
                                for="room_id"
                                class="mb-2 block text-sm font-semibold
                                       text-zinc-800 dark:text-zinc-200"
                            >
                                Room
                            </label>

                            <select
                                id="room_id"
                                name="room_id"
                                required
                                class="min-h-12 w-full rounded-xl
                                       border border-zinc-300 bg-white
                                       px-4 text-zinc-900
                                       focus:border-blue-500
                                       focus:outline-none focus:ring-2
                                       focus:ring-blue-500/30
                                       dark:border-zinc-600
                                       dark:bg-zinc-800 dark:text-white"
                            >
                                <option value="">
                                    Select a suitable room
                                </option>

                                @foreach ($rooms as $room)
                                    <option
                                        value="{{ $room->id }}"
                                        @selected(
                                            (string) old(
                                                'room_id',
                                                $selectedRoomId
                                            ) === (string) $room->id
                                        )
                                    >
                                        {{ $room->room_number }}
                                        — {{ $room->name }}
                                        ({{ $room->capacity }} people)
                                    </option>
                                @endforeach
                            </select>

                            @if ($selectionStrategy && $rooms->isEmpty())
                                <p
                                    class="mt-2 text-sm font-medium
                                           text-amber-600
                                           dark:text-amber-400"
                                >
                                    No rooms match the selected strategy
                                    criteria. Try another strategy or criteria.
                                </p>
                            @endif
                        </div>

                        {{-- 开始及结束时间 --}}
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label
                                    for="starts_at"
                                    class="mb-2 block text-sm font-semibold
                                           text-zinc-800 dark:text-zinc-200"
                                >
                                    Starts At
                                </label>

                                <input
                                    id="starts_at"
                                    type="datetime-local"
                                    name="starts_at"
                                    value="{{ old(
                                        'starts_at',
                                        $defaultStart
                                    ) }}"
                                    required
                                    class="min-h-12 w-full rounded-xl
                                           border border-zinc-300 bg-white
                                           px-4 text-zinc-900
                                           focus:border-blue-500
                                           focus:outline-none focus:ring-2
                                           focus:ring-blue-500/30
                                           dark:border-zinc-600
                                           dark:bg-zinc-800 dark:text-white"
                                >
                            </div>

                            <div>
                                <label
                                    for="ends_at"
                                    class="mb-2 block text-sm font-semibold
                                           text-zinc-800 dark:text-zinc-200"
                                >
                                    Ends At
                                </label>

                                <input
                                    id="ends_at"
                                    type="datetime-local"
                                    name="ends_at"
                                    value="{{ old(
                                        'ends_at',
                                        $defaultEnd
                                    ) }}"
                                    required
                                    class="min-h-12 w-full rounded-xl
                                           border border-zinc-300 bg-white
                                           px-4 text-zinc-900
                                           focus:border-blue-500
                                           focus:outline-none focus:ring-2
                                           focus:ring-blue-500/30
                                           dark:border-zinc-600
                                           dark:bg-zinc-800 dark:text-white"
                                >
                            </div>
                        </div>

                        {{-- 预约用途 --}}
                        <div>
                            <label
                                for="purpose"
                                class="mb-2 block text-sm font-semibold
                                       text-zinc-800 dark:text-zinc-200"
                            >
                                Purpose

                                <span
                                    class="text-red-500"
                                    aria-hidden="true"
                                >
                                    *
                                </span>
                            </label>

                            <textarea
                                id="purpose"
                                name="purpose"
                                rows="4"
                                maxlength="255"
                                required
                                placeholder="Example: Group assignment discussion"
                                class="w-full resize-y rounded-xl
                                       border border-zinc-300 bg-white
                                       px-4 py-3 text-zinc-900
                                       transition
                                       placeholder:text-zinc-400
                                       focus:border-blue-500
                                       focus:outline-none focus:ring-2
                                       focus:ring-blue-500/30
                                       dark:border-zinc-600
                                       dark:bg-zinc-800 dark:text-white
                                       dark:placeholder:text-zinc-500
                                       @error('purpose')
                                           border-red-500
                                           focus:border-red-500
                                           focus:ring-red-500/30
                                       @enderror"
                                @error('purpose')
                                    aria-invalid="true"
                                @enderror
                            >{{ old('purpose') }}</textarea>

                            <div
                                class="mt-2 flex flex-wrap
                                       items-center justify-between gap-2"
                            >
                                <p
                                    class="text-xs text-zinc-500
                                           dark:text-zinc-400"
                                >
                                    Required. Maximum 255 characters.
                                </p>

                                <p
                                    class="text-xs text-zinc-500
                                           dark:text-zinc-400"
                                >
                                    Describe the purpose of this reservation.
                                </p>
                            </div>

                            @error('purpose')
                                <p
                                    class="mt-2 text-sm font-medium
                                           text-red-500"
                                    role="alert"
                                >
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div
                            class="flex flex-col-reverse gap-3
                                   border-t border-zinc-200 pt-6
                                   dark:border-zinc-700
                                   sm:flex-row sm:justify-end"
                        >
                            <flux:button
                                :href="route('room-availability.index')"
                                variant="ghost"
                                wire:navigate
                            >
                                Cancel
                            </flux:button>

                            <flux:button
                                type="submit"
                                variant="primary"
                                icon="calendar-days"
                            >
                                Confirm Reservation
                            </flux:button>
                        </div>
                    </form>
                </section>
            </div>

            {{-- 预约规则提示 --}}
            <aside
                class="h-fit rounded-2xl border border-blue-500/20
                       bg-blue-500/5 p-6"
            >
                <h2
                    class="font-bold
                           text-zinc-900 dark:text-white"
                >
                    Reservation Guidelines
                </h2>

                <ul
                    class="mt-4 space-y-4 text-sm leading-6
                           text-zinc-600 dark:text-zinc-400"
                >
                    <li class="flex gap-3">
                        <span class="font-bold text-blue-500">
                            01
                        </span>

                        Reservations must use 30-minute intervals.
                    </li>

                    <li class="flex gap-3">
                        <span class="font-bold text-blue-500">
                            02
                        </span>

                        A reservation may last up to four hours.
                    </li>

                    <li class="flex gap-3">
                        <span class="font-bold text-blue-500">
                            03
                        </span>

                        Maintenance and existing reservations cannot overlap.
                    </li>

                    <li class="flex gap-3">
                        <span class="font-bold text-blue-500">
                            04
                        </span>

                        Select another available time if validation fails.
                    </li>
                </ul>
            </aside>
        </div>
    </div>
</x-layouts::app>