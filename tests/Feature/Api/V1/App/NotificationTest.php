<?php

namespace Tests\Feature\Api\V1\App;

use App\Modules\Identity\Infrastructure\Models\User;
use App\Modules\Notification\Infrastructure\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function employeeUser(): User
    {
        $user = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        Sanctum::actingAs($user);

        return $user;
    }

    public function test_employee_sees_own_notifications_sorted_by_created_at(): void
    {
        $user = $this->employeeUser();

        // Create notifications with slight delay via timestamp
        Notification::create([
            'user_id' => $user->id,
            'category' => 'absensi',
            'title' => 'Terlambat',
        ]);

        Notification::create([
            'user_id' => $user->id,
            'category' => 'tugas',
            'title' => 'Tugas baru',
        ]);

        $other = User::factory()->create(['role' => 'employee']);
        Notification::create([
            'user_id' => $other->id,
            'category' => 'info',
            'title' => 'Pengumuman untuk orang lain',
        ]);

        $response = $this->getJson('/api/v1/notifications');

        $response->assertOk()
            ->assertJsonCount(2, 'data.notifications')
            ->assertJsonPath('data.notifications.0.title', 'Terlambat')
            ->assertJsonPath('data.notifications.1.title', 'Tugas baru');
    }

    public function test_mark_one_notification_as_read(): void
    {
        $user = $this->employeeUser();

        $n = Notification::create([
            'user_id' => $user->id,
            'category' => 'tugas',
            'title' => 'Tugas baru',
            'read_at' => null,
        ]);

        $this->postJson("/api/v1/notifications/{$n->id}/read")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.notification.read_at', fn ($value) => $value !== null);

        $this->assertNotNull($n->fresh()->read_at);
    }

    public function test_mark_all_notifications_as_read(): void
    {
        $user = $this->employeeUser();

        Notification::create([
            'user_id' => $user->id,
            'category' => 'tugas',
            'title' => 'Tugas 1',
            'read_at' => null,
        ]);
        Notification::create([
            'user_id' => $user->id,
            'category' => 'absensi',
            'title' => 'Absen 1',
            'read_at' => null,
        ]);

        $this->postJson('/api/v1/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.updated', 2);

        // Verify both notifications are read
        $userId = $user->id;
        $count = Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();

        $this->assertEquals(0, $count);
    }

    public function test_unread_count_in_response(): void
    {
        $user = $this->employeeUser();

        Notification::create([
            'user_id' => $user->id,
            'category' => 'tugas',
            'title' => 'Tugas',
            'read_at' => null,
        ]);
        Notification::create([
            'user_id' => $user->id,
            'category' => 'absensi',
            'title' => 'Absen',
            'read_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/notifications');

        $response->assertOk()
            ->assertJsonPath('data.unread_count', 1);
    }

    public function test_employee_cannot_read_another_users_notification(): void
    {
        $user = $this->employeeUser();

        $other = User::factory()->create(['role' => 'employee']);
        $n = Notification::create([
            'user_id' => $other->id,
            'category' => 'tugas',
            'title' => 'Tugas orang lain',
        ]);

        $this->getJson("/api/v1/notifications/{$n->id}")
            ->assertNotFound();

        $this->postJson("/api/v1/notifications/{$n->id}/read")
            ->assertNotFound();
    }
}
