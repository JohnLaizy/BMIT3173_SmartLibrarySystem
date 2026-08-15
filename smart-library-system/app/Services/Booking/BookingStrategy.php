<?php

namespace App\Services\Booking;

interface BookingStrategy
{
    public function isRoomAvailable(
        int $roomId,
        string $bookingDate,
        string $startTime,
        string $endTime
    ): bool;
}