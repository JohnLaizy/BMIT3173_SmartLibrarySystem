<x-layouts::app :title="__('Edit Room')">
@php
    /*
     * 从 Room Management list 带来的页码。
     * 如果 URL 没有 page，例如直接进入 /rooms/1/edit，
     * 就安全地使用第一页。
     */
    $page = max(1, (int) request()->query('page', 1));
    $search = (string) ($search ?? request()->query('search', ''));
    $indexQuery = array_merge(
        request()->only([
            'search',
            'type',
            'capacity',
            'location',
            'facilities',
        ]),
        ['page' => $page]
    );
@endphp
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
                :href="route('rooms.index', $indexQuery)"
                variant="ghost"
                icon="arrow-left"
                class="self-start"
                wire:navigate.hover
            >
                Back to Room Management
            </flux:button>
        </div>

        <!-- Edit form -->
        <div
            class="rounded-xl border border-zinc-200 bg-white p-6
                   dark:border-zinc-700 dark:bg-zinc-900"
        >
<form
    method="POST"
    action="{{ route('rooms.update', array_merge(
        ['room' => $room],
        $indexQuery
    )) }}"
    data-room-location-form
    data-type-locations='@json($typeLocations)'
>
@csrf
@method('PUT')
@include('rooms._form')
                <div class="mt-10 flex justify-end gap-3">
                    <flux:button
                        :href="route('rooms.index', $indexQuery)"
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
