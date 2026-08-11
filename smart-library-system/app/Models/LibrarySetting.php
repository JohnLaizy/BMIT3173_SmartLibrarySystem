<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $regular_opening_hour
 * @property int $regular_closing_hour
 * @property bool $exam_period_enabled
 * @property CarbonImmutable|null $exam_period_starts_on
 * @property CarbonImmutable|null $exam_period_ends_on
 * @property int $exam_opening_hour
 * @property int $exam_closing_hour
 * @property bool $exam_closes_next_day
 * @property int|null $updated_by
 */
class LibrarySetting extends Model
{
    protected $fillable = [
        'regular_opening_hour',
        'regular_closing_hour',
        'exam_period_enabled',
        'exam_period_starts_on',
        'exam_period_ends_on',
        'exam_opening_hour',
        'exam_closing_hour',
        'exam_closes_next_day',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'regular_opening_hour' => 'integer',
            'regular_closing_hour' => 'integer',

            'exam_period_enabled' => 'boolean',

            'exam_period_starts_on' => 'immutable_date',

            'exam_period_ends_on' => 'immutable_date',

            'exam_opening_hour' => 'integer',
            'exam_closing_hour' => 'integer',

            'exam_closes_next_day' => 'boolean',
        ];
    }

    /**
     * 取得系统目前使用的设置。
     */
    public static function current(): self
    {
        return self::query()
            ->orderBy('id')
            ->firstOrFail();
    }

    /**
     * 判断指定营业日期是否属于 Exam Period。
     */
    public function isExamPeriodActiveFor(
        CarbonImmutable $date
    ): bool {
        if (! $this->exam_period_enabled) {
            return false;
        }

        $businessDate = $date->startOfDay();

        /*
         * 兼容旧资料：
         * 如果 Exam Period 已启用，但还没有日期，
         * 暂时继续视为有效。
         */
        if (
            $this->exam_period_starts_on === null
            && $this->exam_period_ends_on === null
        ) {
            return true;
        }

        if (
            $this->exam_period_starts_on !== null
            && $businessDate->lessThan(
                $this->exam_period_starts_on
                    ->startOfDay()
            )
        ) {
            return false;
        }

        if (
            $this->exam_period_ends_on !== null
            && $businessDate->greaterThan(
                $this->exam_period_ends_on
                    ->startOfDay()
            )
        ) {
            return false;
        }

        return true;
    }

    /**
     * 取得指定日期的开放小时。
     */
    public function openingHour(
        ?CarbonImmutable $date = null
    ): int {
        $date ??= CarbonImmutable::now();

        return $this->isExamPeriodActiveFor($date)
            ? $this->exam_opening_hour
            : $this->regular_opening_hour;
    }

    /**
     * 取得指定日期的关闭小时。
     */
    public function closingHour(
        ?CarbonImmutable $date = null
    ): int {
        $date ??= CarbonImmutable::now();

        return $this->isExamPeriodActiveFor($date)
            ? $this->exam_closing_hour
            : $this->regular_closing_hour;
    }

    /**
     * 指定营业日期是否跨到第二天关闭。
     */
    public function closesNextDay(
        ?CarbonImmutable $date = null
    ): bool {
        $date ??= CarbonImmutable::now();

        return $this->isExamPeriodActiveFor($date)
            && $this->exam_closes_next_day;
    }

    /**
     * 取得关闭时间相对于营业日期的小时数。
     */
    public function closingOffsetHour(
        ?CarbonImmutable $date = null
    ): int {
        $date ??= CarbonImmutable::now();

        if ($this->closesNextDay($date)) {
            return 24
                + $this->closingHour($date);
        }

        return $this->closingHour($date);
    }

    /**
     * 取得指定营业日期的完整开放时间。
     */
    public function openingAt(
        CarbonImmutable $date
    ): CarbonImmutable {
        return $date
            ->startOfDay()
            ->addHours(
                $this->openingHour($date)
            );
    }

    /**
     * 取得指定营业日期的完整关闭时间。
     */
    public function closingAt(
        CarbonImmutable $date
    ): CarbonImmutable {
        return $date
            ->startOfDay()
            ->addHours(
                $this->closingOffsetHour($date)
            );
    }

    /**
     * 最后修改设置的 Librarian。
     *
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }
}
