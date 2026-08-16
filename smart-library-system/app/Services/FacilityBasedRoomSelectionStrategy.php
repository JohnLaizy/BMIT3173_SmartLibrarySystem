<?php

namespace App\Services;

use Illuminate\Support\Collection;

class FacilityBasedRoomSelectionStrategy implements RoomSelectionStrategy
{
    public function select(
        Collection $rooms,
        array $criteria
    ): Collection {
        $requiredFacilities =
            $criteria['facilities'] ?? [];

        return $rooms
            ->filter(function ($room) use (
                $requiredFacilities
            ) {
                $roomFacilities =
                    $room->facilities ?? [];

                return empty(
                    array_diff(
                        $requiredFacilities,
                        $roomFacilities
                    )
                );
            })
            ->values();
    }
}