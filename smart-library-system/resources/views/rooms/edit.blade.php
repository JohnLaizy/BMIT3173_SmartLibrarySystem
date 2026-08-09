<x-layouts::app :title="__('Edit Room')">
    <div class="mx-auto w-full max-w-4xl">
        <div class="mb-6">
            <flux:heading size="xl" level="1">
                Edit Room
            </flux:heading>

            <flux:text class="mt-2">
                Update {{ $room->room_number }} information.
            </flux:text>
        </div>

        <div
            class="rounded-xl border border-zinc-200 bg-white p-6
                   dark:border-zinc-700 dark:bg-zinc-900"
        >
            <form
                method="POST"
                action="{{ route('rooms.update', $room) }}"
            >
                @csrf
                @method('PUT')

                @include('rooms._form')

                <div class="mt-10 flex justify-end gap-3">
                    <flux:button
                        :href="route('rooms.show', $room)"
                        variant="ghost"
                    >
                        Cancel
                    </flux:button>

                    <flux:button
                        type="submit"
                        variant="primary"
                    >
                        Save Changes
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>