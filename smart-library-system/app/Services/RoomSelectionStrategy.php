<?php

namespace App\Services;

use Illuminate\Support\Collection;

interface RoomSelectionStrategy
{
    /**
     * Select suitable rooms using a specific strategy.
     *
     * @param Collection<int, mixed> $rooms
     * @param array<string, mixed> $criteria
     */
    public function select(
        Collection $rooms,
        array $criteria
    ): Collection;
}