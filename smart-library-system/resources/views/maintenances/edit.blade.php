<x-layouts::app :title="__('Edit Maintenance')">
    <div
        data-page-transition
        class="mx-auto w-full max-w-4xl px-2 sm:px-4"
    >
        {{-- Page header --}}
        <header
            class="mb-8 flex flex-wrap items-start
                   justify-between gap-4"
        >
            <div>
                <flux:heading size="xl" level="1">
                    Edit Maintenance
                </flux:heading>

                <flux:text class="mt-2">
                    Update
                    {{ $maintenance->room->room_number ?? 'room' }}
                    maintenance.
                </flux:text>
            </div>

            <flux:button
                :href="route('maintenances.index')"
                variant="ghost"
                wire:navigate
            >
                Back
            </flux:button>
        </header>

        {{-- Maintenance form card --}}
        <section
            class="rounded-2xl border border-zinc-200
                   bg-white p-6
                   dark:border-zinc-700 dark:bg-zinc-900
                   sm:p-8"
        >
            <form
                method="POST"
                action="{{ route(
                    'maintenances.update',
                    $maintenance
                ) }}"
            >
                @csrf
                @method('PUT')

                @include('maintenances._form')

                {{-- Form actions --}}
                <div
                    class="mt-8 flex flex-wrap
                           justify-end gap-3"
                >
                    <flux:button
                        :href="route('maintenances.index')"
                        variant="ghost"
                        wire:navigate
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
        </section>
    </div>
</x-layouts::app>