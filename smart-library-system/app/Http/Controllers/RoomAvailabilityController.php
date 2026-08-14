<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Services\RoomAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class RoomAvailabilityController extends Controller
{
    /**
     * 显示指定日期的房间可用性时间表。
     */
    public function __invoke(
        Request $request,
        RoomAvailabilityService $availabilityService
    ): View {
        /*
         * Student 和 Librarian 都可以查看
         * Room Availability。
         */
        Gate::authorize(
            'viewAvailability',
            Room::class
        );

        /*
         * Availability 页面现在只接收日期参数。
         */
        $validated = Validator::make(
            $request->query(),
            [
                'date' => [
                    'nullable',
                    'date_format:Y-m-d',
                ],
            ]
        )->validate();

        /*
         * 如果用户没有选择日期，
         * 默认显示今天的时间表。
         */
        $selectedDate = isset($validated['date'])
            ? CarbonImmutable::createFromFormat(
                'Y-m-d',
                $validated['date']
            )->startOfDay()
            : CarbonImmutable::today();

        /*
         * 根据数据库中的 Room、Reservation
         * 和 Maintenance 动态建立时间表。
         */
        $availability = $availabilityService->forDate(
            $selectedDate
        );

        return view(
            'room-availability.index',
            [
                /*
                 * 展开以下动态数据：
                 *
                 * rooms
                 * slots
                 * schedule
                 * summary
                 */
                ...$availability,

                'selectedDate' => $selectedDate,
            ]
        );
    }
}
