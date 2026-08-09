@php
    $currentRoom = $room ?? null;

    $selectedFacilities = (array) old(
        'facilities',
        $currentRoom?->facilities ?? []
    );
@endphp

<div class="grid gap-6 md:grid-cols-2">
    <flux:input
        name="room_number"
        label="Room Number"
        :value="old('room_number', $currentRoom?->room_number)"
        placeholder="Example: R101"
        maxlength="20"
        required
    />

    <flux:input
        name="name"
        label="Room Name"
        :value="old('name', $currentRoom?->name)"
        placeholder="Example: Discussion Room 1"
        maxlength="100"
        required
    />

    <flux:select
        name="type"
        label="Room Type"
        required
    >
        <flux:select.option value="">
            Select a room type
        </flux:select.option>

        @foreach ($roomTypes as $type)
            <flux:select.option
                :value="$type"
                :selected="old('type', $currentRoom?->type) === $type"
            >
                {{ ucfirst($type) }}
            </flux:select.option>
        @endforeach
    </flux:select>

    <flux:input
        name="capacity"
        label="Capacity"
        type="number"
        :value="old('capacity', $currentRoom?->capacity)"
        min="1"
        max="100"
        required
    />

    <flux:input
        name="location"
        label="Location"
        :value="old('location', $currentRoom?->location)"
        placeholder="Example: First Floor"
        maxlength="100"
        required
    />

    <flux:select
        name="status"
        label="Room Status"
        required
    >
        <flux:select.option value="">
            Select a room status
        </flux:select.option>

        @foreach ($roomStatuses as $status)
            <flux:select.option
                :value="$status"
                :selected="old(
                    'status',
                    $currentRoom?->status ?? 'available'
                ) === $status"
            >
                {{ match ($status) {
                     'maintenance' => 'Under Maintenance',
                       default => str($status)->headline(),
                } }}
            </flux:select.option>
        @endforeach
    </flux:select>
</div>

<div class="pt-8">
    <flux:checkbox.group
        name="facilities"
        label="Available Facilities"
        description="Select all facilities available in this room."
        class="grid gap-3 sm:grid-cols-2"
    >
        @foreach ($roomFacilities as $facility)
            <flux:checkbox
                name="facilities[]"
                :value="$facility"
                :label="str($facility)->headline()"
                :checked="in_array(
                    $facility,
                    $selectedFacilities,
                    true
                )"
            />
        @endforeach
    </flux:checkbox.group>

    @if ($errors->has('facilities.*'))
        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
            {{ $errors->first('facilities.*') }}
        </p>
    @endif
</div>


<div class="mt-6">
    <flux:textarea
        name="description"
        label="Description"
        rows="4"
        maxlength="1000"
        placeholder="Optional room description"
    >{{ old('description', $currentRoom?->description) }}</flux:textarea>
</div>