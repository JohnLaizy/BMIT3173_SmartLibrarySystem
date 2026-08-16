<?php

namespace App\Services;

use Illuminate\Support\Collection;

class CapacityBasedRoomSelectionStrategy implements RoomSelectionStrategy
{
    public function select(
        Collection $rooms,
        array $criteria
    ): Collection {
        $requiredCapacity = (int) (
            $criteria['capacity'] ?? 1
        );

        return $rooms
            ->filter(
                fn ($room) =>
                    $room->capacity >= $requiredCapacity
            )
            ->sortBy('capacity')
            ->values();
    }
}