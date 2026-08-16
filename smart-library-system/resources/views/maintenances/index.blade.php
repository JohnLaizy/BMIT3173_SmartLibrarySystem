<x-layouts::app :title="__('Maintenance Management')">
    <div
        data-page-transition
        class="mx-auto flex w-full max-w-7xl
               flex-col gap-7 px-2 sm:px-4"
    >
        {{-- 页面标题 --}}
        <header
            class="flex flex-col justify-between gap-5
                   sm:flex-row sm:items-end"
        >
            <div>
                <div
                    class="mb-3 inline-flex items-center gap-2
                           rounded-full border border-amber-500/20
                           bg-amber-500/10 px-3 py-1
                           text-xs font-bold uppercase tracking-wider
                           text-amber-700 dark:text-amber-300"
                >
                    <span
                        class="size-2 rounded-full bg-amber-500"
                        aria-hidden="true"
                    ></span>

                    Room Operations
                </div>

                <flux:heading size="xl" level="1">
                    Maintenance Management
                </flux:heading>

                <flux:text class="mt-2 max-w-2xl">
                    Schedule room maintenance and prevent conflicts
                    with existing reservations.
                </flux:text>
            </div>

            <flux:button
                :href="route('maintenances.create')"
                variant="primary"
                icon="plus"
                class="min-h-11 w-full sm:w-auto"
                wire:navigate
            >
                Schedule Maintenance
            </flux:button>
        </header>

        {{-- 操作成功提示 --}}
        @if (session('success'))
            <div
                role="status"
                class="flex items-center gap-3 rounded-xl
                       border border-emerald-500/30
                       bg-emerald-500/10 px-4 py-3
                       text-sm font-medium text-emerald-700
                       dark:text-emerald-300"
            >
                <span
                    class="flex size-6 shrink-0 items-center
                           justify-center rounded-full
                           bg-emerald-500/20"
                    aria-hidden="true"
                >
                    ✓
                </span>

                {{ session('success') }}
            </div>
        @endif

        {{-- Maintenance Records --}}
        <section
            class="overflow-hidden rounded-2xl
                   border border-zinc-200 bg-white shadow-sm
                   dark:border-zinc-700 dark:bg-zinc-900"
        >
            {{-- Card 标题 --}}
            <div
                class="flex flex-wrap items-center justify-between
                       gap-3 border-b border-zinc-200
                       px-5 py-4 dark:border-zinc-700"
            >
                <div>
                    <h2
                        class="font-semibold
                               text-zinc-900 dark:text-white"
                    >
                        Maintenance Schedule
                    </h2>

                    <p
                        class="mt-1 text-sm
                               text-zinc-500 dark:text-zinc-400"
                    >
                    Monitor scheduled and in-progress maintenance
                    that currently affects room availability.
                    </p>
                </div>

                <span
                    class="rounded-full bg-zinc-100
                           px-3 py-1 text-sm font-medium
                           text-zinc-600
                           dark:bg-zinc-800 dark:text-zinc-300"
                >
                    {{ $currentMaintenances->total() }}

                    {{ Str::plural(
                        'record',
                        $currentMaintenances->total()
                    ) }}
                </span>
            </div>

            @if ($currentMaintenances->isEmpty())
                {{--
                    没有 Maintenance 时不使用 min-w-[1000px] Table。

                    这样 Empty State 会根据目前可见的 Card 宽度居中，
                    不会在手机或平板上偏向右边。
                --}}
                <div
                    class="flex min-h-[340px] flex-col
                           items-center justify-center
                           px-6 py-12 text-center sm:py-16"
                >
                    <div
                        class="flex size-14 items-center
                               justify-center rounded-2xl
                               bg-amber-500/10 text-2xl"
                        aria-hidden="true"
                    >
                        🛠
                    </div>

                    <h3
                        class="mt-4 font-semibold
                               text-zinc-900 dark:text-white"
                    >
                        No current maintenance
                    </h3>

                    <p
                        class="mt-2 max-w-sm text-sm leading-6
                               text-zinc-500 dark:text-zinc-400"
                    >
                        All rooms are currently clear. Schedule maintenance
                        when a room needs to be temporarily blocked.
                    </p>

                    <flux:button
                        :href="route('maintenances.create')"
                        variant="primary"
                        icon="plus"
                        class="mt-5 min-h-11"
                        wire:navigate
                    >
                        Schedule Maintenance
                    </flux:button>
                </div>
            @else
                {{--
                    有记录时才启用横向滚动及最小 Table 宽度，
                    避免手机端的资料栏被压缩。
                --}}
                {{-- 清单固定保留五个 record row 的高度，footer 固定在卡片底部。 --}}
                <div class="min-h-[33rem] overflow-x-auto">
                    <table
                        class="w-full min-w-[1000px]
                               table-fixed text-sm"
                    >
                        <thead
                            class="bg-zinc-100 text-left
                                   text-zinc-700
                                   dark:bg-zinc-800
                                   dark:text-zinc-200"
                        >
                            <tr>
                                <th
                                    class="w-[18%] px-6 py-4
                                           font-semibold"
                                >
                                    Room
                                </th>

                                <th
                                    class="w-[24%] px-6 py-4
                                           font-semibold"
                                >
                                    Maintenance
                                </th>

                                <th
                                    class="w-[28%] px-6 py-4
                                           font-semibold"
                                >
                                    Period
                                </th>

                                <th
                                    class="w-[14%] px-6 py-4
                                           font-semibold"
                                >
                                    Status
                                </th>

                                <th
                                    class="w-[16%] px-6 py-4
                                           text-right font-semibold"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody
                            class="divide-y divide-zinc-200
                                   dark:divide-zinc-800"
                        >
                            @foreach ($currentMaintenances as $maintenance)
                                @php
                                    /*
                                     * 根据状态选择 Badge 颜色。
                                     */
                                    $statusColor = match (
                                        $maintenance->status
                                    ) {
                                        'scheduled' => 'amber',
                                        'in_progress' => 'blue',
                                        'completed' => 'green',
                                        default => 'zinc',
                                    };
                                @endphp

                                <tr
                                    class="h-24 transition-colors duration-150
                                           hover:bg-zinc-50
                                           dark:hover:bg-zinc-800/70"
                                >
                                    {{-- Room --}}
                                    <td class="px-6 py-5 align-middle">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="flex size-10 shrink-0
                                                       items-center justify-center
                                                       rounded-xl bg-amber-500/10
                                                       font-bold text-amber-700
                                                       dark:text-amber-300"
                                                aria-hidden="true"
                                            >
                                                {{ substr(
                                                    $maintenance->room
                                                        ->room_number,
                                                    0,
                                                    1
                                                ) }}
                                            </div>

                                            <div class="min-w-0">
                                                <p
                                                    class="truncate font-bold
                                                           text-zinc-900
                                                           dark:text-white"
                                                >
                                                    {{ $maintenance->room
                                                        ->room_number }}
                                                </p>

                                                <p
                                                    class="mt-1 truncate
                                                           text-xs text-zinc-500
                                                           dark:text-zinc-400"
                                                >
                                                    {{ $maintenance->room
                                                        ->name }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Maintenance --}}
                                    <td class="px-6 py-5 align-middle">
                                        <p
                                            class="truncate font-semibold
                                                   text-zinc-900
                                                   dark:text-white"
                                        >
                                            {{ $maintenance->title }}
                                        </p>

                                        <p
                                            class="mt-1 truncate
                                                   text-zinc-500
                                                   dark:text-zinc-400"
                                            title="{{ $maintenance
                                                ->description }}"
                                        >
                                            {{ $maintenance->description
                                                ?: 'No description provided' }}
                                        </p>
                                    </td>

                                    {{-- Period --}}
                                    <td class="px-6 py-5 align-middle">
                                        <div class="flex items-start gap-3">
                                            <div
                                                class="mt-0.5 flex size-8
                                                       shrink-0 items-center
                                                       justify-center rounded-lg
                                                       bg-zinc-100 text-zinc-500
                                                       dark:bg-zinc-800
                                                       dark:text-zinc-300"
                                                aria-hidden="true"
                                            >
                                                ◷
                                            </div>

                                            <div>
                                                <p
                                                    class="font-medium
                                                           text-zinc-900
                                                           dark:text-white"
                                                >
                                                    {{ $maintenance
                                                        ->starts_at
                                                        ->format(
                                                            'd M Y, h:i A'
                                                        ) }}
                                                </p>

                                                <p
                                                    class="mt-1
                                                           text-zinc-500
                                                           dark:text-zinc-400"
                                                >
                                                    Until

                                                    {{ $maintenance
                                                        ->ends_at
                                                        ->format(
                                                            'd M Y, h:i A'
                                                        ) }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Status --}}
                                    <td class="px-6 py-5 align-middle">
                                        <flux:badge
                                            :color="$statusColor"
                                        >
                                            {{ str(
                                                $maintenance->status
                                            )->headline() }}
                                        </flux:badge>
                                    </td>

                                    {{-- Actions --}}
                                    <td
                                        class="px-6 py-5
                                               text-right align-middle"
                                    >
                                        <div
                                            class="flex items-center
                                                   justify-end gap-2"
                                        >
                                            <flux:button
                                                size="sm"
                                                variant="ghost"
                                                class="min-h-10
                                                       hover:!bg-zinc-500/10"
                                                :href="route(
                                                    'maintenances.edit',
                                                    $maintenance
                                                )"
                                                wire:navigate
                                            >
                                                Edit
                                            </flux:button>

                                            <form
                                                method="POST"
                                                action="{{ route(
                                                    'maintenances.destroy',
                                                    $maintenance
                                                ) }}"
                                                onsubmit="return confirm(
                                                    'Delete this maintenance record?'
                                                )"
                                            >
                                                @csrf
                                                @method('DELETE')

                                                <flux:button
                                                    type="submit"
                                                    size="sm"
                                                    variant="danger"
                                                    class="min-h-10"
                                                >
                                                    Delete
                                                </flux:button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if ($currentMaintenances->count() < 5)
                        <div class="border-t border-zinc-200 dark:border-zinc-800"></div>
                    @endif
                </div>

                <x-listing-pagination
                    :paginator="$currentMaintenances"
                    aria-label="Current maintenance pagination"
                />
            @endif

        </section>

        {{-- History 保持在独立区块，避免和当前维修资料混在一起。 --}}
        <section
            class="overflow-hidden rounded-2xl border border-zinc-200 bg-white
                   shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
        >
            <div
                class="flex flex-col gap-4 border-b border-zinc-200 px-5 py-4
                       lg:flex-row lg:items-center lg:justify-between
                       dark:border-zinc-700"
            >
                <div>
                    <h2 class="font-semibold text-zinc-900 dark:text-white">
                        Maintenance History
                    </h2>

                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        Search completed and cancelled maintenance records.
                    </p>
                </div>

                <form
                    method="GET"
                    action="{{ route('maintenances.index') }}"
                    data-maintenance-filter-form
                    class="flex w-full flex-col gap-2 sm:flex-row sm:items-center lg:w-auto"
                >
                    <label for="maintenance-search" class="sr-only">
                        Search maintenance history
                    </label>

                    <input
                        id="maintenance-search"
                        name="search"
                        type="search"
                        data-maintenance-filter-input
                        value="{{ $search }}"
                        placeholder="Room, title or description"
                        class="min-h-11 w-full rounded-xl border border-zinc-300 bg-white
                               px-3 text-sm outline-none placeholder:text-zinc-400
                               focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20
                               sm:w-64 dark:border-zinc-600 dark:bg-zinc-800
                               dark:text-white"
                    >

                    <div class="relative w-full sm:w-44">
                        <select
                            name="status"
                            data-maintenance-filter-select
                            class="min-h-11 w-full appearance-none rounded-xl border border-zinc-300 bg-white px-3 pe-11
                                   text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                        >
                            <option value="">All history</option>
                            <option value="completed" @selected($status === 'completed')>
                                Completed
                            </option>
                            <option value="cancelled" @selected($status === 'cancelled')>
                                Cancelled
                            </option>
                        </select>

                        <svg
                            class="pointer-events-none absolute end-4 top-1/2 size-4 -translate-y-1/2 text-zinc-500 dark:text-zinc-400"
                            viewBox="0 0 20 20"
                            fill="none"
                            aria-hidden="true"
                        >
                            <path d="m5 7 5 5 5-5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" />
                        </svg>
                    </div>

                    <button
                        type="button"
                        data-maintenance-filter-clear
                        @class([
                            'hidden' => $search === '' && $status === null,
                            'inline-flex min-h-11 items-center justify-center rounded-xl px-3 text-sm font-semibold text-zinc-600 transition hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800',
                        ])
                    >
                        Clear
                    </button>
                </form>
            </div>

            <div data-live-maintenance-results>
            @if ($historyMaintenances->isEmpty())
                <div class="px-6 py-14 text-center">
                    <h3 class="font-semibold text-zinc-900 dark:text-white">
                        {{ $search !== '' || $status !== null
                            ? 'No matching history records'
                            : 'No maintenance history yet' }}
                    </h3>

                    <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $search !== '' || $status !== null
                            ? 'Try a different room number, maintenance title or status.'
                            : 'Completed and cancelled maintenance records will appear here.' }}
                    </p>
                </div>
            @else
                {{-- 清单固定保留五个 record row 的高度，footer 固定在卡片底部。 --}}
                <div class="min-h-[33rem] overflow-x-auto">
                    <table class="w-full min-w-[860px] table-fixed text-sm">
                        <thead
                            class="bg-zinc-100 text-left text-zinc-700
                                   dark:bg-zinc-800 dark:text-zinc-200"
                        >
                            <tr>
                                <th class="w-[20%] px-6 py-4 font-semibold">
                                    Room
                                </th>
                                <th class="w-[29%] px-6 py-4 font-semibold">
                                    Maintenance
                                </th>
                                <th class="w-[29%] px-6 py-4 font-semibold">
                                    Period
                                </th>
                                <th class="w-[22%] px-6 py-4 font-semibold">
                                    Status
                                </th>
                            </tr>
                        </thead>

                        <tbody
                            class="divide-y divide-zinc-200
                                   dark:divide-zinc-700"
                        >
                            @foreach ($historyMaintenances as $maintenance)
                                @php
                                    $statusColor = match ($maintenance->status) {
                                        'completed' => 'green',
                                        'cancelled' => 'zinc',
                                        default => 'zinc',
                                    };
                                @endphp

                                <tr
                                    class="h-24 transition-colors duration-150 hover:bg-zinc-50
                                           dark:hover:bg-zinc-800/70"
                                >
                                    <td class="px-6 py-5">
                                        <p class="font-semibold text-zinc-900 dark:text-white">
                                            {{ $maintenance->room->room_number }}
                                        </p>
                                        <p class="mt-1 truncate text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $maintenance->room->name }}
                                        </p>
                                    </td>

                                    <td class="px-6 py-5">
                                        <p class="font-semibold text-zinc-900 dark:text-white">
                                            {{ $maintenance->title }}
                                        </p>
                                        <p class="mt-1 truncate text-zinc-500 dark:text-zinc-400">
                                            {{ $maintenance->description ?: 'No description provided' }}
                                        </p>
                                    </td>

                                    <td class="px-6 py-5">
                                        <p class="font-medium text-zinc-900 dark:text-white">
                                            {{ $maintenance->starts_at->format('d M Y, h:i A') }}
                                        </p>
                                        <p class="mt-1 text-zinc-500 dark:text-zinc-400">
                                            Ended {{ $maintenance->ends_at->format('d M Y, h:i A') }}
                                        </p>
                                    </td>

                                    <td class="px-6 py-5">
                                        <flux:badge :color="$statusColor">
                                            {{ str($maintenance->status)->headline() }}
                                        </flux:badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if ($historyMaintenances->count() < 5)
                        <div class="border-t border-zinc-200 dark:border-zinc-700"></div>
                    @endif
                </div>

                <x-listing-pagination
                    :paginator="$historyMaintenances"
                    aria-label="Maintenance history pagination"
                />
            @endif
            </div>
        </section>
    </div>

    <script data-navigate-once>
        (() => {
            if (window.smartLibraryMaintenanceFilterInitialised) {
                return;
            }

            window.smartLibraryMaintenanceFilterInitialised = true;

            let inputTimer;
            let activeRequest;

            const updateClearButton = (form) => {
                const clearButton = form.querySelector('[data-maintenance-filter-clear]');
                const search = form.querySelector('[name="search"]')?.value.trim() ?? '';
                const status = form.querySelector('[name="status"]')?.value ?? '';

                if (clearButton instanceof HTMLElement) {
                    clearButton.classList.toggle('hidden', search === '' && status === '');
                }
            };

            const updateMaintenanceResults = async (form) => {
                if (!(form instanceof HTMLFormElement)) {
                    return;
                }

                const url = new URL(form.action, window.location.origin);
                const formData = new FormData(form);

                for (const [key, value] of formData.entries()) {
                    if (String(value) !== '') {
                        url.searchParams.append(key, String(value));
                    }
                }

                const currentResults = document.querySelector('[data-live-maintenance-results]');

                if (!(currentResults instanceof HTMLElement)) {
                    window.location.assign(url);
                    return;
                }

                if (activeRequest) {
                    activeRequest.abort();
                }

                activeRequest = new AbortController();
                currentResults.setAttribute('aria-busy', 'true');

                try {
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        signal: activeRequest.signal,
                    });

                    if (!response.ok) {
                        throw new Error('Unable to update maintenance history.');
                    }

                    const documentResponse = new DOMParser().parseFromString(
                        await response.text(),
                        'text/html'
                    );
                    const nextResults = documentResponse.querySelector('[data-live-maintenance-results]');

                    if (!(nextResults instanceof HTMLElement)) {
                        throw new Error('Maintenance results were not found in the response.');
                    }

                    currentResults.replaceWith(nextResults);
                    window.history.replaceState({}, '', url);
                    updateClearButton(form);
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        window.location.assign(url);
                    }
                } finally {
                    currentResults.removeAttribute('aria-busy');
                }
            };

            const submitFilterForm = (form, delay = 0) => {
                window.clearTimeout(inputTimer);

                inputTimer = window.setTimeout(() => {
                    updateMaintenanceResults(form);
                }, delay);
            };

            document.addEventListener('input', (event) => {
                const input = event.target;

                if (!(input instanceof HTMLInputElement) || !input.matches('[data-maintenance-filter-input]')) {
                    return;
                }

                const form = input.closest('form[data-maintenance-filter-form]');

                if (form instanceof HTMLFormElement) {
                    submitFilterForm(form, 350);
                }
            });

            document.addEventListener('change', (event) => {
                const select = event.target;

                if (!(select instanceof HTMLSelectElement) || !select.matches('[data-maintenance-filter-select]')) {
                    return;
                }

                const form = select.closest('form[data-maintenance-filter-form]');

                if (form instanceof HTMLFormElement) {
                    submitFilterForm(form);
                }
            });

            document.addEventListener('submit', (event) => {
                const form = event.target;

                if (!(form instanceof HTMLFormElement) || !form.matches('[data-maintenance-filter-form]')) {
                    return;
                }

                event.preventDefault();
                window.clearTimeout(inputTimer);
                updateMaintenanceResults(form);
            });

            document.addEventListener('click', (event) => {
                const clearButton = event.target.closest('[data-maintenance-filter-clear]');

                if (!(clearButton instanceof HTMLElement)) {
                    return;
                }

                const form = clearButton.closest('form[data-maintenance-filter-form]');

                if (!(form instanceof HTMLFormElement)) {
                    return;
                }

                form.querySelector('[name="search"]').value = '';
                form.querySelector('[name="status"]').value = '';
                updateMaintenanceResults(form);
                form.querySelector('[name="search"]').focus();
            });
        })();
    </script>
</x-layouts::app>
