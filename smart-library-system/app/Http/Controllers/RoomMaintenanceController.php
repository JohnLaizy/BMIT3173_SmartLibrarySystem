<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoomMaintenanceRequest;
use App\Http\Requests\UpdateRoomMaintenanceRequest;
use App\Models\Room;
use App\Models\RoomMaintenance;
use App\Models\User;
use App\Services\RoomMaintenanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
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
        Request $request,
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

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => [
                'nullable',
                Rule::in([
                    RoomMaintenance::STATUS_COMPLETED,
                    RoomMaintenance::STATUS_CANCELLED,
                ]),
            ],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $status = $validated['status'] ?? null;

        /*
         * Dashboard 部分：只显示尚未结束的维修。
         * 这让管理员打开页面后优先看到真正需要处理的记录。
         */
        // Current Schedule 与 History 一样每页固定五笔，避免卡片因记录增多而变长。
        $currentMaintenances = RoomMaintenance::query()
            ->with(['room', 'creator'])
            ->whereIn('status', [
                RoomMaintenance::STATUS_SCHEDULED,
                RoomMaintenance::STATUS_IN_PROGRESS,
            ])
            ->orderBy('starts_at')
            ->paginate(5, ['*'], 'current_page')
            ->withQueryString();

        /*
         * History 部分：只显示 completed / cancelled。
         * Search 会同时查找房间、标题、描述和状态，
         * 并保留到分页链接中。
         */
        $historyMaintenances = RoomMaintenance::query()
            ->with(['room', 'creator'])
            ->whereIn('status', [
                RoomMaintenance::STATUS_COMPLETED,
                RoomMaintenance::STATUS_CANCELLED,
            ])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('room', function ($query) use ($search): void {
                            $query
                                ->where('room_number', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('ends_at')
            ->paginate(5)
            ->withQueryString();

        return view(
            'maintenances.index',
            compact(
                'currentMaintenances',
                'historyMaintenances',
                'search',
                'status'
            )
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
