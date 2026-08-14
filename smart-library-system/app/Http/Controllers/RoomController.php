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
use Illuminate\View\View;

class RoomController extends Controller
{
    /**
     * Display a listing of rooms.
     */
    public function index(): View
    {
        Gate::authorize('viewAny', Room::class);
        $rooms = Room::query()
            ->with('creator')
            ->orderBy('room_number', 'asc')
            ->paginate(10);

        return view('rooms.index', [
            'rooms' => $rooms,
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
    public function edit(Room $room): View
    {
        Gate::authorize('update', $room);

        return view('rooms.edit', [
            'room' => $room,
            'roomTypes' => Room::ALLOWED_TYPES,
            'roomStatuses' => Room::ALLOWED_STATUSES,
            'roomFacilities' => Room::ALLOWED_FACILITIES,
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
    $page = max(1, (int) $request->input('page', 1));

    return redirect()
        ->route('rooms.index', [
            'page' => $page,
        ])
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

    $page = max(1, (int) $request->input('page', 1));

    $hasReservations = RoomReservation::query()
        ->where('room_id', $room->id)
        ->exists();

    if ($hasReservations) {
        return redirect()
            ->route('rooms.index', [
                'page' => $page,
            ])
->with(
    'error',
    'This room has reservation records and cannot be deleted. Reservation history must be retained.'
);
    }

    $room->delete();

    return redirect()
        ->route('rooms.index', [
            'page' => $page,
        ])
        ->with('success', 'Room deleted successfully.');
    }
}