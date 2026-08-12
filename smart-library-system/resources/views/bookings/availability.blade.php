<x-layouts::app :title="__('Check Availability')">

    <div class="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-8 px-4 py-8">


        {{-- Header --}}
        <div>

            <flux:heading size="xl">
                Check Room Availability
            </flux:heading>


            <flux:text class="mt-2">
                Find available library rooms based on your selected date and time.
            </flux:text>

        </div>





        {{-- Search Form --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6">


            <form method="GET" action="{{ route('bookings.availability') }}">



                <div class="grid grid-cols-1 gap-5 md:grid-cols-3">



                    {{-- Date --}}
                    <div>

                        <label class="mb-2 block text-sm font-medium">
                            Booking Date
                        </label>


                        <input type="date" name="booking_date" value="{{ request('booking_date') }}"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2" required>

                    </div>





                    {{-- Start Time --}}
                    <div>

                        <label class="mb-2 block text-sm font-medium">
                            Start Time
                        </label>


                        <input type="time" name="start_time" value="{{ request('start_time') }}"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2" required>

                    </div>





                    {{-- End Time --}}
                    <div>

                        <label class="mb-2 block text-sm font-medium">
                            End Time
                        </label>


                        <input type="time" name="end_time" value="{{ request('end_time') }}"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2" required>

                    </div>



                </div>





                <div class="mt-5">

                    <flux:button type="submit" variant="primary">
                        Check Availability
                    </flux:button>


                </div>



            </form>


        </div>






        {{-- Available Rooms Result --}}

        @if ($rooms->count())


            <div class="rounded-xl border border-zinc-200 bg-white p-6">


                <flux:heading size="lg">
                    Available Rooms
                </flux:heading>




                <div class="mt-4 space-y-3">


                    @foreach ($rooms as $room)
                        <div class="rounded-lg border p-4">


                            <div class="font-semibold">
                                {{ $room->room_number }}
                            </div>


                            <div class="text-sm text-zinc-600">
                                {{ $room->name }}
                            </div>


                        </div>
                    @endforeach


                </div>


            </div>
        @elseif(request()->filled('booking_date'))
            <div class="rounded-xl bg-red-50 px-5 py-4 text-red-700">

                No rooms are available during this time.

            </div>


        @endif





    </div>


</x-layouts::app>
