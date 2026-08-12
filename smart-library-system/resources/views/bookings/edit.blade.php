<x-layouts::app :title="__('Edit Booking')">


    <div class="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-8 px-4 py-8">



        {{-- Header --}}
        <div>

            <flux:heading size="xl">
                Edit Booking
            </flux:heading>


            <flux:text class="mt-2">
                Update your room reservation details.
            </flux:text>

        </div>





        {{-- Edit Form --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">


            <form method="POST" action="{{ route('bookings.update', $booking->id) }}">


                @csrf
                @method('PATCH')





                {{-- Booking Date --}}
                <div class="mb-5">


                    <label class="mb-2 block text-sm font-medium text-zinc-700">
                        Booking Date
                    </label>


                    <input type="date" name="booking_date" value="{{ $booking->booking_date }}"
                        class="w-full rounded-lg border border-zinc-300 px-3 py-2" required>


                </div>







                {{-- Time --}}
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">



                    <div>


                        <label class="mb-2 block text-sm font-medium text-zinc-700">
                            Start Time
                        </label>


                        <input type="time" name="start_time" value="{{ $booking->start_time }}"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2" required>


                    </div>






                    <div>


                        <label class="mb-2 block text-sm font-medium text-zinc-700">
                            End Time
                        </label>


                        <input type="time" name="end_time" value="{{ $booking->end_time }}"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2" required>


                    </div>



                </div>







                <flux:button type="submit" variant="primary" class="mt-6">

                    Update Booking

                </flux:button>



            </form>



        </div>



    </div>



</x-layouts::app>
