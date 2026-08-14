<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\RoomReservation;

class RoomAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_librarian_can_open_create_room_page(): void
    {
        $librarian = User::factory()
            ->librarian()
            ->create();

        $response = $this
            ->actingAs($librarian)
            ->get(route('rooms.create'));

        $response
            ->assertOk()
            ->assertSee('Add Room');
    }

    public function test_student_cannot_open_create_room_page(): void
    {
        $student = User::factory()
            ->student()
            ->create();

        $response = $this
            ->actingAs($student)
            ->get(route('rooms.create'));

        $response->assertForbidden();
    }

    public function test_student_cannot_open_room_management_page(): void
    {
        $student = User::factory()
            ->student()
            ->create();

        $response = $this
            ->actingAs($student)
            ->get(route('rooms.index'));

        $response->assertForbidden();
    }

    public function test_student_can_open_room_availability_page(): void
    {
        $student = User::factory()
            ->student()
            ->create();

        $response = $this
            ->actingAs($student)
            ->get(route('room-availability.index'));

        $response
            ->assertOk()
            ->assertSee('Room Availability')
            ->assertDontSee('Enable Exam Period');
    }

    public function test_student_cannot_open_edit_room_page(): void
    {
        $student = User::factory()
            ->student()
            ->create();

        $room = Room::factory()->create();

        $response = $this
            ->actingAs($student)
            ->get(route('rooms.edit', $room));

        $response->assertForbidden();
    }

    public function test_student_cannot_update_room(): void
    {
        $student = User::factory()
            ->student()
            ->create();

        $room = Room::factory()->create([
            'capacity' => 6,
            'status' => 'available',
        ]);

        $response = $this
            ->actingAs($student)
            ->put(route('rooms.update', $room), [
                'room_number' => $room->room_number,
                'name' => $room->name,
                'type' => $room->type,
                'capacity' => 20,
                'location' => $room->location,
                'status' => 'unavailable',
                'description' => $room->description,
                'facilities' => ['whiteboard'],
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'capacity' => 6,
            'status' => 'available',
        ]);
    }

    public function test_student_cannot_delete_room(): void
    {
        $student = User::factory()
            ->student()
            ->create();

        $room = Room::factory()->create();

        $response = $this
            ->actingAs($student)
            ->delete(route('rooms.destroy', $room));

        $response->assertForbidden();

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
        ]);
    }

 public function test_librarian_can_update_room(): void
{
    $librarian = User::factory()
        ->librarian()
        ->create();

    $room = Room::factory()->create([
        'capacity' => 6,
        'status' => 'available',
    ]);

    $response = $this
        ->actingAs($librarian)
        ->put(route('rooms.update', $room), [
            'room_number' => $room->room_number,
            'name' => $room->name,
            'type' => $room->type,
            'capacity' => 12,
            'location' => $room->location,
            'status' => 'unavailable',
            'description' => $room->description,
            'facilities' => [
                'whiteboard',
                'projector',
            ],

            /*
             * 模拟管理员从 Room Management 的第 2 页进入 Edit Room。
             * page 不是 Room table 的资料，只是用来保存返回的位置。
             */
            'page' => 2,
        ]);

    /*
     * 更新后必须回到原本的 Room Management 第 2 页，
     * 而不是 Room Details 页面，也不是列表的第一页。
     */
    $response->assertRedirect(
        route('rooms.index', ['page' => 2])
    );

    $this->assertDatabaseHas('rooms', [
        'id' => $room->id,
        'capacity' => 12,
        'status' => 'unavailable',
    ]);
}

public function test_librarian_can_delete_room(): void
{
    $librarian = User::factory()
        ->librarian()
        ->create();

    $room = Room::factory()->create();

    $response = $this
        ->actingAs($librarian)
        /*
         * 模拟管理员在 Room Management 第 2 页删除没有 reservation 的房间。
         */
        ->delete(route('rooms.destroy', $room), [
            'page' => 2,
        ]);

    /*
     * 删除成功后，同样保留在原本的第 2 页。
     */
    $response->assertRedirect(
        route('rooms.index', ['page' => 2])
    );

    $this->assertDatabaseMissing('rooms', [
        'id' => $room->id,
    ]);
}
public function test_librarian_cannot_delete_a_room_that_has_reservations(): void
{
    // 建立有删除权限的 librarian。
    $librarian = User::factory()
        ->librarian()
        ->create();

    // 建立一间准备被删除的房间。
    $room = Room::factory()->create();

    // 建立一名学生，并为该学生建立该房间的预约。
    $student = User::factory()
        ->student()
        ->create();

    $reservation = RoomReservation::factory()->create([
        'room_id' => $room->id,
        'user_id' => $student->id,
    ]);

    /*
     * 模拟管理员从 Room Management 第 2 页尝试删除该房间。
     *
     * 系统规则：
     * 只要 Room 已经有 reservation record，
     * 无论预约是未来、已完成或已取消，都不允许删除 Room。
     * 这是为了保留预约历史，避免资料一起消失。
     */
    $response = $this
        ->actingAs($librarian)
        ->delete(route('rooms.destroy', $room), [
            'page' => 2,
        ]);

    /*
     * 删除被阻止后，系统应回到原本列表的第 2 页。
     */
    $response
        ->assertRedirect(
            route('rooms.index', ['page' => 2])
        )
        ->assertSessionHas('error');

    /*
     * 最重要的两项数据库验证：
     *
     * 1. Room 仍然存在；
     * 2. Reservation 也仍然存在。
     */
    $this->assertDatabaseHas('rooms', [
        'id' => $room->id,
    ]);

    $this->assertDatabaseHas('room_reservations', [
        'id' => $reservation->id,
        'room_id' => $room->id,
        'user_id' => $student->id,
    ]);
}
}