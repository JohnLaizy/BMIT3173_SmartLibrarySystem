<x-layouts::app :title="__('Book Reservations')">
    <div
        data-page-transition
        class="mx-auto w-full max-w-7xl space-y-7 px-2 py-2 sm:px-4"
    >
        <header>
            <div
                class="mb-3 inline-flex items-center gap-2 rounded-full
                       border border-fuchsia-500/20 bg-fuchsia-500/10 px-3 py-1
                       text-xs font-bold uppercase tracking-wider
                       text-fuchsia-700 dark:text-fuchsia-300"
            >
                <span class="size-2 rounded-full bg-fuchsia-500" aria-hidden="true"></span>
                Book collection
            </div>

            <flux:heading size="xl">
                {{ __('Book Reservations') }}
            </flux:heading>

            <flux:text class="mt-2 max-w-2xl">
                @if (auth()->user()->isStudent())
                    {{ __('Request books and track your reservations.') }}
                @else
                    {{ __('Approve, reject and process reservation collection.') }}
                @endif
            </flux:text>
        </header>

        @if (session('success'))
            <flux:callout
                variant="success"
                icon="check-circle"
            >
                {{ session('success') }}
            </flux:callout>
        @endif

        @if (session('error'))
            <flux:callout
                variant="danger"
                icon="x-circle"
            >
                {{ session('error') }}
            </flux:callout>
        @endif

        @if ($errors->any())
            <flux:callout
                variant="danger"
                icon="x-circle"
            >
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </flux:callout>
        @endif

        @if (auth()->user()->isStudent())
            <section
                class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm
                       dark:border-zinc-700 dark:bg-zinc-900"
            >
                <flux:heading size="lg">
                    {{ __('Reserve a Book') }}
                </flux:heading>

                <div class="mt-4 grid gap-4
                            md:grid-cols-2 xl:grid-cols-3">
                    @forelse ($books as $book)
                        @php
                            $alreadyReserved =
                                $activeReservationBookIds
                                    ->contains($book->id);
                        @endphp

                        <article
                            class="rounded-xl border border-zinc-200
                                   bg-white p-5 shadow-sm dark:border-zinc-700
                                   dark:bg-slate-800/70"
                        >
                            <flux:heading>
                                {{ $book->title }}
                            </flux:heading>

                            <flux:text class="mt-1">
                                {{ $book->author }}
                            </flux:text>

                            <div class="mt-3 text-sm">
                                <span class="font-medium">
                                    {{ __('Available:') }}
                                </span>

                                {{ $book->available_copies }}
                                /
                                {{ $book->total_copies }}
                            </div>

                            <form
                                method="POST"
                                action="{{ route(
                                    'book-reservations.store'
                                ) }}"
                                class="mt-4"
                            >
                                @csrf

                                <input
                                    type="hidden"
                                    name="book_id"
                                    value="{{ $book->id }}"
                                >

                                <flux:button
                                    type="submit"
                                    variant="primary"
                                    class="w-full"
                                    :disabled="$alreadyReserved"
                                >
                                    @if ($alreadyReserved)
                                        {{ __('Already Requested') }}
                                    @elseif ($book->available_copies > 0)
                                        {{ __('Reserve Book') }}
                                    @else
                                        {{ __('Join Waiting List') }}
                                    @endif
                                </flux:button>
                            </form>
                        </article>
                    @empty
                        <flux:text>
                            {{ __('No books are currently available.') }}
                        </flux:text>
                    @endforelse
                </div>
            </section>
        @endif

            <section
                class="overflow-hidden rounded-2xl border-2 border-zinc-700 bg-zinc-950 text-zinc-100 shadow-sm"
            >
                <div class="border-b-2 border-zinc-700 bg-zinc-900 px-5 py-4">
                    <flux:heading size="lg">
                        @if (auth()->user()->isStudent())
                            {{ __('My Reservations') }}
                        @else
                            {{ __('Reservation Management') }}
                        @endif
                    </flux:heading>
                </div>

            <div class="overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-sm">
                        <thead class="border-b-2 border-zinc-700 bg-zinc-900 text-zinc-200">
                            <tr>
                                @if (auth()->user()->isLibrarian())
                                    <th class="px-4 py-3">
                                        {{ __('Student') }}
                                    </th>
                                @endif

                                <th class="px-4 py-3">
                                    {{ __('Book') }}
                                </th>

                                <th class="px-4 py-3">
                                    {{ __('Requested') }}
                                </th>

                                <th class="px-4 py-3">
                                    {{ __('Status') }}
                                </th>

                                <th class="px-4 py-3">
                                    {{ __('Expires') }}
                                </th>

                                <th class="px-4 py-3">
                                    {{ __('Actions') }}
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y-2 divide-zinc-800 bg-zinc-950">
                            @forelse ($reservations as $reservation)
                                @php
                                    $badgeColor = match (
                                        $reservation->status
                                    ) {
                                        'approved' => 'green',
                                        'pending' => 'amber',
                                        'rejected' => 'red',
                                        'cancelled' => 'zinc',
                                        'collected' => 'blue',
                                        'expired' => 'orange',
                                        default => 'zinc',
                                    };
                                @endphp

                                <tr class="transition-colors hover:bg-zinc-900/80">
                                    @if (auth()->user()->isLibrarian())
                                        <td class="px-4 py-3">
                                            <div class="font-medium">
                                                {{ $reservation->student->name }}
                                            </div>

                                            <div class="text-xs text-zinc-500">
                                                {{ $reservation->student->email }}
                                            </div>
                                        </td>
                                    @endif

                                    <td class="px-4 py-3">
                                        <div class="font-medium">
                                            {{ $reservation->book->title }}
                                        </div>

                                        <div class="text-xs text-zinc-500">
                                            {{ $reservation->book->author }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3">
                                        {{ $reservation->requested_at
                                            ->format('d M Y, g:i A') }}
                                    </td>

                                    <td class="px-4 py-3">
                                        <flux:badge
                                            :color="$badgeColor"
                                        >
                                            {{ ucfirst(
                                                $reservation->status
                                            ) }}
                                        </flux:badge>

                                        @if ($reservation->rejection_reason)
                                            <div class="mt-1 max-w-xs text-xs
                                                        text-red-600">
                                                {{ $reservation
                                                    ->rejection_reason }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3">
                                        {{ $reservation->expires_at
                                            ?->format('d M Y, g:i A')
                                            ?? '—' }}
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-2">
                                            @if (
                                                auth()->user()->isLibrarian()
                                                && $reservation->status
                                                    === 'pending'
                                            )
                                                <form
                                                    method="POST"
                                                    action="{{ route(
                                                        'book-reservations.approve',
                                                        $reservation
                                                    ) }}"
                                                >
                                                    @csrf
                                                    @method('PATCH')

                                                    <flux:button
                                                        type="submit"
                                                        variant="primary"
                                                        size="sm"
                                                    >
                                                        {{ __('Approve') }}
                                                    </flux:button>
                                                </form>

                                                <form
                                                    method="POST"
                                                    action="{{ route(
                                                        'book-reservations.reject',
                                                        $reservation
                                                    ) }}"
                                                    class="flex gap-2"
                                                >
                                                    @csrf
                                                    @method('PATCH')

                                                    <input
                                                        name="rejection_reason"
                                                        type="text"
                                                        maxlength="255"
                                                        placeholder="Reason"
                                                        class="w-36 rounded-lg
                                                               border border-zinc-300
                                                               bg-white px-2 py-1
                                                               text-sm
                                                               dark:border-zinc-600
                                                               dark:bg-zinc-800"
                                                    >

                                                    <flux:button
                                                        type="submit"
                                                        variant="danger"
                                                        size="sm"
                                                    >
                                                        {{ __('Reject') }}
                                                    </flux:button>
                                                </form>
                                            @endif

                                            @if (
                                                auth()->user()->isLibrarian()
                                                && $reservation->isApproved()
                                            )
                                                <form
                                                    method="POST"
                                                    action="{{ route(
                                                        'book-reservations.collect',
                                                        $reservation
                                                    ) }}"
                                                >
                                                    @csrf
                                                    @method('PATCH')

                                                    <flux:button
                                                        type="submit"
                                                        variant="primary"
                                                        size="sm"
                                                    >
                                                        {{ __('Collect') }}
                                                    </flux:button>
                                                </form>
                                            @endif

                                            @if (
                                                in_array(
                                                    $reservation->status,
                                                    ['pending', 'approved'],
                                                    true
                                                )
                                                && (
                                                    auth()->user()->isLibrarian()
                                                    || $reservation->user_id
                                                        === auth()->id()
                                                )
                                            )
                                                <form
                                                    method="POST"
                                                    action="{{ route(
                                                        'book-reservations.cancel',
                                                        $reservation
                                                    ) }}"
                                                >
                                                    @csrf
                                                    @method('PATCH')

                                                    <flux:button
                                                        type="submit"
                                                        variant="ghost"
                                                        size="sm"
                                                    >
                                                        {{ __('Cancel') }}
                                                    </flux:button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="{{ auth()->user()->isLibrarian()
                                            ? 6
                                            : 5 }}"
                                        class="px-4 py-8 text-center
                                               text-zinc-500"
                                    >
                                        {{ __('No reservations found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">
                {{ $reservations->withQueryString()->links() }}
            </div>
        </section>
    </div>
</x-layouts::app>
