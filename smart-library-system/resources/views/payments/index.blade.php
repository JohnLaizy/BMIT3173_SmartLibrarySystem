<x-layouts::app :title="__(auth()->user()->isLibrarian() ? 'Payment Management' : 'My Payments')">
    <div
        data-page-transition
        class="mx-auto flex w-full max-w-7xl flex-col gap-7 px-2 sm:px-4"
    >
        <header class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div>
                <div
                    class="mb-3 inline-flex items-center gap-2 rounded-full
                           border border-sky-500/20 bg-sky-500/10 px-3 py-1
                           text-xs font-bold uppercase tracking-wider
                           text-sky-700 dark:text-sky-300"
                >
                    <span class="size-2 rounded-full bg-sky-500" aria-hidden="true"></span>
                    Payment
                </div>

                <flux:heading size="xl" level="1">
                    {{ auth()->user()->isLibrarian()
                        ? 'Payment Management'
                        : 'My Payments' }}
                </flux:heading>

                <flux:text class="mt-2 max-w-2xl">
                    {{ auth()->user()->isLibrarian()
                        ? 'Review simulated overdue-fee payments and approve them after checking the payment record.'
                        : 'Pay overdue fees through the simulated online-banking workflow and review your payment history.' }}
                </flux:text>
            </div>

            <flux:button
                :href="route('borrowings.index')"
                variant="filled"
                icon="arrow-left"
                wire:navigate
            >
                {{ __('Borrow & Return') }}
            </flux:button>
        </header>

        <div
            role="note"
            class="rounded-2xl border border-sky-500/20 bg-sky-500/5 p-4
                   text-sm text-zinc-700 dark:text-zinc-200"
        >
            <div class="flex gap-3">
                <span class="text-lg text-sky-500" aria-hidden="true">ⓘ</span>

                <div>
                    <p class="font-semibold text-zinc-950 dark:text-white">
                        {{ __('Simulated online-banking payment') }}
                    </p>

                    <p class="mt-1 leading-6 text-zinc-600 dark:text-zinc-400">
                        {{ __('This system opens a public bank website only. It does not collect bank credentials or verify a real bank transaction. Submitted simulations require librarian approval.') }}
                    </p>
                </div>
            </div>
        </div>

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

        <section
            class="overflow-hidden rounded-2xl border border-zinc-800
                   bg-zinc-950 text-zinc-100 shadow-sm"
        >
            <div
                class="flex flex-col justify-between gap-3 border-b border-zinc-800
                       bg-zinc-900 p-5 sm:flex-row sm:items-center"
            >
                <div>
                    <flux:heading>
                        {{ auth()->user()->isLibrarian()
                            ? 'Overdue fee payments'
                            : 'Payment history' }}
                    </flux:heading>

                    <flux:text class="mt-1">
                        {{ $payments->total() }}
                        {{ Str::plural('record', $payments->total()) }}
                    </flux:text>
                </div>
            </div>

            @if ($payments->isEmpty())
                <div class="p-12 text-center">
                    <div
                        class="mx-auto grid size-12 place-items-center rounded-2xl
                               bg-zinc-900 text-xl text-zinc-400"
                        aria-hidden="true"
                    >
                        ✓
                    </div>

                    <flux:heading class="mt-4">
                        {{ __('No payment records found') }}
                    </flux:heading>

                    <flux:text class="mt-2">
                        {{ auth()->user()->isLibrarian()
                            ? 'Overdue-fee payment records will appear here.'
                            : 'You do not currently have any overdue-fee payments.' }}
                    </flux:text>
                </div>
            @else
                {{-- 清单固定保留五个 record row 的高度，footer 固定在卡片底部。 --}}
                <div class="min-h-[33rem] overflow-x-auto">
                    <table class="w-full min-w-[940px] text-left text-sm">
                        <thead class="border-b border-zinc-800 bg-zinc-900 text-zinc-200">
                            <tr>
                                @if (auth()->user()->isLibrarian())
                                    <th class="px-5 py-3 font-semibold">Student</th>
                                @endif

                                <th class="px-5 py-3 font-semibold">Book</th>
                                <th class="px-5 py-3 font-semibold">Payment reference</th>
                                <th class="px-5 py-3 font-semibold">Method</th>
                                <th class="px-5 py-3 font-semibold">Amount</th>
                                <th class="px-5 py-3 font-semibold">Status</th>
                                <th class="px-5 py-3 text-right font-semibold">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-zinc-800 bg-zinc-950">
                            @foreach ($payments as $payment)
                                @php
                                    $status = $payment->status;

                                    $statusLabel = match ($status) {
                                        \App\Models\Borrowing::STATUS_FEE_UNPAID => 'Payment required',
                                        \App\Models\Borrowing::STATUS_PAYMENT_PENDING => 'Awaiting approval',
                                        \App\Models\Borrowing::STATUS_COMPLETED => 'Approved',
                                        default => Str::headline($status),
                                    };

                                    $statusClasses = match ($status) {
                                        \App\Models\Borrowing::STATUS_FEE_UNPAID => 'bg-red-500/15 text-red-300 ring-red-500/20',
                                        \App\Models\Borrowing::STATUS_PAYMENT_PENDING => 'bg-amber-500/15 text-amber-300 ring-amber-500/20',
                                        \App\Models\Borrowing::STATUS_COMPLETED => 'bg-emerald-500/15 text-emerald-300 ring-emerald-500/20',
                                        default => 'bg-zinc-800 text-zinc-300 ring-zinc-700',
                                    };
                                @endphp

                                <tr class="h-24 transition-colors hover:bg-zinc-900/80">
                                    @if (auth()->user()->isLibrarian())
                                        <td class="px-5 py-4">
                                            <div class="font-medium text-white">
                                                {{ $payment->student->name }}
                                            </div>

                                            <div class="mt-1 text-xs text-zinc-500">
                                                {{ $payment->student->email }}
                                            </div>
                                        </td>
                                    @endif

                                    <td class="px-5 py-4">
                                        <div class="font-medium text-white">
                                            {{ $payment->book->title }}
                                        </div>

                                        <div class="mt-1 text-xs text-zinc-500">
                                            {{ $payment->book->author }}
                                        </div>
                                    </td>

                                    <td class="px-5 py-4 font-mono text-xs text-sky-300">
                                        {{ $payment->payment_reference ?? 'Not started' }}
                                    </td>

                                    <td class="px-5 py-4 text-zinc-300">
                                        {{ $payment->payment_method
                                            ? Str::headline($payment->payment_method)
                                            : 'Not selected' }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 font-semibold text-white">
                                        RM {{ number_format($payment->overdue_fee_cents / 100, 2) }}
                                    </td>

                                    <td class="px-5 py-4">
                                        <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $statusClasses }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4">
                                        <div class="flex justify-end gap-2">
                                            <flux:button
                                                :href="route('payments.show', $payment)"
                                                variant="filled"
                                                size="sm"
                                                wire:navigate
                                            >
                                                {{ $status === \App\Models\Borrowing::STATUS_FEE_UNPAID
                                                    ? 'Pay now'
                                                    : 'View' }}
                                            </flux:button>

                                            @if (
                                                auth()->user()->isLibrarian()
                                                && $status === \App\Models\Borrowing::STATUS_PAYMENT_PENDING
                                            )
                                                <form
                                                    method="POST"
                                                    action="{{ route('borrowings.payment.approve', $payment) }}"
                                                >
                                                    @csrf
                                                    @method('PATCH')

                                                    <flux:button type="submit" size="sm" variant="primary">
                                                        Approve
                                                    </flux:button>
                                                </form>

                                                <form
                                                    method="POST"
                                                    action="{{ route('borrowings.payment.reject', $payment) }}"
                                                >
                                                    @csrf
                                                    @method('PATCH')

                                                    <flux:button type="submit" size="sm" variant="danger">
                                                        Reject
                                                    </flux:button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if ($payments->count() < 5)
                        <div class="border-t border-zinc-800"></div>
                    @endif
                </div>

                <x-listing-pagination
                    :paginator="$payments"
                    aria-label="Payment pagination"
                    :dark-card="true"
                />
            @endif
        </section>
    </div>
</x-layouts::app>
