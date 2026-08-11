<?php

namespace App\Http\Controllers;

use App\Models\LibrarySetting;
use App\Models\RoomReservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LibrarySettingController extends Controller
{
    /**
     * 开启或关闭 Exam Period。
     */
    public function __invoke(
        Request $request
    ): RedirectResponse {
        /*
         * 取得当前图书馆设置。
         */
        $librarySetting = LibrarySetting::current();

        /*
         * 只有 Librarian 可以修改。
         */
        Gate::authorize(
            'update',
            $librarySetting
        );

        $enabled = $request->boolean('enabled');

        /*
         * 开启 Exam Period 时，必须填写开始和结束日期。
         *
         * 关闭时排除日期验证，因为系统会保留原本的日期记录。
         */
        $validated = $request->validate([
            'enabled' => [
                'required',
                'boolean',
            ],

            'exam_period_starts_on' => [
                Rule::excludeIf(! $enabled),
                Rule::requiredIf($enabled),
                'date',
            ],

            'exam_period_ends_on' => [
                Rule::excludeIf(! $enabled),
                Rule::requiredIf($enabled),
                'date',
                'after_or_equal:exam_period_starts_on',
                'after_or_equal:today',
            ],
        ]);

        $user = $request->user();

        abort_unless(
            $user instanceof User,
            403
        );

        DB::transaction(
            function () use (
                $librarySetting,
                $enabled,
                $validated,
                $user
            ): void {
                /*
                 * 锁定设置，防止两位 Librarian
                 * 同时修改 Exam Period。
                 */
                $lockedSetting = LibrarySetting::query()
                    ->whereKey($librarySetting->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                /*
                 * 准备修改后的日期。
                 *
                 * 关闭时保留旧日期记录；
                 * 开启或更新时使用表单提交的新日期。
                 */
                $proposedStartsOn = $enabled
                    ? CarbonImmutable::parse(
                        (string) $validated[
                            'exam_period_starts_on'
                        ]
                    )->startOfDay()
                    : $lockedSetting
                        ->exam_period_starts_on;

                $proposedEndsOn = $enabled
                    ? CarbonImmutable::parse(
                        (string) $validated[
                            'exam_period_ends_on'
                        ]
                    )->startOfDay()
                    : $lockedSetting
                        ->exam_period_ends_on;

                /*
                 * 建立一个尚未保存的设置副本。
                 *
                 * 系统使用它模拟修改后的开放时间，
                 * 不会立刻改变数据库。
                 */
                $proposedSetting = clone $lockedSetting;

                $proposedSetting->forceFill([
                    'exam_period_enabled' => $enabled,

                    'exam_period_starts_on' => $proposedStartsOn,

                    'exam_period_ends_on' => $proposedEndsOn,
                ]);

                /*
                 * 检查修改后是否有未来预约落在
                 * 新开放时间以外。
                 *
                 * 这个检查同时保护：
                 * 1. 关闭 Exam Period
                 * 2. 提前结束日期
                 * 3. 推迟开始日期
                 */
                $conflictingReservations =
                    $this->futureReservationsOutsideOperatingHours(
                        $proposedSetting
                    );

                if ($conflictingReservations->isNotEmpty()) {
                    $latestReservation =
                        $conflictingReservations->last();

                    $latestEndTime =
    $latestReservation
        ->ends_at
        ->timezone(
            (string) config(
                'app.timezone',
                'Asia/Kuala_Lumpur'
            )
        )
        ->format(
            'd M Y, h:i A'
        );

                    $count =
                        $conflictingReservations->count();

                    $errorField = $enabled
                        ? 'exam_period_ends_on'
                        : 'enabled';

                    $message = $enabled
                        ? "Exam Period dates cannot be changed because {$count} confirmed reservation(s) would fall outside the proposed operating hours. "
                        : "Exam Period cannot be disabled because {$count} confirmed after-hours reservation(s) still exist. ";

                    throw ValidationException::withMessages([
                        $errorField => $message
                            ."The latest reservation ends at {$latestEndTime}. "
                            .'Cancel or reschedule these reservations first.',
                    ]);
                }

                /*
                 * 全部预约都安全后才正式保存。
                 */
                $lockedSetting->update([
                    'exam_period_enabled' => $enabled,

                    'exam_period_starts_on' => $proposedStartsOn,

                    'exam_period_ends_on' => $proposedEndsOn,

                    'updated_by' => $user->id,
                ]);
            }
        );

        return back()->with(
            'success',
            $enabled
                ? 'Exam Period settings saved successfully.'
                : 'Exam Period disabled. Library hours are now 8:00 AM to 8:00 PM.'
        );
    }

    /**
     * 找出修改设置后会落在开放时间以外的未来预约。
     *
     * @return Collection<int, RoomReservation>
     */
    private function futureReservationsOutsideOperatingHours(
        LibrarySetting $proposedSetting
    ): Collection {
        return RoomReservation::query()
            ->where(
                'status',
                RoomReservation::STATUS_CONFIRMED
            )
            ->where(
                'ends_at',
                '>',
                CarbonImmutable::now()
            )
            ->orderBy('ends_at')
            ->lockForUpdate()
            ->get()
            ->filter(
                fn (
                    RoomReservation $reservation
                ): bool => $this->isOutsideOperatingHours(
                    $reservation,
                    $proposedSetting
                )
            )
            ->values();
    }

    /**
     * 判断预约是否超出建议设置的开放时间。
     */
    private function isOutsideOperatingHours(
        RoomReservation $reservation,
        LibrarySetting $setting
    ): bool {
        $startsAt = $reservation->starts_at;
        $endsAt = $reservation->ends_at;

        /*
         * 凌晨时段属于前一个营业日期。
         *
         * 例如星期二 12:00 AM，
         * 实际属于星期一 Exam Period 的延长时间。
         */
        $businessDate =
            $startsAt->hour
                < $setting->regular_opening_hour
                    ? $startsAt
                        ->subDay()
                        ->startOfDay()
                    : $startsAt
                        ->startOfDay();

        /*
         * 根据建议设置动态计算开放和关闭时间。
         *
         * 日期在 Exam Period 内：
         * 8:00 AM–1:00 AM。
         *
         * 日期不在范围内：
         * 8:00 AM–8:00 PM。
         */
        $openingAt =
            $setting->openingAt(
                $businessDate
            );

        $closingAt =
            $setting->closingAt(
                $businessDate
            );

        return $startsAt->lessThan(
            $openingAt
        ) || $endsAt->greaterThan(
            $closingAt
        );
    }
}
