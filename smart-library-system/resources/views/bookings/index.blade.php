<x-layouts::app :title="__('Bookings')">

    <div class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-8 px-2 sm:px-4">


        {{-- Header --}}
        <div class="flex items-center justify-between">


            <div>

                <flux:heading size="xl">
                    Room Booking
                </flux:heading>


                <flux:text class="mt-2">
                    Manage library room bookings.
                </flux:text>


            </div>



            {{-- Create Booking Button --}}
            <flux:button href="{{ route('bookings.create') }}" variant="primary">
                + Book Room
            </flux:button>


        </div>





        {{-- Success Message --}}
        @if (session('success'))
            <div class="rounded-lg bg-green-50 px-4 py-3 text-green-800">

                {{ session('success') }}

            </div>
        @endif






        {{-- Error Message --}}
        @if (session('error'))
            <div class="rounded-lg bg-red-50 px-4 py-3 text-red-800">

                {{ session('error') }}

            </div>
        @endif








        {{-- Booking Table --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-5">


            <flux:table>


                <flux:table.columns>


                    <flux:table.column>
                        User
                    </flux:table.column>


                    <flux:table.column>
                        Room
                    </flux:table.column>


                    <flux:table.column>
                        Date
                    </flux:table.column>


                    <flux:table.column>
                        Time
                    </flux:table.column>


                    <flux:table.column>
                        Status
                    </flux:table.column>


                    <flux:table.column>
                        Action
                    </flux:table.column>


                </flux:table.columns>





                <flux:table.rows>


                    @foreach ($bookings as $booking)
                        <flux:table.row>


                            <flux:table.cell>
                                {{ $booking->user->name }}
                            </flux:table.cell>




                            <flux:table.cell>
                                {{ $booking->room->room_number }}
                            </flux:table.cell>




                            <flux:table.cell>
                                {{ $booking->booking_date }}
                            </flux:table.cell>




                            <flux:table.cell>

                                {{ $booking->start_time }}
                                -
                                {{ $booking->end_time }}

                            </flux:table.cell>





                            <flux:table.cell>

                                {{ ucfirst($booking->status) }}

                            </flux:table.cell>







                            <flux:table.cell>


                                @if ($booking->status == 'confirmed')
                                    <form method="POST" action="/bookings/{{ $booking->id }}/cancel">


                                        @csrf
                                        @method('PATCH')


                                        <flux:button type="submit" variant="danger">

                                            Cancel

                                        </flux:button>


                                    </form>
                                @else
                                    -
                                @endif


                            </flux:table.cell>





                        </flux:table.row>
                    @endforeach



                </flux:table.rows>



            </flux:table>


        </div>



    </div>


</x-layouts::app>
