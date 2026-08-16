<?php

namespace App\Services;

use Illuminate\Support\Collection;

class RoomSelectionContext
{
    public function __construct(
        private RoomSelectionStrategy $strategy
    ) {
    }

    public function setStrategy(
        RoomSelectionStrategy $strategy
    ): void {
        $this->strategy = $strategy;
    }

    /**
     * @param Collection<int, mixed> $rooms
     * @param array<string, mixed> $criteria
     */
    public function selectRooms(
        Collection $rooms,
        array $criteria
    ): Collection {
        return $this->strategy->select(
            $rooms,
            $criteria
        );
    }
}