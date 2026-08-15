<?php

namespace App\Http\Controllers;

use App\Models\LibrarySetting;
use App\Models\Room;
use App\Models\RoomMaintenance;
use App\Models\RoomReservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        /*
         * 取得目前登录的用户。
         */
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        /*
         * 记录当前时间。
         *
         * 后面的 reservation 和 maintenance
         * 都会根据这个时间判断是否正在进行。
         */
        $now = now();

        /*
 * 读取 Exam Period 设置，让 Dashboard 可以提醒用户。
 */
        $librarySetting = LibrarySetting::current();

        $today = CarbonImmutable::today();

        /*
         * 今天是否正在 Exam Period 范围内。
         */
        $examPeriodActive =
            $librarySetting->isExamPeriodActiveFor(
                $today
            );

        /*
         * Exam Period 已设置，但开始日期还没到。
         */
        $examPeriodUpcoming =
            $librarySetting->exam_period_enabled
            && $librarySetting->exam_period_starts_on !== null
            && $today->lessThan(
                $librarySetting
                    ->exam_period_starts_on
                    ->startOfDay()
            );

        /*
         * 找出当前正在 Maintenance 的房间 ID。
         *
         * 必须满足：
         * 1. 状态是 scheduled 或 in_progress
         * 2. 开始时间已经到达
         * 3. 结束时间还没有到达
         */
        $maintenanceRoomIds = RoomMaintenance::query()
            ->whereIn('status', [
                RoomMaintenance::STATUS_SCHEDULED,
                RoomMaintenance::STATUS_IN_PROGRESS,
            ])
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>', $now)
            ->pluck('room_id');

        /*
         * 找出当前正在被预约使用的房间 ID。
         */
        $reservedRoomIds = RoomReservation::query()
            ->where(
                'status',
                RoomReservation::STATUS_CONFIRMED
            )
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>', $now)
            ->pluck('room_id');

        /*
         * 准备 Dashboard 顶部的房间统计。
         */
        $roomStats = [
            /*
             * 系统中的全部房间。
             */
            'total' => Room::query()->count(),

            /*
             * Available：
             * 房间基本状态必须是 available，
             * 而且目前没有 maintenance 和 reservation。
             */
            'available' => Room::query()
                ->where('status', 'available')
                ->whereNotIn('id', $maintenanceRoomIds)
                ->whereNotIn('id', $reservedRoomIds)
                ->count(),

            /*
             * Reserved：
             * 目前在 reservation 时间范围内，
             * 同时没有进入 maintenance。
             */
            'reserved' => Room::query()
                ->whereIn('id', $reservedRoomIds)
                ->whereNotIn('id', $maintenanceRoomIds)
                ->count(),

            /*
             * 基本状态被 librarian 标记为 unavailable。
             */
            'unavailable' => Room::query()
                ->where('status', 'unavailable')
                ->count(),

            /*
             * Maintenance：
             * 房间状态本身是 maintenance，
             * 或者目前存在有效的 maintenance record。
             */
            'maintenance' => Room::query()
                ->where(function ($query) use (
                    $maintenanceRoomIds
                ) {
                    $query
                        ->where('status', 'maintenance')
                        ->orWhereIn(
                            'id',
                            $maintenanceRoomIds
                        );
                })
                ->count(),
        ];

        /*
         * 最近更新的 5 个房间。
         */
        $recentRooms = Room::query()
            ->with('creator')
            ->latest('updated_at')
            ->limit(5)
            ->get();

        /*
         * 接下来即将发生或正在进行的 Maintenance。
         *
         * ends_at > now 代表还没有结束。
         */
        $upcomingMaintenances = RoomMaintenance::query()
            ->with('room')
            ->whereIn('status', [
                RoomMaintenance::STATUS_SCHEDULED,
                RoomMaintenance::STATUS_IN_PROGRESS,
            ])
            ->where('ends_at', '>', $now)
            ->orderBy('starts_at')
            ->limit(5)
            ->get();

        /*
         * 接下来即将发生或正在进行的 Reservation。
         *
         * Librarian：看到所有人的预约。
         * Student：只看到自己的预约。
         */
        $upcomingReservations = RoomReservation::query()
            ->with([
                'room',
                'user',
            ])
            ->where(
                'status',
                RoomReservation::STATUS_CONFIRMED
            )
            ->where('ends_at', '>', $now)
            ->when(
                $user->isStudent(),
                fn ($query) => $query->where(
                    'user_id',
                    $user->id
                ),
            )
            ->orderBy('starts_at')
            ->limit(5)
            ->get();

      /*
|--------------------------------------------------------------------------
| Student Dashboard
|--------------------------------------------------------------------------
|
| Student 不使用 Admin Dashboard。
| 只传送学生真正需要的资料：
|
| 1. 房间即时可用数量
| 2. 学生自己的 upcoming reservations
| 3. Library operating hours
| 4. Exam Period 状态
|
| 不传送 Recently Updated Rooms、
| Maintenance Management 或管理员操作资料。
*/
if ($user->isStudent()) {
    return view('dashboard.student', [
        'roomStats' => $roomStats,
        'upcomingReservations' => $upcomingReservations,
        'librarySetting' => $librarySetting,
        'examPeriodActive' => $examPeriodActive,
        'examPeriodUpcoming' => $examPeriodUpcoming,
    ]);
}

/*
|--------------------------------------------------------------------------
| Librarian Dashboard
|--------------------------------------------------------------------------
|
| Librarian 保留原本 Dashboard，
| 下一部分会将它重新设计成 Library Operations Dashboard。
*/
return view('dashboard', compact(
    'roomStats',
    'recentRooms',
    'upcomingMaintenances',
    'upcomingReservations',
    'librarySetting',
    'examPeriodActive',
    'examPeriodUpcoming',
));
    }
}
