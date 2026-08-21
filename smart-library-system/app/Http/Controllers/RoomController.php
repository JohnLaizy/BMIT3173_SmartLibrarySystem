<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Models\Room;
use App\Models\RoomReservation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoomController extends Controller
{
    /**
     * Display a listing of rooms.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Room::class);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::in(Room::ALLOWED_TYPES)],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'location' => ['nullable', 'string', 'max:100'],
            'facilities' => ['nullable', 'array'],
            'facilities.*' => [Rule::in(Room::ALLOWED_FACILITIES)],
        ]);

        $filters = array_filter(
            [
                'search' => trim((string) ($validated['search'] ?? '')),
                'type' => $validated['type'] ?? null,
                'capacity' => $validated['capacity'] ?? null,
                'location' => $validated['location'] ?? null,
                'facilities' => array_values(
                    array_unique($validated['facilities'] ?? [])
                ),
            ],
            function ($value): bool {
                if (is_array($value)) {
                    return $value !== [];
                }

                return $value !== null && $value !== '';
            }
        );

        $search = $filters['search'] ?? '';

        $rooms = Room::query()
            ->with('creator')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('room_number', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('facilities', 'like', "%{$search}%");
                });
            })
            ->when(
                filled($filters['location'] ?? null),
                fn ($query) => $query->where(
                    'location',
                    $filters['location']
                )
            )
            ->when(
                filled($filters['type'] ?? null),
                fn ($query) => $query->where('type', $filters['type'])
            )
            ->when(
                filled($filters['capacity'] ?? null),
                fn ($query) => $query->where(
                    'capacity',
                    '>=',
                    $filters['capacity']
                )
            )
            ->when(
                filled($filters['facilities'] ?? null),
                function ($query) use ($filters): void {
                    foreach ($filters['facilities'] as $facility) {
                        $query->whereJsonContains('facilities', $facility);
                    }
                }
            )
            ->orderBy('room_number', 'asc')
            ->paginate(5)
            ->withQueryString();

        return view('rooms.index', [
            'rooms' => $rooms,
            'search' => $search,
            'filters' => $filters,
            'locations' => Room::query()
                ->whereNotNull('location')
                ->where('location', '!=', '')
                ->distinct()
                ->orderBy('location')
                ->pluck('location'),
            /*
             * 人数选项只依赖 Room Type + Floor。
             * Facility 是额外的筛选条件，不应阻止用户先选择人数。
             */
            'capacityOptions' => filled($filters['location'] ?? null)
                && filled($filters['type'] ?? null)
                ? Room::query()
                    ->where('location', $filters['location'])
                    ->where('type', $filters['type'])
                    ->orderBy('capacity')
                    ->pluck('capacity')
                    ->unique()
                    ->values()
                    ->all()
                : [],
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
            'typeLocations' => $this->typeLocations(),
        ]);
    }

    /**
     * Show the form for creating a room.
     */
    public function create(): View
    {
        Gate::authorize('create', Room::class);

        return view('rooms.create', [
            'roomTypes' => Room::ALLOWED_TYPES,
            'roomStatuses' => Room::ALLOWED_STATUSES,
            'roomFacilities' => Room::ALLOWED_FACILITIES,
            'typeLocations' => $this->typeLocations(),
        ]);
    }

    /**
     * Store a newly created room.
     */
    public function store(
        StoreRoomRequest $request
    ): RedirectResponse {

        Gate::authorize('create', Room::class);
        $user = $request->user();

        if (! $user instanceof User) {
            throw new AuthorizationException;
        }

        $room = new Room($request->validated());

        $room->creator()->associate($user);

        $room->save();

        return redirect()
            ->route('rooms.index')
            ->with('success', 'Room created successfully.');
    }

    /**
     * Display the specified room.
     */
    public function show(Room $room): View
    {
        Gate::authorize('view', $room);
        $room->load('creator');

        return view('rooms.show', [
            'room' => $room,
        ]);
    }

    /**
     * Show the form for editing a room.
     */
    public function edit(Request $request, Room $room): View
    {
        Gate::authorize('update', $room);

        return view('rooms.edit', [
            'room' => $room,
            'roomTypes' => Room::ALLOWED_TYPES,
            'roomStatuses' => Room::ALLOWED_STATUSES,
            'roomFacilities' => Room::ALLOWED_FACILITIES,
            'typeLocations' => $this->typeLocations(),
            'search' => trim((string) $request->query('search', '')),
        ]);
    }

  /**
 * Update the specified room.
 *
 * 编辑完成后回到原本的 Room Management 分页。
 * 例如从 rooms?page=2 进入 Edit，保存后仍返回 rooms?page=2。
 */
public function update(
    UpdateRoomRequest $request,
    Room $room
): RedirectResponse {
    Gate::authorize('update', $room);

    $room->update($request->validated());

    /*
     * page 不属于 Room 的数据库资料，所以不使用 validated()。
     * 它只用来决定保存后回到 Room Listing 的哪一页。
     */
    $indexParameters = $this->indexParameters($request);

    return redirect()
        ->route('rooms.index', $indexParameters)
        ->with('success', 'Room updated successfully.');
}

/**
 * Remove the specified room.
 *
 * 安全规则：
 * 只要这个 Room 有任何 reservation record，就不能删除。
 * 这样不会因为删除 Room 而让预约历史或未来预约一起消失。
 */
public function destroy(
    Request $request,
    Room $room
): RedirectResponse {
    Gate::authorize('delete', $room);

    $indexParameters = $this->indexParameters($request);

    $hasReservations = RoomReservation::query()
        ->where('room_id', $room->id)
        ->exists();

    if ($hasReservations) {
        return redirect()
            ->route('rooms.index', $indexParameters)
->with(
    'error',
    'This room has reservation records and cannot be deleted. Reservation history must be retained.'
);
    }

    $room->delete();

    return redirect()
        ->route('rooms.index', $indexParameters)
        ->with('success', 'Room deleted successfully.');
    }

    /**
     * 取得 Room Management 目前使用中的页码与筛选值。
     *
     * 编辑或删除完成后传回同一组查询参数，因此使用者会回到原本的
     * 搜索结果和分页位置，而不是不必要地被送回第一页。
     *
     * @return array<string, array<int, string>|int|string>
     */

    private function indexParameters(Request $request): array
{
    $page = $request->query('page');

    if ($page === null) {
        $page = $request->input('page', 1);
    }

    $facilities = $request->query('facilities', []);

    $parameters = [
        'page' => max(1, (int) $page),

        'search' => trim(
            (string) $request->query('search', '')
        ),

        'type' => trim(
            (string) $request->query('type', '')
        ),

        'capacity' => $request->query('capacity') !== null
            ? (int) $request->query('capacity')
            : '',

        'location' => trim(
            (string) $request->query('location', '')
        ),

        'facilities' => is_array($facilities)
            ? array_values(
                array_filter(
                    $facilities,
                    fn ($facility): bool =>
                        is_string($facility)
                        && $facility !== ''
                )
            )
            : [],
    ];

    return array_filter(
        $parameters,
        function ($value): bool {
            if (is_array($value)) {
                return $value !== [];
            }

            return $value !== ''
                && $value !== null;
        }
    );
}




    /**
     * Return the real type-to-location relationships stored in the database.
     *
     * The Create and Edit forms only auto-fill Location when one Room Type has
     * exactly one known location. If future data permits a type on multiple
     * floors, the form deliberately leaves Location unchanged for the librarian
     * to choose instead of guessing incorrectly.
     *
     * @return array<string, array<int, string>>
     */
    private function typeLocations(): array
    {
        return Room::query()
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
            ->all();
    }
}
