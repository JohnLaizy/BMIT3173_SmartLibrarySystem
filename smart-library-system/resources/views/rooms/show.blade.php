<x-layouts::app :title="$room->room_number">
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

    <div class="mx-auto w-full max-w-4xl">
        <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row">
            <div>
                <div class="flex items-center gap-3">
                    <flux:heading size="xl" level="1">
                        {{ $room->room_number }}
                    </flux:heading>

                    <flux:badge :color="$statusColor">
                        {{ $statusLabel }}
                    </flux:badge>
                </div>

                <flux:text class="mt-2">
                    {{ $room->name }}
                </flux:text>
            </div>

            <div class="flex gap-3">
                <flux:button
                    :href="route('rooms.index')"
                    variant="ghost"
                >
                    Back
                </flux:button>

                @can('update', $room)
                <flux:button
                    :href="route('rooms.edit', $room)"
                    variant="primary"
                    icon="pencil"
                >
                    Edit Room
                </flux:button>
                @endcan
            </div>
        </div>

        @if (session('success'))
            <div
                role="status"
                class="mb-6 rounded-lg border border-green-200
                       bg-green-50 px-4 py-3 text-sm text-green-800
                       dark:border-green-800 dark:bg-green-950
                       dark:text-green-200"
            >
                {{ session('success') }}
            </div>
        @endif

        <div
            class="rounded-xl border border-zinc-200 bg-white p-6
                   dark:border-zinc-700 dark:bg-zinc-900"
        >
            <dl class="grid gap-6 sm:grid-cols-2">
                <div>
                    <dt class="text-sm text-zinc-500">Room Type</dt>
                    <dd class="mt-1 font-medium">
                        {{ ucfirst($room->type) }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm text-zinc-500">Capacity</dt>
                    <dd class="mt-1 font-medium">
                        {{ $room->capacity }} people
                    </dd>
                </div>

                <div>
                    <dt class="text-sm text-zinc-500">Location</dt>
                    <dd class="mt-1 font-medium">
                        {{ $room->location }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm text-zinc-500">Created By</dt>
                    <dd class="mt-1 font-medium">
                        {{ $room->creator?->name ?? 'Unknown' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm text-zinc-500">Created At</dt>
                    <dd class="mt-1 font-medium">
                        {{ $room->created_at->format('d M Y, h:i A') }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm text-zinc-500">Last Updated</dt>
                    <dd class="mt-1 font-medium">
                        {{ $room->updated_at->format('d M Y, h:i A') }}
                    </dd>
                </div>

                <div class="sm:col-span-2">
                    <dt class="text-sm text-zinc-500">
                        Available Facilities
                    </dt>

                    <dd class="mt-3 flex flex-wrap gap-2">
                        @forelse ($room->facilities ?? [] as $facility)
                            <flux:badge
                                color="blue"
                                size="sm"
                            >
                                {{ str($facility)->headline() }}
                            </flux:badge>
                        @empty
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">
                                No facilities listed.
                            </span>
                        @endforelse
                    </dd>
                </div>

                <div class="sm:col-span-2">
                    <dt class="text-sm text-zinc-500">Description</dt>
                    <dd class="mt-1 whitespace-pre-line">
                        {{ $room->description ?: 'No description provided.' }}
                    </dd>
                </div>
            </dl>
        </div>

    @can('delete', $room)
        <div
            class="mt-6 rounded-xl border border-red-200 p-6
                   dark:border-red-900"
        >
            <flux:heading>Delete Room</flux:heading>

            <flux:text class="mt-2">
                This action permanently deletes the room.
            </flux:text>

            <form
                class="mt-4"
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
                    variant="danger"
                    icon="trash"
                >
                    Delete Room
                </flux:button>
            </form>
        </div>
    @endcan
</x-layouts::app>