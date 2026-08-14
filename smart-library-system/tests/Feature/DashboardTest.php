<?php

namespace Tests\Feature;

use App\Models\LibrarySetting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未登录用户会被送到 Login。
     */
    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(
            route('dashboard')
        );

        $response->assertRedirect(
            route('login')
        );
    }

    /**
     * 已登录用户可以进入 Dashboard。
     */
    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()
            ->student()
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(
                route('dashboard')
            );

        $response->assertOk();
    }

    /**
     * Exam Period 尚未开始时，
     * Dashboard 应该显示 Scheduled 通知。
     */
    public function test_dashboard_shows_upcoming_exam_period_notice(): void
    {
        $user = User::factory()
            ->student()
            ->create();

        $startsOn = CarbonImmutable::tomorrow();

        $endsOn = $startsOn->addWeeks(2);

        LibrarySetting::current()->update([
            'exam_period_enabled' => true,

            'exam_period_starts_on' =>
                $startsOn->format('Y-m-d'),

            'exam_period_ends_on' =>
                $endsOn->format('Y-m-d'),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                route('dashboard')
            );

        $response
            ->assertOk()
            ->assertSee(
                'Exam Period is scheduled'
            )
            ->assertSee(
                $startsOn->format('d M Y')
            )
            ->assertSee(
                $endsOn->format('d M Y')
            );
    }

    /**
     * 当前日期处于 Exam Period 时，
     * Dashboard 应显示 Active 通知。
     */
    public function test_dashboard_shows_active_exam_period_notice(): void
    {
        $user = User::factory()
            ->student()
            ->create();

        LibrarySetting::current()->update([
            'exam_period_enabled' => true,

            'exam_period_starts_on' =>
                CarbonImmutable::yesterday()
                    ->format('Y-m-d'),

            'exam_period_ends_on' =>
                CarbonImmutable::tomorrow()
                    ->format('Y-m-d'),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                route('dashboard')
            );

        $response
            ->assertOk()
            ->assertSee(
                'Exam Period is active'
            )
->assertSee(
    'Reservations after regular closing hours'
)
->assertSee(
    'remain valid during this period.'
);
    }

    /**
     * Exam Period 结束后，即使 enabled 仍然是 true，
     * 日期规则也会让 Exam Period 自动失效，
     * Dashboard 不再显示通知。
     */
    public function test_expired_exam_period_notice_is_hidden(): void
    {
        $user = User::factory()
            ->student()
            ->create();

        LibrarySetting::current()->update([
            'exam_period_enabled' => true,

            'exam_period_starts_on' =>
                CarbonImmutable::today()
                    ->subWeeks(2)
                    ->format('Y-m-d'),

            'exam_period_ends_on' =>
                CarbonImmutable::yesterday()
                    ->format('Y-m-d'),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                route('dashboard')
            );

        $response
            ->assertOk()
            ->assertDontSee(
                'Exam Period is active'
            )
            ->assertDontSee(
                'Exam Period is scheduled'
            );
    }
}