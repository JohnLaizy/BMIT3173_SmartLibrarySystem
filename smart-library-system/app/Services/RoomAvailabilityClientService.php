<?php


namespace App\Services;


use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;


class RoomAvailabilityClientService
{
    /**
     * Consume Room Availability Web Service.
     */
    public function getAvailableRooms(
        string $startsAt,
        string $endsAt
    ): array {
        $response = Http::get(
            config('app.url') . '/api/v1/rooms/availability',
            [
                'request_id' => (string) Str::uuid(),
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]
        );


        return $response->json();
    }
}



