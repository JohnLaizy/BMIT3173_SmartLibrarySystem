<?php

namespace App\Http\Controllers;

use App\Models\RoomReservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomReservationApiController extends Controller
{
    /**
     * Provide Room Reservation Information Web Service.
     */
    public function index(Request $request): JsonResponse
    {
        /*
         * Request tracking identifier
         * required for Web Service monitoring
         */
        $request->validate([
            'request_id' => [
                'required',
                'string',
            ],
        ]);

        $reservations = RoomReservation::query()
            ->with([
                'room',
                'user',
            ])
            ->latest()
            ->get();

        return response()->json([
            /*
             * Return the same request ID received
             * from the client request.
             */
            'request_id' =>
                $request->input('request_id'),

            'timestamp' =>
                now()->toISOString(),

            'status' =>
                'success',

            'data' =>
                $reservations->map(function ($reservation) {

                    return [
                        'reservation_id' =>
                            $reservation->id,

                        'room' => [
                            'id' =>
                                $reservation->room->id,

                            'room_number' =>
                                $reservation->room->room_number,

                            'name' =>
                                $reservation->room->name,
                        ],

                        'student' => [
                            'id' =>
                                $reservation->user->id,

                            'name' =>
                                $reservation->user->name,
                        ],

                        'purpose' =>
                            $reservation->purpose,

                        'starts_at' =>
                            $reservation->starts_at,

                        'ends_at' =>
                            $reservation->ends_at,

                        'status' =>
                            $reservation->status,
                    ];
                }),
        ]);
    }
}