<?php

namespace App\Services;

use App\Models\RoomReservation;
use App\Models\User;

interface RoomReservationStrategy
{
    /**
     * 根据当前用户角色决定预约属于哪一个 Student。
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