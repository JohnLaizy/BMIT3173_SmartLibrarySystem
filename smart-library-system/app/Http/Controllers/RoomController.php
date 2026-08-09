<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Models\Room;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
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
     */
    public function update(
        UpdateRoomRequest $request,
        Room $room
    ): RedirectResponse {
        Gate::authorize('update', $room);
        $room->update($request->validated());

        return redirect()
            ->route('rooms.show', $room)
            ->with('success', 'Room updated successfully.');
    }

    /**
     * Remove the specified room.
     */
    public function destroy(Room $room): RedirectResponse
    {
        Gate::authorize('delete', $room);
        $room->delete();

        return redirect()
            ->route('rooms.index')
            ->with('success', 'Room deleted successfully.');
    }
}
