<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'room_number' => 'R'.$this->faker
                ->unique()
                ->numberBetween(100, 999),

            'name' => $this->faker->words(3, true),

            'type' => $this->faker->randomElement(
                Room::ALLOWED_TYPES
            ),

            'capacity' => $this->faker->numberBetween(1, 100),

            'location' => $this->faker->randomElement([
                'First Floor',
                'Second Floor',
                'Third Floor',
            ]),

            'status' => $this->faker->randomElement(
                Room::ALLOWED_STATUSES
            ),

            'description' => $this->faker
                ->optional()
                ->sentence(),

            'facilities' => $this->faker->randomElements(
                Room::ALLOWED_FACILITIES,
                $this->faker->numberBetween(
                    0,
                    count(Room::ALLOWED_FACILITIES)
                )
            ),

            'created_by' => null,
        ];
    }
}
