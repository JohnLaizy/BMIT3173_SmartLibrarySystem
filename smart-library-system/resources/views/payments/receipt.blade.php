<x-layouts::app :title="__('Payment receipt')">
    <div
        data-page-transition
        class="mx-auto flex w-full max-w-3xl flex-col gap-7 px-2 sm:px-4"
    >
        <header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <div
                    class="mb-3 inline-flex items-center gap-2 rounded-full
                           border border-emerald-500/20 bg-emerald-500/10 px-3 py-1
                           text-xs font-bold uppercase tracking-wider
                           text-emerald-700 dark:text-emerald-300"
                >
                    <span class="size-2 rounded-full bg-emerald-500" aria-hidden="true"></span>
                    Simulated payment receipt
                </div>

                <flux:heading size="xl" level="1">
                    Payment receipt
                </flux:heading>

                <flux:text class="mt-2">
                    Internal acknowledgement for your Smart Library System assignment simulation.
                </flux:text>
            </div>

            <flux:button
                :href="route('payments.show', $borrowing)"
                variant="filled"
                icon="arrow-left"
                wire:navigate
            >
                Back to payment
            </flux:button>
        </header>

        <section
            class="overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-950
                   text-zinc-100 shadow-sm"
        >
            <div class="border-b border-zinc-800 bg-zinc-900 p-5 sm:p-7">
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-zinc-500">
                            Payment reference
                        </p>

                        <p class="mt-2 font-mono text-xl font-bold text-emerald-300">
                            {{ $borrowing->payment_reference }}
                        </p>
                    </div>

                    <span class="inline-flex w-fit rounded-full bg-emerald-500/15 px-3 py-1 text-sm font-semibold text-emerald-300">
                        Completed
                    </span>
                </div>
            </div>

            <dl class="grid gap-px bg-zinc-800 sm:grid-cols-2">
                <div class="bg-zinc-950 p-5">
                    <dt class="text-xs font-bold uppercase tracking-wider text-zinc-500">Student</dt>
                    <dd class="mt-2 font-semibold text-white">{{ $borrowing->student->name }}</dd>
                    <dd class="mt-1 text-sm text-zinc-400">{{ $borrowing->student->email }}</dd>
                </div>

                <div class="bg-zinc-950 p-5">
                    <dt class="text-xs font-bold uppercase tracking-wider text-zinc-500">Book</dt>
                    <dd class="mt-2 font-semibold text-white">{{ $borrowing->book->title }}</dd>
                    <dd class="mt-1 text-sm text-zinc-400">{{ $borrowing->book->author }}</dd>
                </div>

                <div class="bg-zinc-950 p-5">
                    <dt class="text-xs font-bold uppercase tracking-wider text-zinc-500">Payment method</dt>
                    <dd class="mt-2 font-semibold text-white">{{ Str::headline((string) $borrowing->payment_method) }}</dd>
                </div>

                <div class="bg-zinc-950 p-5">
                    <dt class="text-xs font-bold uppercase tracking-wider text-zinc-500">Amount paid</dt>
                    <dd class="mt-2 text-xl font-bold text-white">RM {{ number_format($borrowing->overdue_fee_cents / 100, 2) }}</dd>
                </div>

                <div class="bg-zinc-950 p-5">
                    <dt class="text-xs font-bold uppercase tracking-wider text-zinc-500">Approved by</dt>
                    <dd class="mt-2 font-semibold text-white">{{ $borrowing->paymentApprover?->name ?? 'Librarian' }}</dd>
                </div>

                <div class="bg-zinc-950 p-5">
                    <dt class="text-xs font-bold uppercase tracking-wider text-zinc-500">Approved at</dt>
                    <dd class="mt-2 font-semibold text-white">{{ $borrowing->payment_approved_at?->format('d M Y, h:i A') }}</dd>
                </div>
            </dl>

            <div class="border-t border-zinc-800 p-5 text-sm leading-6 text-zinc-400 sm:p-7">
                This is an internal receipt for a simulated online-banking payment. Smart Library System does not collect bank credentials, initiate a real bank transfer, or verify an external bank transaction.
            </div>
        </section>
    </div>
</x-layouts::app>
