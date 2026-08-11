<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\RoomReservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomReservation>
 */
class RoomReservationFactory extends Factory
{
    protected $model = RoomReservation::class;

    public function definition(): array
    {
        /*
         * 默认建立未来的预约，避免预约立即被系统判断为 Past。
         */
        $startsAt = CarbonImmutable::now()
            ->addDays(fake()->numberBetween(1, 14))
            ->setTime(
                fake()->numberBetween(8, 18),
                0,
                0
            );

        return [
            'room_id' => Room::factory(),

            'user_id' => User::factory()
                ->student(),

            'purpose' => fake()->sentence(5),

            'starts_at' => $startsAt,

            'ends_at' => $startsAt->addHour(),

            'status' => RoomReservation::STATUS_CONFIRMED,

            'cancelled_at' => null,

            'cancelled_by' => null,
        ];
    }

    /**
     * 建立已经取消的预约。
     */
    public function cancelled(): static
    {
        return $this->state(function (): array {
            return [
                'status' => RoomReservation::STATUS_CANCELLED,

                'cancelled_at' => CarbonImmutable::now(),

                'cancelled_by' => User::factory()
                    ->librarian(),
            ];
        });
    }

    /**
     * 建立已经结束的历史预约。
     */
    public function past(): static
    {
        return $this->state(function (): array {
            $startsAt = CarbonImmutable::now()
                ->subDays(2)
                ->setTime(10, 0, 0);

            return [
                'starts_at' => $startsAt,

                'ends_at' => $startsAt->addHour(),
            ];
        });
    }
}
