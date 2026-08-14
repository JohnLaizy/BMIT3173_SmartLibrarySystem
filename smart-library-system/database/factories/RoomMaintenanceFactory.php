<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\RoomMaintenance;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomMaintenance>
 */
class RoomMaintenanceFactory extends Factory
{
    protected $model = RoomMaintenance::class;

    public function definition(): array
    {
        /*
         * 默认建立未来的维修记录。
         */
        $startsAt = CarbonImmutable::now()
            ->addDays(fake()->numberBetween(1, 14))
            ->setTime(
                fake()->numberBetween(8, 17),
                0,
                0
            );

        return [
            'room_id' => Room::factory(),

            'title' => fake()->randomElement([
                'Air conditioner inspection',
                'Projector repair',
                'Electrical maintenance',
                'Room cleaning',
                'Furniture replacement',
            ]),

            'description' => fake()->sentence(),

            'starts_at' => $startsAt,

            'ends_at' => $startsAt->addHours(2),

            'status' => RoomMaintenance::STATUS_SCHEDULED,

            'created_by' => User::factory()
                ->librarian(),
        ];
    }

    /**
     * 正在进行中的维修。
     */
    public function inProgress(): static
    {
        return $this->state(function (): array {
            return [
                'status' => RoomMaintenance::STATUS_IN_PROGRESS,
            ];
        });
    }

    /**
     * 已完成的维修。
     */
    public function completed(): static
    {
        return $this->state(function (): array {
            return [
                'status' => RoomMaintenance::STATUS_COMPLETED,
            ];
        });
    }

    /**
     * 已取消的维修。
     */
    public function cancelled(): static
    {
        return $this->state(function (): array {
            return [
                'status' => RoomMaintenance::STATUS_CANCELLED,
            ];
        });
    }
}
