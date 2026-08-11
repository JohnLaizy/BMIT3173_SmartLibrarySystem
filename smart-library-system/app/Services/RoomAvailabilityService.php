<?php

namespace App\Services;

use App\Models\LibrarySetting;
use App\Models\Room;
use App\Models\RoomMaintenance;
use App\Models\RoomReservation;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * @phpstan-type CellStatus 'available'|'reserved'|'maintenance'|'unavailable'|'past'
 * @phpstan-type ScheduleCell array{
 *     status: CellStatus,
 *     maintenance: RoomMaintenance|null,
 *     reservation: RoomReservation|null
 * }
 */
class RoomAvailabilityService
{
    /**
     * 建立指定营业日期的房间时间表。
     *
     * @return array{
     *     rooms: EloquentCollection<int, Room>,
     *     slots: Collection<int, CarbonImmutable>,
     *     schedule: array<int, array<string, ScheduleCell>>,
     *     summary: array{
     *         available: int,
     *         reserved: int,
     *         maintenance: int,
     *         unavailable: int,
     *         past: int
     *     },
     *     librarySetting: LibrarySetting,
     *     openingAt: CarbonImmutable,
     *     closingAt: CarbonImmutable
     * }
     */
    public function forDate(
        CarbonImmutable $date
    ): array {
        /*
         * 从数据库读取普通模式或 Exam Period 设置。
         */
        $librarySetting = LibrarySetting::current();

        /*
         * 普通模式：
         * 08:00 至 20:00。
         *
         * Exam Period：
         * 08:00 至第二天 01:00。
         */
        $openingAt = $librarySetting->openingAt($date);
        $closingAt = $librarySetting->closingAt($date);

        /*
         * 只读取与营业时间有重叠的
         * Maintenance 和 Reservation。
         *
         * Exam Period 跨过凌晨时，这里的 closingAt
         * 也会自动进入第二天。
         */
        $rooms = Room::query()
            ->with([
                'maintenances' => fn ($query) => $query
                    ->whereIn('status', [
                        RoomMaintenance::STATUS_SCHEDULED,
                        RoomMaintenance::STATUS_IN_PROGRESS,
                    ])
                    ->where(
                        'starts_at',
                        '<',
                        $closingAt
                    )
                    ->where(
                        'ends_at',
                        '>',
                        $openingAt
                    ),

                'reservations' => fn ($query) => $query
                    ->with('user')
                    ->where(
                        'status',
                        RoomReservation::STATUS_CONFIRMED
                    )
                    ->where(
                        'starts_at',
                        '<',
                        $closingAt
                    )
                    ->where(
                        'ends_at',
                        '>',
                        $openingAt
                    ),
            ])
            ->orderBy('room_number')
            ->get();

        /*
         * 根据 Opening 和 Closing 动态建立时段。
         *
         * 普通模式产生：
         * 08:00、09:00……19:00。
         *
         * Exam Period 产生：
         * 08:00、09:00……23:00、第二天 00:00。
         */
        /** @var Collection<int, CarbonImmutable> $slots */
        $slots = collect();

        for (
            $slotStart = $openingAt;
            $slotStart->lessThan($closingAt);
            $slotStart = $slotStart->addHour()
        ) {
            $slots->push($slotStart);
        }

        /** @var array<int, array<string, ScheduleCell>> $schedule */
        $schedule = [];

        /*
         * Past 也加入 Summary，避免已经过去的
         * Available Slot 继续被计算为可预约。
         */
        $summary = [
            'available' => 0,
            'reserved' => 0,
            'maintenance' => 0,
            'unavailable' => 0,
            'past' => 0,
        ];

        foreach ($rooms as $room) {
            foreach ($slots as $slotStart) {
                $slotEnd = $slotStart->addHour();

                $cell = $this->resolveCell(
                    $room,
                    $slotStart,
                    $slotEnd
                );

                /*
                 * 只有原本 Available 但开始时间已经过去的
                 * Cell 才转换为 Past。
                 *
                 * 历史 Reservation 和 Maintenance
                 * 仍然保留它们原本的状态。
                 */
                if (
                    $cell['status'] === 'available'
                    && $slotStart->isPast()
                ) {
                    $cell = [
                        'status' => 'past',
                        'maintenance' => null,
                        'reservation' => null,
                    ];
                }

                $timeKey = $slotStart->format('H:i');

                $schedule[$room->id][$timeKey] = $cell;

                $summary[$cell['status']]++;
            }
        }

        return [
            'rooms' => $rooms,
            'slots' => $slots,
            'schedule' => $schedule,
            'summary' => $summary,
            'librarySetting' => $librarySetting,
            'openingAt' => $openingAt,
            'closingAt' => $closingAt,
        ];
    }

    /**
     * 判断一个房间时段属于什么状态。
     *
     * @return ScheduleCell
     */
    private function resolveCell(
        Room $room,
        CarbonImmutable $slotStart,
        CarbonImmutable $slotEnd
    ): array {
        /*
         * 检查当前时段是否和维修记录重叠。
         */
        $maintenance =
            $room->maintenances->first(
                fn (RoomMaintenance $item) => $this->overlaps(
                    $item->starts_at,
                    $item->ends_at,
                    $slotStart,
                    $slotEnd
                )
            );

        if (
            $room->status === 'maintenance'
            || $maintenance
        ) {
            return [
                'status' => 'maintenance',
                'maintenance' => $maintenance,
                'reservation' => null,
            ];
        }

        /*
         * 房间基本状态为 unavailable。
         */
        if ($room->status === 'unavailable') {
            return [
                'status' => 'unavailable',
                'maintenance' => null,
                'reservation' => null,
            ];
        }

        /*
         * 检查当前时段是否和预约记录重叠。
         */
        $reservation =
            $room->reservations->first(
                fn (RoomReservation $item) => $this->overlaps(
                    $item->starts_at,
                    $item->ends_at,
                    $slotStart,
                    $slotEnd
                )
            );

        if ($reservation) {
            return [
                'status' => 'reserved',
                'maintenance' => null,
                'reservation' => $reservation,
            ];
        }

        return [
            'status' => 'available',
            'maintenance' => null,
            'reservation' => null,
        ];
    }

    /**
     * 判断两个时间范围是否重叠。
     */
    private function overlaps(
        CarbonImmutable $itemStart,
        CarbonImmutable $itemEnd,
        CarbonImmutable $slotStart,
        CarbonImmutable $slotEnd
    ): bool {
        return $itemStart->lessThan($slotEnd)
            && $itemEnd->greaterThan($slotStart);
    }
}
