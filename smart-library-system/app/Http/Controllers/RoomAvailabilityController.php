<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Services\RoomAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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
         * 页面接收日期和房间筛选条件。
         *
         * 这些条件会传到 Service 的 Room Query，
         * 因此统计卡与时间表只计算筛选后的房间。
         */
        $validated = Validator::make(
            $request->query(),
            [
                'date' => [
                    'nullable',
                    'date_format:Y-m-d',
                ],
                'search' => [
                    'nullable',
                    'string',
                    'max:100',
                ],
                'type' => [
                    'nullable',
                    Rule::in(Room::ALLOWED_TYPES),
                ],
                'capacity' => [
                    'nullable',
                    'integer',
                    'min:1',
                    'max:1000',
                ],
                'location' => [
                    'nullable',
                    'string',
                    'max:100',
                ],
                /*
                 * facilities[] 支援多选；保留旧的 facility
                 * 是为了让已经复制或收藏的旧筛选 URL 仍然可以使用。
                 */
                'facilities' => [
                    'nullable',
                    'array',
                ],
                'facilities.*' => [
                    Rule::in(Room::ALLOWED_FACILITIES),
                ],
                'facility' => [
                    'nullable',
                    Rule::in(Room::ALLOWED_FACILITIES),
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
         * 将新的 facilities[] 和旧的 facility 合并。
         * array_unique() 避免同一个设施被重复筛选。
         */
        $selectedFacilities = array_values(
            array_unique(
                array_merge(
                    $validated['facilities'] ?? [],
                    isset($validated['facility'])
                        ? [$validated['facility']]
                        : []
                )
            )
        );

        /*
         * 根据数据库中的 Room、Reservation
         * 和 Maintenance 动态建立时间表。
         */
        $filters = array_filter(
            [
                'search' => trim((string) ($validated['search'] ?? '')),
                'type' => $validated['type'] ?? null,
                'capacity' => $validated['capacity'] ?? null,
                'location' => $validated['location'] ?? null,
                'facilities' => $selectedFacilities,
            ],
            function ($value): bool {
                if (is_array($value)) {
                    return $value !== [];
                }

                return $value !== null && $value !== '';
            }
        );

        $availability = $availabilityService->forDate(
            $selectedDate,
            $filters
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
                'filters' => $filters,
                'locations' => Room::query()
                    ->whereNotNull('location')
                    ->where('location', '!=', '')
                    ->distinct()
                    ->orderBy('location')
                    ->pluck('location'),

                /*
                 * 只在用户已选择 Room type 和 Floor 后，
                 * 才显示真实存在的 capacity 选项，避免让用户猜人数。
                 *
                 * Facility 是独立的额外条件，不应该阻止用户先按人数筛选。
                 */
                'capacityOptions' => filled(
                    $validated['location'] ?? null
                ) && filled($validated['type'] ?? null)
                    ? Room::query()
                        ->where('location', $validated['location'])
                        ->where('type', $validated['type'])
                        ->orderBy('capacity')
                        ->pluck('capacity')
                        ->unique()
                        ->values()
                        ->all()
                    : [],

                /*
                 * 由真实房间记录建立 type -> location mapping。
                 * 前端只在一个 type 只对应一个 location 时才自动填入，
                 * 因此没有把 Study = First Floor 写死在代码里。
                 */
                'typeLocations' => Room::query()
                    ->whereIn('type', Room::ALLOWED_TYPES)
                    ->whereNotNull('location')
                    ->where('location', '!=', '')
                    ->select(['type', 'location'])
                    ->distinct()
                    ->orderBy('type')
                    ->orderBy('location')
                    ->get()
                    ->groupBy('type')
                    ->map(
                        fn ($typeRooms): array => $typeRooms
                            ->pluck('location')
                            ->values()
                            ->all()
                    )
                    ->all(),

                /*
                 * 和 typeLocations 相反的 mapping：location -> type。
                 *
                 * 例如数据库中 First Floor 只有 Study 房间时，前端选择
                 * First Floor 就会自动选择 Study。若将来同一楼层有多种
                 * 房型，则不自动覆盖用户的选择。
                 */
                'locationTypes' => Room::query()
                    ->whereIn('type', Room::ALLOWED_TYPES)
                    ->whereNotNull('location')
                    ->where('location', '!=', '')
                    ->select(['location', 'type'])
                    ->distinct()
                    ->orderBy('location')
                    ->orderBy('type')
                    ->get()
                    ->groupBy('location')
                    ->map(
                        fn ($locationRooms): array => $locationRooms
                            ->pluck('type')
                            ->values()
                            ->all()
                    )
                    ->all(),
            ]
        );
    }
}
