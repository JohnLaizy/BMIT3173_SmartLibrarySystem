<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ConsumeRoomAvailability extends Command
{
    protected $signature = 'app:consume-room-availability';

    protected $description = 'Consume Room Availability Web Service';

    public function handle()
    {
        $requestId = 'ROOM-' . str_pad(
            random_int(1, 999),
            3,
            '0',
            STR_PAD_LEFT
        );

        $startsAt = now()
            ->addDay()
            ->setTime(8, 0)
            ->format('Y-m-d H:i:s');

        $endsAt = now()
            ->addDay()
            ->setTime(9, 0)
            ->format('Y-m-d H:i:s');


        $response = Http::get(
            config('app.url') . '/api/v1/rooms/availability',
            [
                'request_id' => $requestId,

                'starts_at' => $startsAt,

                'ends_at' => $endsAt,
            ]
        );


        if ($response->successful()) {

            $data = $response->json();


            $this->info(
                'Room Availability Service Consumed Successfully'
            );


            $this->line('');

            $this->line(
                'Request ID: '
                . $data['request_id']
            );


            $this->line(
                'Timestamp: '
                . $data['timestamp']
            );


            $this->line(
                'Search Period: '
                . $data['data']['starts_at']
                . ' to '
                . $data['data']['ends_at']
            );


            $this->line('');

            $this->info(
                'Available Rooms: '
                . $data['data']['available_rooms_count']
            );


            $this->line('');


            foreach ($data['data']['rooms'] as $room) {


                $this->line(
                    'Room: '
                    . $room['room_number']
                    . ' - '
                    . $room['name']
                );


                $this->line(
                    'Capacity: '
                    . $room['capacity']
                );


                if (!empty($room['facilities'])) {

                    $this->line(
                        'Facilities: '
                        . implode(
                            ', ',
                            $room['facilities']
                        )
                    );

                }


                $this->line('----------------');
            }


        } else {


            $this->error(
                'Failed to consume Room Availability Service'
            );


            $this->error(
                'HTTP Status: '
                . $response->status()
            );

        }


        return Command::SUCCESS;
    }
}