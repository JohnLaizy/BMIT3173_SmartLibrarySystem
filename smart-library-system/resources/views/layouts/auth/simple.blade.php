<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="dark"
>
    <head>
        @include('partials.head')
    </head>

    <body class="min-h-screen bg-zinc-950 text-white antialiased">
        <!-- Background decoration -->
        <div
            class="pointer-events-none fixed inset-0 overflow-hidden"
            aria-hidden="true"
        >
            <div
                class="absolute -left-40 -top-40 size-96
                       rounded-full bg-emerald-500/10 blur-3xl"
            ></div>

            <div
                class="absolute -bottom-48 -right-40 size-[30rem]
                       rounded-full bg-sky-500/10 blur-3xl"
            ></div>
        </div>

        <!-- Top navigation -->
        <header class="absolute inset-x-0 top-0 z-20">
            <div
                class="mx-auto flex h-20 max-w-7xl items-center
                       justify-between px-5 sm:px-8 lg:px-10"
            >
                <a
                    href="{{ route('home') }}"
                    class="flex items-center gap-3"
                    wire:navigate
                >
                    <span
                        class="grid size-10 place-items-center
                               rounded-xl bg-emerald-600 text-white
                               shadow-lg shadow-emerald-950/30"
                    >
                        <svg
                            class="size-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden="true"
                        >
                            <path
                                d="M4.75 5.75A2.75 2.75 0 0 1 7.5 3h3.25A2.25
                                   2.25 0 0 1 13 5.25V20a3.5 3.5 0 0 0-3.5-3.5
                                   H4.75V5.75ZM19.25 5.75A2.75 2.75 0 0 0
                                   16.5 3h-1.25A2.25 2.25 0 0 0 13 5.25V20
                                   a3.5 3.5 0 0 1 3.5-3.5h2.75V5.75Z"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </span>

                    <span>
                        <span class="block font-bold leading-tight text-white">
                            Smart Library
                        </span>

                        <span class="block text-xs text-zinc-400">
                            Learn. Discover. Connect.
                        </span>
                    </span>
                </a>

                <a
                    href="{{ route('home') }}"
                    class="inline-flex min-h-11 items-center gap-2
                           rounded-xl border border-zinc-700
                           bg-zinc-900 px-4 text-sm font-semibold
                           text-zinc-200 transition
                           hover:border-zinc-600 hover:bg-zinc-800
                           focus:outline-none focus:ring-2
                           focus:ring-emerald-500"
                    wire:navigate
                >
                    <span aria-hidden="true">←</span>
                    <span class="hidden sm:inline">Back to Home</span>
                    <span class="sm:hidden">Home</span>
                </a>
            </div>
        </header>

        <main
            class="relative mx-auto grid min-h-svh max-w-7xl
                   items-center gap-12 px-5 pb-10 pt-28
                   sm:px-8 lg:grid-cols-[1fr_0.9fr]
                   lg:px-10"
        >
            <!-- Introduction: desktop -->
            <section class="hidden max-w-xl lg:block">
                <div
                    class="inline-flex items-center gap-2 rounded-full
                           border border-emerald-500/20
                           bg-emerald-500/10 px-3 py-1.5
                           text-sm font-semibold text-emerald-300"
                >
                    <span class="size-2 rounded-full bg-emerald-400"></span>
                    Secure library access
                </div>

                <h1
                    class="mt-6 text-5xl font-bold leading-tight
                           tracking-tight text-white"
                >
                    Your library workspace,
                    <span class="text-emerald-400">
                        ready when you are.
                    </span>
                </h1>

                <p class="mt-6 max-w-lg text-lg leading-8 text-zinc-400">
                    Sign in to view room availability and securely access
                    the Smart Library System.
                </p>

                <div class="mt-10 grid gap-4">
                    <div
                        class="flex items-start gap-4 rounded-2xl
                               border border-zinc-800 bg-zinc-900/70 p-4"
                    >
                        <span
                            class="grid size-10 shrink-0 place-items-center
                                   rounded-xl bg-emerald-500/10
                                   text-emerald-400"
                        >
                            ✓
                        </span>

                        <div>
                            <p class="font-semibold text-white">
                                Current room information
                            </p>

                            <p class="mt-1 text-sm leading-6 text-zinc-400">
                                Check room status, capacity, location
                                and available facilities.
                            </p>
                        </div>
                    </div>

                    <div
                        class="flex items-start gap-4 rounded-2xl
                               border border-zinc-800 bg-zinc-900/70 p-4"
                    >
                        <span
                            class="grid size-10 shrink-0 place-items-center
                                   rounded-xl bg-sky-500/10 text-sky-400"
                        >
                            🔒
                        </span>

                        <div>
                            <p class="font-semibold text-white">
                                Role-based access
                            </p>

                            <p class="mt-1 text-sm leading-6 text-zinc-400">
                                Students can view information while librarians
                                securely manage room records.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Authentication card -->
            <section class="flex w-full justify-center lg:justify-end">
                <div
                    class="w-full max-w-md rounded-3xl border
                           border-zinc-700 bg-zinc-900/95
                           p-6 shadow-2xl shadow-black/30
                           backdrop-blur sm:p-8"
                >
                    {{ $slot }}
                </div>
            </section>
        </main>

        <footer
            class="relative border-t border-zinc-800
                   bg-zinc-950/80 px-5 py-6 text-center
                   text-sm text-zinc-500"
        >
            &copy; {{ now()->year }} Smart Library System.
            Secure access for students and librarians.
        </footer>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>