<?php

namespace App\Services;

use App\Models\LibrarySetting;
use App\Models\Room;
use App\Models\RoomMaintenance;
use App\Models\RoomReservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoomReservationService
{
    /**
     * 单次预约最长四小时。
     */
    public const MAX_DURATION_MINUTES = 240;

    /**
     * 建立房间预约。
     *
     * @param array{
     *     room_id: int,
     *     user_id: int|null,
     *     purpose: string,
     *     starts_at: string,
     *     ends_at: string
     * } $data
     */
    public function create(
        User $actor,
        array $data
    ): RoomReservation {
        return DB::transaction(
            function () use ($actor, $data) {
                /*
                 * 锁定房间，避免两个用户同时预约
                 * 同一个房间及时间。
                 */
                $room = Room::query()
                    ->whereKey($data['room_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                /*
                 * 根据当前用户角色解析
                 * Reservation 所属的 Student。
                 *
                 * Student:
                 * - 只能替自己预约。
                 *
                 * Librarian:
                 * - 可以替指定的 Student 预约。
                 */
                $resolver =
                    $this->targetResolverFor($actor);

                $targetUser =
                    $resolver->resolveTargetUser(
                        $actor,
                        $data
                    );

                /*
                 * Laravel 会根据 config/app.php 的时区
                 * 解析预约时间。
                 */
                $startsAt = CarbonImmutable::parse(
                    $data['starts_at']
                );

                $endsAt = CarbonImmutable::parse(
                    $data['ends_at']
                );

                /*
                 * 使用数据库中的 Library Setting
                 * 验证普通时间或 Exam Period。
                 */
                $this->validateTimeWindow(
                    $startsAt,
                    $endsAt
                );

                /*
                 * 检查房间状态、维修和其他预约。
                 */
                $this->ensureRoomCanBeReserved(
                    $room,
                    $startsAt,
                    $endsAt
                );

                /*
                 * 检查 Student 同一时间是否已经
                 * 预约其他房间。
                 */
                $this->ensureUserIsFree(
                    $targetUser,
                    $startsAt,
                    $endsAt
                );

                return RoomReservation::query()
                    ->create([
                        'room_id' => $room->id,
                        'user_id' => $targetUser->id,
                        'purpose' => $data['purpose'],
                        'starts_at' => $startsAt,
                        'ends_at' => $endsAt,
                        'status' =>
                            RoomReservation::STATUS_CONFIRMED,
                    ])
                    ->load([
                        'room',
                        'user',
                    ]);
            }
        );
    }

    /**
     * 修改现有预约。
     *
     * @param array{
     *     room_id: int,
     *     user_id: int|null,
     *     purpose: string,
     *     starts_at: string,
     *     ends_at: string
     * } $data
     */
    public function update(
        RoomReservation $reservation,
        User $actor,
        array $data
    ): RoomReservation {
        return DB::transaction(
            function () use (
                $reservation,
                $actor,
                $data
            ) {
                /*
                 * 锁定当前预约。
                 */
                $lockedReservation =
                    RoomReservation::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $reservation->id
                        );

                /*
                 * 只有仍然 Confirmed，
                 * 而且尚未开始的预约才允许修改。
                 */
                if (
                    $lockedReservation->status
                        !== RoomReservation::STATUS_CONFIRMED
                    || $lockedReservation->starts_at->isPast()
                ) {
                    throw ValidationException::withMessages([
                        'reservation' =>
                            'This reservation can no longer be updated.',
                    ]);
                }

                /*
                 * 锁定新的目标房间。
                 */
                $room = Room::query()
                    ->whereKey($data['room_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                /*
                 * 根据当前用户角色解析
                 * Reservation 所属的 Student。
                 */
                $resolver =
                    $this->targetResolverFor($actor);

                $targetUser =
                    $resolver->resolveTargetUser(
                        $actor,
                        $data,
                        $lockedReservation
                    );

                $startsAt = CarbonImmutable::parse(
                    $data['starts_at']
                );

                $endsAt = CarbonImmutable::parse(
                    $data['ends_at']
                );

                /*
                 * 检查预约时间。
                 */
                $this->validateTimeWindow(
                    $startsAt,
                    $endsAt
                );

                /*
                 * 检查目标房间。
                 *
                 * Update 时忽略当前 Reservation，
                 * 否则系统会认为预约和自己发生冲突。
                 */
                $this->ensureRoomCanBeReserved(
                    $room,
                    $startsAt,
                    $endsAt,
                    $lockedReservation->id
                );

                /*
                 * 检查目标 Student 是否在同一时间
                 * 已经拥有其他预约。
                 *
                 * 同样忽略当前 Reservation。
                 */
                $this->ensureUserIsFree(
                    $targetUser,
                    $startsAt,
                    $endsAt,
                    $lockedReservation->id
                );

                $lockedReservation->update([
                    'room_id' => $room->id,
                    'user_id' => $targetUser->id,
                    'purpose' => $data['purpose'],
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                ]);

                return $lockedReservation
                    ->refresh()
                    ->load([
                        'room',
                        'user',
                    ]);
            }
        );
    }

    /**
     * 根据当前用户角色决定
     * Reservation 所属 Student 的解析方式。
     */
    private function targetResolverFor(
        User $actor
    ): RoomReservationTargetResolver {
        if ($actor->isLibrarian()) {
            return new LibrarianRoomReservationTargetResolver();
        }

        if ($actor->isStudent()) {
            return new StudentRoomReservationTargetResolver();
        }

        throw ValidationException::withMessages([
            'user' =>
                'This user is not allowed to make room reservations.',
        ]);
    }

    /**
     * 取消预约。
     */
    public function cancel(
        RoomReservation $reservation,
        User $actor
    ): RoomReservation {
        return DB::transaction(
            function () use ($reservation, $actor) {
                $lockedReservation =
                    RoomReservation::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $reservation->id
                        );

                if (! $lockedReservation->canBeCancelled()) {
                    throw ValidationException::withMessages([
                        'reservation' =>
                            'This reservation can no longer be cancelled.',
                    ]);
                }

                $lockedReservation->update([
                    'status' =>
                        RoomReservation::STATUS_CANCELLED,

                    'cancelled_at' => now(),

                    'cancelled_by' => $actor->id,
                ]);

                return $lockedReservation->refresh();
            }
        );
    }

    /**
     * 验证预约时间是否符合图书馆营业时间。
     */
    private function validateTimeWindow(
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt
    ): void {
        /*
         * 开始时间必须属于未来，
         * 结束时间必须晚于开始时间。
         */
        if (
            $startsAt->isPast()
            || $endsAt->lessThanOrEqualTo($startsAt)
        ) {
            throw ValidationException::withMessages([
                'starts_at' =>
                    'Select a future reservation time.',
            ]);
        }

        /*
         * 预约必须使用整点或半点。
         */
        if (
            ! in_array(
                $startsAt->minute,
                [0, 30],
                true
            )
            || ! in_array(
                $endsAt->minute,
                [0, 30],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'starts_at' =>
                    'Reservation times must use 30-minute intervals.',
            ]);
        }

        /*
         * 从数据库读取目前开放模式。
         */
        $librarySetting = LibrarySetting::current();

        /*
         * 找出这个预约所属的营业日期。
         *
         * Exam Period 的第二天 00:00 至 01:00，
         * 实际上属于前一天开始的营业时间。
         */
        $businessDate = $this->businessDateFor(
            $startsAt,
            $librarySetting
        );

        $openingAt =
            $librarySetting->openingAt(
                $businessDate
            );

        $closingAt =
            $librarySetting->closingAt(
                $businessDate
            );

        /*
         * 开始和结束时间都必须在同一个
         * 营业时间范围内。
         */
        if (
            $startsAt->lessThan($openingAt)
            || $endsAt->greaterThan($closingAt)
        ) {
            $openingLabel =
                $openingAt->format('g:i A');

            $closingLabel =
                $closingAt->format('g:i A');

            $nextDayLabel =
                $librarySetting->closesNextDay()
                    ? ' on the next day'
                    : '';

            throw ValidationException::withMessages([
                'starts_at' =>
                    "Reservations are available from {$openingLabel} ".
                    "to {$closingLabel}{$nextDayLabel}.",
            ]);
        }

        /*
         * 单次预约最长四小时。
         */
        if (
            $startsAt->diffInMinutes($endsAt)
            > self::MAX_DURATION_MINUTES
        ) {
            throw ValidationException::withMessages([
                'ends_at' =>
                    'A reservation cannot be longer than 4 hours.',
            ]);
        }
    }

    /**
     * 判断预约属于哪一个营业日期。
     *
     * 例如 Exam Period：
     *
     * 10 August 11:00 PM
     * 至
     * 11 August 1:00 AM
     *
     * 都属于 10 August 的营业时间。
     */
    private function businessDateFor(
        CarbonImmutable $startsAt,
        LibrarySetting $librarySetting
    ): CarbonImmutable {
        /*
         * 如果 Exam Period 跨到第二天，
         * 而预约开始时间早于关闭小时，
         * 表示它属于前一天的营业时段。
         */
        if (
            $librarySetting->closesNextDay()
            && $startsAt->hour
                < $librarySetting->closingHour()
        ) {
            return $startsAt
                ->subDay()
                ->startOfDay();
        }

        return $startsAt->startOfDay();
    }

    /**
     * 检查房间是否可以预约。
     *
     * Update 时可以传入 ignoredReservationId，
     * 让系统忽略正在修改的预约本身。
     */
    private function ensureRoomCanBeReserved(
        Room $room,
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
        ?int $ignoredReservationId = null
    ): void {
        if ($room->status !== 'available') {
            throw ValidationException::withMessages([
                'room_id' =>
                    'This room is currently unavailable for reservation.',
            ]);
        }

        /*
         * 检查是否和维修时间重叠。
         */
        $maintenanceConflict =
            RoomMaintenance::query()
                ->where(
                    'room_id',
                    $room->id
                )
                ->whereIn(
                    'status',
                    [
                        RoomMaintenance::STATUS_SCHEDULED,
                        RoomMaintenance::STATUS_IN_PROGRESS,
                    ]
                )
                ->where(
                    'starts_at',
                    '<',
                    $endsAt
                )
                ->where(
                    'ends_at',
                    '>',
                    $startsAt
                )
                ->exists();

        if ($maintenanceConflict) {
            throw ValidationException::withMessages([
                'room_id' =>
                    'The selected time overlaps room maintenance.',
            ]);
        }

        /*
         * 检查房间是否已经被其他预约占用。
         */
        $reservationConflictQuery =
            RoomReservation::query()
                ->where(
                    'room_id',
                    $room->id
                )
                ->where(
                    'status',
                    RoomReservation::STATUS_CONFIRMED
                )
                ->where(
                    'starts_at',
                    '<',
                    $endsAt
                )
                ->where(
                    'ends_at',
                    '>',
                    $startsAt
                );

        /*
         * Update 时排除预约本身。
         */
        if ($ignoredReservationId !== null) {
            $reservationConflictQuery->where(
                'id',
                '!=',
                $ignoredReservationId
            );
        }

        if ($reservationConflictQuery->exists()) {
            throw ValidationException::withMessages([
                'room_id' =>
                    'The selected room has already been reserved.',
            ]);
        }
    }

    /**
     * 检查 Student 同一时间是否有其他预约。
     *
     * Update 时可以忽略当前预约本身。
     */
    private function ensureUserIsFree(
        User $user,
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
        ?int $ignoredReservationId = null
    ): void {
        $conflictQuery =
            RoomReservation::query()
                ->where(
                    'user_id',
                    $user->id
                )
                ->where(
                    'status',
                    RoomReservation::STATUS_CONFIRMED
                )
                ->where(
                    'starts_at',
                    '<',
                    $endsAt
                )
                ->where(
                    'ends_at',
                    '>',
                    $startsAt
                );

        /*
         * Update 时排除当前预约。
         */
        if ($ignoredReservationId !== null) {
            $conflictQuery->where(
                'id',
                '!=',
                $ignoredReservationId
            );
        }

        if ($conflictQuery->exists()) {
            throw ValidationException::withMessages([
                'user_id' =>
                    'The student already has a reservation at this time.',
            ]);
        }
    }
}