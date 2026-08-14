<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\RoomReservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomReservationAccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        /*
         * 固定测试时间，避免测试结果受电脑当前时间影响。
         */
        CarbonImmutable::setTestNow(
            CarbonImmutable::parse(
                '2026-08-10 10:00:00'
            )
        );
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_student_only_sees_own_reservations(): void
    {
        $student = User::factory()
            ->student()
            ->create();

        $otherStudent = User::factory()
            ->student()
            ->create();

        $room = Room::factory()->create();

        RoomReservation::factory()
            ->for($room)
            ->for($student, 'user')
            ->create([
                'purpose' => 'My private reservation',
                'starts_at' => now()->addDay()->setTime(10, 0),
                'ends_at' => now()->addDay()->setTime(11, 0),
            ]);

        RoomReservation::factory()
            ->for($room)
            ->for($otherStudent, 'user')
            ->create([
                'purpose' => 'Other student reservation',
                'starts_at' => now()->addDays(2)->setTime(10, 0),
                'ends_at' => now()->addDays(2)->setTime(11, 0),
            ]);

        $response = $this
            ->actingAs($student)
            ->get(route('room-reservations.index'));

        $response
            ->assertOk()
            ->assertSee('My private reservation')
            ->assertDontSee('Other student reservation');
    }

    public function test_librarian_can_see_all_reservations(): void
    {
        $librarian = User::factory()
            ->librarian()
            ->create();

        $firstStudent = User::factory()
            ->student()
            ->create();

        $secondStudent = User::factory()
            ->student()
            ->create();

        $room = Room::factory()->create();

        RoomReservation::factory()
            ->for($room)
            ->for($firstStudent, 'user')
            ->create([
                'purpose' => 'First student reservation',
                'starts_at' => now()->addDay()->setTime(10, 0),
                'ends_at' => now()->addDay()->setTime(11, 0),
            ]);

        RoomReservation::factory()
            ->for($room)
            ->for($secondStudent, 'user')
            ->create([
                'purpose' => 'Second student reservation',
                'starts_at' => now()->addDays(2)->setTime(10, 0),
                'ends_at' => now()->addDays(2)->setTime(11, 0),
            ]);

        $response = $this
            ->actingAs($librarian)
            ->get(route('room-reservations.index'));

        $response
            ->assertOk()
            ->assertSee('First student reservation')
            ->assertSee('Second student reservation');
    }

    public function test_student_cannot_cancel_another_students_reservation(): void
    {
        $student = User::factory()
            ->student()
            ->create();

        $otherStudent = User::factory()
            ->student()
            ->create();

        $reservation = RoomReservation::factory()
            ->for($otherStudent, 'user')
            ->create([
                'starts_at' => now()->addDay()->setTime(10, 0),
                'ends_at' => now()->addDay()->setTime(11, 0),
            ]);

        $response = $this
            ->actingAs($student)
            ->patch(
                route(
                    'room-reservations.cancel',
                    $reservation
                )
            );

        $response->assertForbidden();

        $this->assertDatabaseHas('room_reservations', [
            'id' => $reservation->id,
            'status' => RoomReservation::STATUS_CONFIRMED,
            'cancelled_at' => null,
            'cancelled_by' => null,
        ]);
    }

    public function test_student_can_cancel_own_reservation(): void
    {
        $student = User::factory()
            ->student()
            ->create();

        $reservation = RoomReservation::factory()
            ->for($student, 'user')
            ->create([
                'starts_at' => now()->addDay()->setTime(10, 0),
                'ends_at' => now()->addDay()->setTime(11, 0),
            ]);

        $response = $this
            ->actingAs($student)
            ->from(route('room-reservations.index'))
            ->patch(
                route(
                    'room-reservations.cancel',
                    $reservation
                )
            );

        $response
            ->assertRedirect(
                route('room-reservations.index')
            )
            ->assertSessionHas(
                'success',
                'Reservation cancelled successfully.'
            );

        $this->assertDatabaseHas('room_reservations', [
            'id' => $reservation->id,
            'status' => RoomReservation::STATUS_CANCELLED,
            'cancelled_by' => $student->id,
        ]);

        $this->assertNotNull(
            $reservation->refresh()->cancelled_at
        );
    }

    public function test_librarian_can_cancel_students_reservation(): void
    {
        $librarian = User::factory()
            ->librarian()
            ->create();

        $student = User::factory()
            ->student()
            ->create();

        $reservation = RoomReservation::factory()
            ->for($student, 'user')
            ->create([
                'starts_at' => now()->addDay()->setTime(10, 0),
                'ends_at' => now()->addDay()->setTime(11, 0),
            ]);

        $response = $this
            ->actingAs($librarian)
            ->from(route('room-reservations.index'))
            ->patch(
                route(
                    'room-reservations.cancel',
                    $reservation
                )
            );

        $response
            ->assertRedirect(
                route('room-reservations.index')
            )
            ->assertSessionHas(
                'success',
                'Reservation cancelled successfully.'
            );

        $this->assertDatabaseHas('room_reservations', [
            'id' => $reservation->id,
            'status' => RoomReservation::STATUS_CANCELLED,
            'cancelled_by' => $librarian->id,
        ]);
    }
}
