<?php

namespace App\Services;

use App\Models\RoomReservation;
use App\Models\User;

interface RoomReservationTargetResolver
{
    /**
     * Resolve which student the reservation belongs to.
     *
     * @param array{
     *     room_id: int,
     *     user_id: int|null,
     *     purpose: string,
     *     starts_at: string,
     *     ends_at: string
     * } $data
     */
    public function resolveTargetUser(
        User $actor,
        array $data,
        ?RoomReservation $reservation = null
    ): User;
}