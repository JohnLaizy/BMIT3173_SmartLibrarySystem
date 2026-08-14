<?php

namespace App\Services;

use App\Models\Room;
use App\Models\RoomMaintenance;
use App\Models\RoomReservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoomMaintenanceService
{
    /**
     * 建立维修记录。
     *
     * @param array{
     *     room_id: int,
     *     title: string,
     *     description: string|null,
     *     starts_at: string,
     *     ends_at: string,
     *     status: string
     * } $data
     */
    public function create(
        User $actor,
        array $data
    ): RoomMaintenance {
        return DB::transaction(
            function () use ($actor, $data) {
                $room = Room::query()
                    ->whereKey($data['room_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $startsAt = CarbonImmutable::parse(
                    $data['starts_at']
                );

                $endsAt = CarbonImmutable::parse(
                    $data['ends_at']
                );

                $this->ensureNoConflicts(
                    $room,
                    $startsAt,
                    $endsAt,
                    $data['status']
                );

                return RoomMaintenance::query()->create([
                    ...$data,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'created_by' => $actor->id,
                ]);
            }
        );
    }

    /**
     * 更新维修记录。
     *
     * @param array{
     *     room_id: int,
     *     title: string,
     *     description: string|null,
     *     starts_at: string,
     *     ends_at: string,
     *     status: string
     * } $data
     */
    public function update(
        RoomMaintenance $maintenance,
        array $data
    ): RoomMaintenance {
        return DB::transaction(
            function () use ($maintenance, $data) {
                $room = Room::query()
                    ->whereKey($data['room_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $startsAt = CarbonImmutable::parse(
                    $data['starts_at']
                );

                $endsAt = CarbonImmutable::parse(
                    $data['ends_at']
                );

                $this->ensureNoConflicts(
                    $room,
                    $startsAt,
                    $endsAt,
                    $data['status'],
                    $maintenance->id
                );

                $maintenance->update([
                    ...$data,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                ]);

                return $maintenance->refresh();
            }
        );
    }

    /**
     * 根据维修开始和结束时间，同步维修记录的状态。
     *
     * 状态转换规则：
     *
     * 1. starts_at 还没到
     *    => scheduled
     *
     * 2. starts_at 已到，但 ends_at 还没到
     *    => in_progress
     *
     * 3. ends_at 已经到了
     *    => completed
     *
     * cancelled 不会被自动修改，因为取消是管理员主动执行的状态。
     *
     * @return int 实际被更新的维修记录数量
     */
    public function synchronizeStatuses(
        ?CarbonImmutable $currentTime = null
    ): int {
        /*
         * 测试时可以传入固定时间。
         * 正常运行时没有传值，就使用系统当前时间。
         */
        $now = $currentTime ?? CarbonImmutable::now();

        return DB::transaction(
            function () use ($now): int {
                $updatedRecords = 0;

                /*
                 * 第一种情况：
                 * 维修结束时间已经过去。
                 *
                 * scheduled 和 in_progress 都应该变成 completed。
                 */
                $updatedRecords += RoomMaintenance::query()
                    ->whereIn('status', [
                        RoomMaintenance::STATUS_SCHEDULED,
                        RoomMaintenance::STATUS_IN_PROGRESS,
                    ])
                    ->where('ends_at', '<=', $now)
                    ->update([
                        'status' => RoomMaintenance::STATUS_COMPLETED,
                        'updated_at' => $now,
                    ]);

                /*
                 * 第二种情况：
                 * 维修已经开始，但还没有结束。
                 *
                 * scheduled 应该自动变成 in_progress。
                 */
                $updatedRecords += RoomMaintenance::query()
                    ->where(
                        'status',
                        RoomMaintenance::STATUS_SCHEDULED
                    )
                    ->where('starts_at', '<=', $now)
                    ->where('ends_at', '>', $now)
                    ->update([
                        'status' => RoomMaintenance::STATUS_IN_PROGRESS,
                        'updated_at' => $now,
                    ]);

                /*
                 * 第三种情况：
                 * 数据库可能错误地把未来的维修记录保存成
                 * in_progress。
                 *
                 * 如果维修还没开始，就恢复成 scheduled。
                 */
                $updatedRecords += RoomMaintenance::query()
                    ->where(
                        'status',
                        RoomMaintenance::STATUS_IN_PROGRESS
                    )
                    ->where('starts_at', '>', $now)
                    ->update([
                        'status' => RoomMaintenance::STATUS_SCHEDULED,
                        'updated_at' => $now,
                    ]);

                return $updatedRecords;
            }
        );
    }

    private function ensureNoConflicts(
        Room $room,
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
        string $status,
        ?int $ignoreMaintenanceId = null
    ): void {
        if (
            ! in_array(
                $status,
                [
                    RoomMaintenance::STATUS_SCHEDULED,
                    RoomMaintenance::STATUS_IN_PROGRESS,
                ],
                true
            )
        ) {
            return;
        }

        $maintenanceConflict =
            RoomMaintenance::query()
                ->where('room_id', $room->id)
                ->when(
                    $ignoreMaintenanceId,
                    fn ($query) => $query->where(
                        'id',
                        '!=',
                        $ignoreMaintenanceId
                    )
                )
                ->whereIn('status', [
                    RoomMaintenance::STATUS_SCHEDULED,
                    RoomMaintenance::STATUS_IN_PROGRESS,
                ])
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
                ->exists();

        if ($maintenanceConflict) {
            throw ValidationException::withMessages([
                'starts_at' => 'This room already has maintenance during that time.',
            ]);
        }

        $reservationConflict =
            RoomReservation::query()
                ->where('room_id', $room->id)
                ->where(
                    'status',
                    RoomReservation::STATUS_CONFIRMED
                )
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
                ->exists();

        if ($reservationConflict) {
            throw ValidationException::withMessages([
                'starts_at' => 'Cancel the conflicting reservation before scheduling maintenance.',
            ]);
        }
    }
}
