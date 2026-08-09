<x-layouts::app :title="__('Edit Room')">
    <div
        data-page-transition
        class="mx-auto flex w-full max-w-6xl flex-1
               flex-col gap-8 px-2 sm:px-4"
    >
        <!-- Page heading -->
        <div
            class="flex flex-col gap-4
                   sm:flex-row sm:items-start sm:justify-between"
        >
            <div>
                <flux:heading size="xl" level="1">
                    Edit Room
                </flux:heading>

                <flux:text class="mt-2">
                    Update {{ $room->room_number }} information.
                </flux:text>
            </div>

            <flux:button
                :href="route('rooms.show', $room)"
                variant="ghost"
                icon="arrow-left"
                class="self-start"
                wire:navigate.hover
            >
                Back to Room
            </flux:button>
        </div>

        <!-- Edit form -->
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
                        wire:navigate.hover
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