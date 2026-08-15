<x-layouts::app :title="__('Add Room')">
    <div
    data-page-transition
    class="mx-auto flex w-full max-w-6xl flex-1
           flex-col gap-8 px-2 sm:px-4"
>
        <header
            class="flex flex-col justify-between gap-4
                   sm:flex-row sm:items-start"
        >
            <div>
                <flux:heading size="xl" level="1">
                    Add Room
                </flux:heading>

                <flux:text class="mt-2">
                    Enter the new room information.
                </flux:text>
            </div>

            <flux:button
                :href="route('rooms.index')"
                variant="ghost"
                icon="arrow-left"
                class="min-h-11 w-full sm:w-auto"
                wire:navigate
            >
                Back to Room Management
            </flux:button>
        </header>

        <div
            class="rounded-xl border border-zinc-200 bg-white p-6
                   dark:border-zinc-700 dark:bg-zinc-900"
        >
            <form
                method="POST"
                action="{{ route('rooms.store') }}"
                data-room-location-form
                data-type-locations='@json($typeLocations)'
            >
                @csrf

                @include('rooms._form')

                <div
                    class="mt-12 flex justify-end gap-3"
                >
                    <flux:button
                        :href="route('rooms.index')"
                        variant="ghost"
                    >
                        Cancel
                    </flux:button>

                    <flux:button
                        type="submit"
                        variant="primary"
                    >
                        Create Room
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
