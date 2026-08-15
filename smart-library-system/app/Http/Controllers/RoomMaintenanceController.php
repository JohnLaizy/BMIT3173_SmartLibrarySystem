<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoomMaintenanceRequest;
use App\Http\Requests\UpdateRoomMaintenanceRequest;
use App\Models\Room;
use App\Models\RoomMaintenance;
use App\Models\User;
use App\Services\RoomMaintenanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RoomMaintenanceController extends Controller
{
    /**
     * 显示维修管理页面。
     *
     * 进入页面时，先根据当前时间同步所有维修记录状态，
     * 然后才读取和显示维修记录。
     */
    public function index(
        RoomMaintenanceService $maintenanceService
    ): View {
        Gate::authorize(
            'viewAny',
            RoomMaintenance::class
        );

        /*
         * 自动更新：
         *
         * 未开始     => scheduled
         * 进行中     => in_progress
         * 已结束     => completed
         */
        $maintenanceService->synchronizeStatuses();

        /*
         * 同步完成以后，再从数据库读取最新维修记录。
         */
        $maintenances =
            RoomMaintenance::query()
                ->with([
                    'room',
                    'creator',
                ])
                ->orderBy('starts_at')
                ->paginate(10);

        return view(
            'maintenances.index',
            compact('maintenances')
        );
    }

    public function create(): View
    {
        Gate::authorize(
            'create',
            RoomMaintenance::class
        );

        return view(
            'maintenances.create',
            [
                'rooms' => Room::query()
                    ->orderBy('room_number')
                    ->get(),

                'maintenanceStatuses' => RoomMaintenance::ALLOWED_STATUSES,
            ]
        );
    }

    public function store(
        StoreRoomMaintenanceRequest $request,
        RoomMaintenanceService $maintenanceService
    ): RedirectResponse {
        Gate::authorize(
            'create',
            RoomMaintenance::class
        );

        $user = $request->user();

        abort_unless(
            $user instanceof User,
            403
        );

        $maintenanceService->create(
            $user,
            $request->validatedData()
        );

        return redirect()
            ->route('maintenances.index')
            ->with(
                'success',
                'Maintenance scheduled successfully.'
            );
    }

    public function edit(
        RoomMaintenance $maintenance
    ): View {
        Gate::authorize(
            'update',
            $maintenance
        );

        return view(
            'maintenances.edit',
            [
                'maintenance' => $maintenance,

                'rooms' => Room::query()
                    ->orderBy('room_number')
                    ->get(),

                'maintenanceStatuses' => RoomMaintenance::ALLOWED_STATUSES,
            ]
        );
    }

    public function update(
        UpdateRoomMaintenanceRequest $request,
        RoomMaintenance $maintenance,
        RoomMaintenanceService $maintenanceService
    ): RedirectResponse {
        Gate::authorize(
            'update',
            $maintenance
        );

        $maintenanceService->update(
            $maintenance,
            $request->validatedData()
        );

        return redirect()
            ->route('maintenances.index')
            ->with(
                'success',
                'Maintenance updated successfully.'
            );
    }

    public function destroy(
        RoomMaintenance $maintenance
    ): RedirectResponse {
        Gate::authorize(
            'delete',
            $maintenance
        );

        $maintenance->delete();

        return redirect()
            ->route('maintenances.index')
            ->with(
                'success',
                'Maintenance deleted successfully.'
            );
    }
}
