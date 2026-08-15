<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomMaintenance;
use App\Models\RoomReservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoomAvailabilityApiController extends Controller
{
    /**
     * Provide Web Service:
     * Return rooms available during the requested period.
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'starts_at' => [
                    'required',
                    'date',
                ],
                'ends_at' => [
                    'required',
                    'date',
                    'after:starts_at',
                ],
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $startsAt = $validated['starts_at'];
        $endsAt = $validated['ends_at'];

        $rooms = Room::query()
            ->where('status', 'available')

            // Exclude rooms under maintenance.
            ->whereDoesntHave(
                'maintenances',
                function ($query) use ($startsAt, $endsAt) {
                    $query
                        ->whereIn('status', [
                            RoomMaintenance::STATUS_SCHEDULED,
                            RoomMaintenance::STATUS_IN_PROGRESS,
                        ])
                        ->where('starts_at', '<', $endsAt)
                        ->where('ends_at', '>', $startsAt);
                }
            )

            // Exclude rooms that already have reservations.
            ->whereDoesntHave(
                'reservations',
                function ($query) use ($startsAt, $endsAt) {
                    $query
                        ->where(
                            'status',
                            RoomReservation::STATUS_CONFIRMED
                        )
                        ->where('starts_at', '<', $endsAt)
                        ->where('ends_at', '>', $startsAt);
                }
            )

            ->orderBy('room_number')
            ->get([
                'id',
                'room_number',
                'name',
                'capacity',
                'facilities',
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Available rooms retrieved successfully.',
            'data' => [
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'available_rooms_count' => $rooms->count(),
                'rooms' => $rooms,
            ],
        ]);
    }
}