<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoomReservationRequest;
use App\Http\Requests\UpdateRoomReservationRequest;
use App\Models\Room;
use App\Models\RoomReservation;
use App\Models\User;
use App\Services\CapacityBasedRoomSelectionStrategy;
use App\Services\FacilityBasedRoomSelectionStrategy;
use App\Services\RoomReservationService;
use App\Services\RoomSelectionContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoomReservationController extends Controller
{
    /**
     * 显示预约列表。
     *
     * Librarian 可以查看所有用户的预约。
     * Student 只能查看自己的预约。
     */
    public function index(Request $request): View
    {
        Gate::authorize(
            'viewAny',
            RoomReservation::class
        );

        $user = $request->user();

        abort_unless(
            $user instanceof User,
            403
        );

        /*
         * 取得未来仍然有效的预约。
         */
        $upcomingReservations =
            RoomReservation::query()
                ->with([
                    'room',
                    'user',
                ])
                ->where(
                    'status',
                    RoomReservation::STATUS_CONFIRMED
                )
                ->where('ends_at', '>', now())
                ->when(
                    $user->isStudent(),
                    fn ($query) => $query->where(
                        'user_id',
                        $user->id
                    )
                )
                ->orderBy('starts_at')
                ->paginate(
                    10,
                    ['*'],
                    'upcoming_page'
                );

        /*
         * 取得已经取消或已经结束的预约。
         */
        $reservationHistory =
            RoomReservation::query()
                ->with([
                    'room',
                    'user',
                ])
                ->where(function ($query) {
                    $query
                        ->where(
                            'status',
                            RoomReservation::STATUS_CANCELLED
                        )
                        ->orWhere(
                            'ends_at',
                            '<=',
                            now()
                        );
                })
                ->when(
                    $user->isStudent(),
                    fn ($query) => $query->where(
                        'user_id',
                        $user->id
                    )
                )
                ->latest('starts_at')
                ->paginate(
                    10,
                    ['*'],
                    'history_page'
                );

        return view(
            'room-reservations.index',
            compact(
                'upcomingReservations',
                'reservationHistory'
            )
        );
    }

    /**
     * 显示建立预约页面。
     */
    public function create(Request $request): View
    {
        Gate::authorize(
            'create',
            RoomReservation::class
        );

        $user = $request->user();

        abort_unless(
            $user instanceof User,
            403
        );

        /*
         * 验证从 Availability 页面以及
         * Room Selection Strategy 表单传入的参数。
         */
        $validated = Validator::make(
            $request->query(),
            [
                'date' => [
                    'nullable',
                    'date_format:Y-m-d',
                ],

                'room_id' => [
                    'nullable',
                    'integer',
                    'exists:rooms,id',
                ],

                'start' => [
                    'nullable',
                    'date_format:H:i',
                ],

                'selection_strategy' => [
                    'nullable',
                    Rule::in([
                        'capacity',
                        'facility',
                    ]),
                ],

                'required_capacity' => [
                    'nullable',
                    'integer',
                    'min:1',
                ],

                'required_facilities' => [
                    'nullable',
                    'array',
                ],

                'required_facilities.*' => [
                    'string',
                    Rule::in(
                        Room::ALLOWED_FACILITIES
                    ),
                ],
            ]
        )->validate();

        /*
         * 如果 URL 没有日期，默认使用今天。
         */
        $selectedDate = isset($validated['date'])
            ? CarbonImmutable::createFromFormat(
                'Y-m-d',
                $validated['date']
            )->startOfDay()
            : CarbonImmutable::today();

        $chosenStart = null;

        /*
         * 如果用户从 Availability Slot 进入，
         * 自动建立完整预约开始时间。
         */
        if (isset($validated['start'])) {
            [$hour, $minute] = array_map(
                'intval',
                explode(
                    ':',
                    $validated['start']
                )
            );

            $chosenStart =
                $selectedDate->setTime(
                    $hour,
                    $minute
                );
        }

        /*
         * 先取得所有基本状态为 available 的房间。
         */
        $rooms = Room::query()
            ->where(
                'status',
                'available'
            )
            ->orderBy(
                'room_number'
            )
            ->get();

        /*
         * Strategy Pattern
         *
         * 用户可以在 runtime 选择不同的
         * Room Selection Strategy。
         */
        $selectionStrategy =
            $validated['selection_strategy']
                ?? null;

        $requiredCapacity =
            max(
                1,
                (int) (
                    $validated['required_capacity']
                    ?? 1
                )
            );

        $requiredFacilities =
            $validated['required_facilities']
                ?? [];

        /*
         * Capacity-Based Strategy
         *
         * 根据用户所需人数筛选房间，
         * 并优先显示容量最接近需求的房间。
         */
        if (
            $selectionStrategy === 'capacity'
        ) {
            $context =
                new RoomSelectionContext(
                    new CapacityBasedRoomSelectionStrategy()
                );

            $rooms =
                $context->selectRooms(
                    $rooms,
                    [
                        'capacity' =>
                            $requiredCapacity,
                    ]
                );
        }

        /*
         * Facility-Based Strategy
         *
         * 根据用户要求的设施筛选房间。
         */
        if (
            $selectionStrategy === 'facility'
        ) {
            $context =
                new RoomSelectionContext(
                    new FacilityBasedRoomSelectionStrategy()
                );

            $rooms =
                $context->selectRooms(
                    $rooms,
                    [
                        'facilities' =>
                            $requiredFacilities,
                    ]
                );
        }

        /*
         * Librarian 可以替 Student 预约，
         * 所以需要学生选择列表。
         *
         * Student 不允许选择其他用户。
         */
        $students = $user->isLibrarian()
            ? User::query()
                ->where(
                    'role',
                    User::ROLE_STUDENT
                )
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'email',
                ])
            : collect();

        return view(
            'room-reservations.create',
            [
                'rooms' => $rooms,

                'students' => $students,

                /*
                 * Strategy Pattern UI Data
                 */
                'selectionStrategy' =>
                    $selectionStrategy,

                'requiredCapacity' =>
                    $requiredCapacity,

                'requiredFacilities' =>
                    $requiredFacilities,

                'availableFacilities' =>
                    Room::ALLOWED_FACILITIES,

                /*
                 * Existing reservation defaults
                 */
                'selectedRoomId' =>
                    $validated['room_id']
                        ?? null,

                'defaultStart' =>
                    $chosenStart?->format(
                        'Y-m-d\TH:i'
                    ) ?? '',

                'defaultEnd' =>
                    $chosenStart?->addHour()
                        ->format(
                            'Y-m-d\TH:i'
                        ) ?? '',
            ]
        );
    }

    /**
     * 保存新的预约。
     */
    public function store(
        StoreRoomReservationRequest $request,
        RoomReservationService $reservationService
    ): RedirectResponse {
        $user = $request->user();

        abort_unless(
            $user instanceof User,
            403
        );

        $reservationService->create(
            $user,
            $request->validatedData()
        );

        return redirect()
            ->route(
                'room-reservations.index'
            )
            ->with(
                'success',
                'Room reserved successfully.'
            );
    }

    /**
     * 显示修改预约页面。
     */
    public function edit(
        Request $request,
        RoomReservation $reservation
    ): View {
        Gate::authorize(
            'update',
            $reservation
        );

        $user = $request->user();

        abort_unless(
            $user instanceof User,
            403
        );

        /*
         * 只显示目前可预约的房间。
         */
        $rooms = Room::query()
            ->where(
                'status',
                'available'
            )
            ->orderBy(
                'room_number'
            )
            ->get();

        /*
         * Librarian 可以修改预约所属 Student。
         * Student 只能修改自己的预约。
         */
        $students = $user->isLibrarian()
            ? User::query()
                ->where(
                    'role',
                    User::ROLE_STUDENT
                )
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'email',
                ])
            : collect();

        $reservation->load([
            'room',
            'user',
        ]);

        return view(
            'room-reservations.edit',
            compact(
                'reservation',
                'rooms',
                'students'
            )
        );
    }

    /**
     * 更新预约。
     */
    public function update(
        UpdateRoomReservationRequest $request,
        RoomReservation $reservation,
        RoomReservationService $reservationService
    ): RedirectResponse {
        $user = $request->user();

        abort_unless(
            $user instanceof User,
            403
        );

        $reservationService->update(
            $reservation,
            $user,
            $request->validatedData()
        );

        return redirect()
            ->route(
                'room-reservations.index'
            )
            ->with(
                'success',
                'Reservation updated successfully.'
            );
    }

    /**
     * 取消预约。
     */
    public function cancel(
        Request $request,
        RoomReservation $reservation,
        RoomReservationService $reservationService
    ): RedirectResponse {
        Gate::authorize(
            'cancel',
            $reservation
        );

        $user = $request->user();

        abort_unless(
            $user instanceof User,
            403
        );

        $reservationService->cancel(
            $reservation,
            $user
        );

        return back()->with(
            'success',
            'Reservation cancelled successfully.'
        );
    }
}