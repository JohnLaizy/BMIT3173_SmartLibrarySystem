<x-layouts::app :title="__('Payment details')">
    <div
        data-page-transition
        class="mx-auto flex w-full max-w-5xl flex-col gap-7 px-2 sm:px-4"
    >
        <header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <div
                    class="mb-3 inline-flex items-center gap-2 rounded-full
                           border border-sky-500/20 bg-sky-500/10 px-3 py-1
                           text-xs font-bold uppercase tracking-wider
                           text-sky-700 dark:text-sky-300"
                >
                    <span class="size-2 rounded-full bg-sky-500" aria-hidden="true"></span>
                    Payment reference
                </div>

                <flux:heading size="xl" level="1">
                    {{ auth()->user()->isLibrarian()
                        ? 'Payment details'
                        : 'Pay overdue fee' }}
                </flux:heading>

                <flux:text class="mt-2">
                    {{ $borrowing->book->title }}
                    — RM {{ number_format($borrowing->overdue_fee_cents / 100, 2) }}
                </flux:text>
            </div>

            <flux:button
                :href="route('payments.index')"
                variant="filled"
                icon="arrow-left"
                wire:navigate
            >
                {{ __('Back to payments') }}
            </flux:button>
        </header>

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">
                {{ session('success') }}
            </flux:callout>
        @endif

        @if (session('error'))
            <flux:callout variant="danger" icon="x-circle">
                {{ session('error') }}
            </flux:callout>
        @endif

        @if ($errors->any())
            <flux:callout variant="danger" icon="x-circle">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </flux:callout>
        @endif

        <section
            class="rounded-2xl border border-zinc-800 bg-zinc-950 p-5
                   text-zinc-100 shadow-sm sm:p-7"
        >
            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-zinc-500">
                        Book
                    </p>

                    <p class="mt-2 font-semibold text-white">
                        {{ $borrowing->book->title }}
                    </p>

                    <p class="mt-1 text-sm text-zinc-400">
                        {{ $borrowing->book->author }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-zinc-500">
                        Amount due
                    </p>

                    <p class="mt-2 text-2xl font-bold text-white">
                        RM {{ number_format($borrowing->overdue_fee_cents / 100, 2) }}
                    </p>
                </div>

                @if (auth()->user()->isLibrarian())
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-zinc-500">
                            Student
                        </p>

                        <p class="mt-2 font-semibold text-white">
                            {{ $borrowing->student->name }}
                        </p>

                        <p class="mt-1 text-sm text-zinc-400">
                            {{ $borrowing->student->email }}
                        </p>
                    </div>
                @endif

                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-zinc-500">
                        Current status
                    </p>

                    <p class="mt-2 font-semibold text-white">
                        {{ Str::headline($borrowing->status) }}
                    </p>
                </div>
            </div>
        </section>

        @if (
            auth()->user()->isStudent()
            && $borrowing->status === \App\Models\Borrowing::STATUS_FEE_UNPAID
        )
            <section
                class="rounded-2xl border border-sky-500/20 bg-sky-500/5 p-5 sm:p-7"
            >
                <flux:heading size="lg">
                    {{ $selectedGateway ? 'Continue payment simulation' : '1. Choose payment method' }}
                </flux:heading>

                <flux:text class="mt-2 max-w-2xl">
                    Choose a bank to generate a system payment reference. This is an assignment simulation only; the system does not process a real bank transaction.
                </flux:text>

                @if ($selectedGateway === null)
                    <form
                        method="POST"
                        action="{{ route('payments.start', $borrowing) }}"
                        class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-end"
                    >
                        @csrf

                        <div class="w-full sm:max-w-sm">
                            <label for="payment_method" class="mb-2 block text-sm font-semibold text-zinc-900 dark:text-white">
                                Payment method
                            </label>

                            <select
                                id="payment_method"
                                name="payment_method"
                                required
                                class="min-h-11 w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white"
                            >
                                <option value="">Select a bank</option>

                                @foreach ($gateways as $gateway)
                                    <option value="{{ $gateway->key() }}">
                                        {{ $gateway->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <flux:button type="submit" variant="primary" icon="arrow-right">
                            Generate reference
                        </flux:button>
                    </form>
                @else
                    <div
                        class="mt-6 rounded-2xl border border-zinc-800 bg-zinc-950 p-5
                               text-zinc-100"
                    >
                        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-center">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-zinc-500">
                                    System payment reference
                                </p>

                                <p class="mt-2 font-mono text-lg font-bold text-sky-300">
                                    {{ $borrowing->payment_reference }}
                                </p>

                                <p class="mt-2 text-sm text-zinc-400">
                                    Selected method: {{ $selectedGateway->label() }}
                                </p>
                            </div>

                            <button
                                type="button"
                                data-payment-popup="{{ $selectedGateway->destinationUrl() }}"
                                class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl
                                       bg-sky-500 px-4 py-2 text-sm font-semibold text-white
                                       transition hover:bg-sky-400 focus:outline-none focus-visible:ring-2
                                       focus-visible:ring-sky-300"
                            >
                                <span aria-hidden="true">↗</span>
                                Open {{ $selectedGateway->label() }}
                            </button>
                        </div>

                        <p class="mt-5 border-t border-zinc-800 pt-4 text-xs leading-5 text-zinc-400">
                            A small browser window will open to the bank's public website. Do not enter your bank credentials into this Smart Library System. After your simulation, return here and confirm below.
                        </p>

                        <form
                            method="POST"
                            action="{{ route('payments.complete', $borrowing) }}"
                            class="mt-5 border-t border-zinc-800 pt-5"
                        >
                            @csrf

                            <label class="flex items-start gap-3 text-sm text-zinc-300">
                                <input
                                    name="confirmed_simulation"
                                    type="checkbox"
                                    value="1"
                                    required
                                    class="mt-0.5 size-4 rounded border-zinc-600 bg-zinc-900 text-sky-500 focus:ring-sky-500"
                                >

                                <span>
                                    I understand this is a simulated payment. I have completed the simulation and request librarian review.
                                </span>
                            </label>

                            <flux:button type="submit" variant="primary" class="mt-5">
                                Done payment — submit for approval
                            </flux:button>
                        </form>
                    </div>
                @endif
            </section>
        @endif

        @if (
            auth()->user()->isLibrarian()
            && $borrowing->status === \App\Models\Borrowing::STATUS_PAYMENT_PENDING
        )
            <section class="rounded-2xl border border-amber-500/20 bg-amber-500/5 p-5 sm:p-7">
                <flux:heading size="lg">Librarian review</flux:heading>

                <flux:text class="mt-2">
                    Review the simulation reference and confirm the payment according to your assignment workflow.
                </flux:text>

                <div class="mt-5 flex flex-wrap gap-3">
                    <form method="POST" action="{{ route('borrowings.payment.approve', $borrowing) }}">
                        @csrf
                        @method('PATCH')

                        <flux:button type="submit" variant="primary">
                            Approve payment
                        </flux:button>
                    </form>

                    <form method="POST" action="{{ route('borrowings.payment.reject', $borrowing) }}">
                        @csrf
                        @method('PATCH')

                        <flux:button type="submit" variant="danger">
                            Reject payment
                        </flux:button>
                    </form>
                </div>
            </section>
        @endif

        @if (
            $borrowing->status === \App\Models\Borrowing::STATUS_COMPLETED
            && $borrowing->payment_reference !== null
        )
            <section class="rounded-2xl border border-emerald-500/20 bg-emerald-500/5 p-5 sm:p-7">
                <flux:heading size="lg">Payment completed</flux:heading>

                <flux:text class="mt-2">
                    This simulated payment was approved by a librarian. You can now view the internal system receipt.
                </flux:text>

                <flux:button
                    class="mt-5"
                    :href="route('payments.receipt', $borrowing)"
                    variant="primary"
                    icon="document-text"
                    wire:navigate
                >
                    View payment receipt
                </flux:button>
            </section>
        @endif

        <section
            class="overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-950
                   text-zinc-100 shadow-sm"
        >
            <div class="border-b border-zinc-800 bg-zinc-900 p-5">
                <flux:heading>Payment audit trail</flux:heading>

                <flux:text class="mt-1">
                    Internal system events only. No bank credentials or external transaction data are stored.
                </flux:text>
            </div>

            @if ($borrowing->paymentAudits->isEmpty())
                <div class="p-10 text-center text-sm text-zinc-500">
                    No payment activity has been recorded yet.
                </div>
            @else
                <div class="divide-y divide-zinc-800">
                    @foreach ($borrowing->paymentAudits->sortByDesc('created_at') as $audit)
                        <div class="flex flex-col justify-between gap-2 p-5 sm:flex-row sm:items-center">
                            <div>
                                <p class="font-semibold text-white">
                                    {{ Str::headline(str_replace('_', ' ', $audit->event)) }}
                                </p>

                                <p class="mt-1 text-sm text-zinc-400">
                                    {{ $audit->actor?->name ?? 'System' }}
                                    · {{ $audit->created_at->format('d M Y, h:i A') }}
                                </p>
                            </div>

                            <span class="font-mono text-xs text-sky-300">
                                {{ $audit->payment_reference ?? 'No reference' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <script>
        if (!window.__smartLibraryPaymentPopupBound) {
            window.__smartLibraryPaymentPopupBound = true;

            document.addEventListener('click', (event) => {
                const trigger = event.target.closest('[data-payment-popup]');

                if (!trigger) {
                    return;
                }

                const destination = trigger.dataset.paymentPopup;

                if (!destination) {
                    return;
                }

                const paymentWindow = window.open(
                    destination,
                    'simulatedBankPayment',
                    'popup=yes,width=760,height=760,noopener,noreferrer'
                );

                if (paymentWindow) {
                    paymentWindow.opener = null;
                }
            });
        }
    </script>
</x-layouts::app>
