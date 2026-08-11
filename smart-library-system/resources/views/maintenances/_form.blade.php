@php
    /*
     * 如果页面传入了 $maintenance，
     * 代表现在是 Edit 模式。
     *
     * 没有 $maintenance 则是 Create 模式。
     */
    $editing = isset($maintenance);

    /*
     * datetime-local 要求的格式：
     * 2026-08-10T14:30
     */
    $startsAt = old(
        'starts_at',
        $editing
            ? $maintenance->starts_at->format('Y-m-d\TH:i')
            : '',
    );

    $endsAt = old(
        'ends_at',
        $editing
            ? $maintenance->ends_at->format('Y-m-d\TH:i')
            : '',
    );
@endphp

<div class="grid gap-6">
    {{-- Room --}}
    <div>
        <label
            for="room_id"
            class="mb-2 block text-sm font-semibold"
        >
            Room
        </label>

        <select
            id="room_id"
            name="room_id"
            required
            class="min-h-11 w-full rounded-xl
                   border border-zinc-300 bg-white px-3
                   text-zinc-900
                   focus:border-amber-500
                   focus:ring-amber-500
                   dark:border-zinc-700
                   dark:bg-zinc-800 dark:text-white"
        >
            <option value="">
                Select a room
            </option>

            @foreach ($rooms as $room)
                <option
                    value="{{ $room->id }}"
                    @selected(
                        (string) old(
                            'room_id',
                            $maintenance->room_id ?? ''
                        ) === (string) $room->id
                    )
                >
                    {{ $room->room_number }}
                    —
                    {{ $room->name }}
                </option>
            @endforeach
        </select>

        @error('room_id')
            <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                {{ $message }}
            </p>
        @enderror
    </div>

    {{-- Maintenance title --}}
    <div>
        <label
            for="title"
            class="mb-2 block text-sm font-semibold"
        >
            Maintenance title
        </label>

        <input
            id="title"
            name="title"
            type="text"
            maxlength="120"
            required
            value="{{ old(
                'title',
                $maintenance->title ?? ''
            ) }}"
            class="min-h-11 w-full rounded-xl
                   border border-zinc-300 bg-white px-3
                   text-zinc-900
                   focus:border-amber-500
                   focus:ring-amber-500
                   dark:border-zinc-700
                   dark:bg-zinc-800 dark:text-white"
        >

        @error('title')
            <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                {{ $message }}
            </p>
        @enderror
    </div>

    {{-- Start and end time --}}
    <div class="grid gap-6 sm:grid-cols-2">
        <div>
            <label
                for="starts_at"
                class="mb-2 block text-sm font-semibold"
            >
                Starts at
            </label>

            <input
                id="starts_at"
                name="starts_at"
                type="datetime-local"
                required
                value="{{ $startsAt }}"
                class="min-h-11 w-full rounded-xl
                       border border-zinc-300 bg-white px-3
                       text-zinc-900
                       focus:border-amber-500
                       focus:ring-amber-500
                       dark:border-zinc-700
                       dark:bg-zinc-800 dark:text-white"
            >

            @error('starts_at')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div>
            <label
                for="ends_at"
                class="mb-2 block text-sm font-semibold"
            >
                Ends at
            </label>

            <input
                id="ends_at"
                name="ends_at"
                type="datetime-local"
                required
                value="{{ $endsAt }}"
                class="min-h-11 w-full rounded-xl
                       border border-zinc-300 bg-white px-3
                       text-zinc-900
                       focus:border-amber-500
                       focus:ring-amber-500
                       dark:border-zinc-700
                       dark:bg-zinc-800 dark:text-white"
            >

            @error('ends_at')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                    {{ $message }}
                </p>
            @enderror
        </div>
    </div>

    {{-- Maintenance status --}}
    <div>
        <label
            for="status"
            class="mb-2 block text-sm font-semibold"
        >
            Status
        </label>

        <select
            id="status"
            name="status"
            required
            class="min-h-11 w-full rounded-xl
                   border border-zinc-300 bg-white px-3
                   text-zinc-900
                   focus:border-amber-500
                   focus:ring-amber-500
                   dark:border-zinc-700
                   dark:bg-zinc-800 dark:text-white"
        >
            @foreach ($maintenanceStatuses as $status)
                <option
                    value="{{ $status }}"
                    @selected(
                        old(
                            'status',
                            $maintenance->status ?? 'scheduled'
                        ) === $status
                    )
                >
                    {{ str($status)->headline() }}
                </option>
            @endforeach
        </select>

        @error('status')
            <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                {{ $message }}
            </p>
        @enderror
    </div>

    {{-- Description --}}
    <div>
        <label
            for="description"
            class="mb-2 block text-sm font-semibold"
        >
            Description
        </label>

        <textarea
            id="description"
            name="description"
            rows="4"
            maxlength="1000"
            class="w-full rounded-xl
                   border border-zinc-300 bg-white p-3
                   text-zinc-900
                   focus:border-amber-500
                   focus:ring-amber-500
                   dark:border-zinc-700
                   dark:bg-zinc-800 dark:text-white"
        >{{ old(
            'description',
            $maintenance->description ?? ''
        ) }}</textarea>

        @error('description')
            <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                {{ $message }}
            </p>
        @enderror
    </div>
</div>