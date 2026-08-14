<?php

namespace App\Services\Booking;

use App\Models\Booking;

class StandardBookingStrategy implements BookingStrategy
{
    public function isRoomAvailable(
        int $roomId,
        string $bookingDate,
        string $startTime,
        string $endTime
    ): bool {
        $existingBooking = Booking::where('room_id', $roomId)
            ->where('booking_date', $bookingDate)
            ->where('status', 'confirmed')
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            })
            ->exists();

        return !$existingBooking;
    }
}