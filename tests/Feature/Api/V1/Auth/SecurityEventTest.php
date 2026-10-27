<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Modules\Identity\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_successful_login_is_recorded(): void
    {
        $user = User::factory()->create([
            'email' => 'karyawan@hadirmetrik.local',
            'password' => 'rahasia123',
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'karyawan@hadirmetrik.local',
            'password' => 'rahasia123',
        ])->assertOk();

        $this->assertDatabaseHas('security_events', [
            'event' => 'login.succeeded',
            'actor_user_id' => $user->id,
        ]);
    }

    public function test_a_failed_login_is_recorded_without_an_actor(): void
    {
        User::factory()->create([
            'email' => 'karyawan@hadirmetrik.local',
            'password' => 'rahasia123',
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'karyawan@hadirmetrik.local',
            'password' => 'salah-sekali',
        ])->assertStatus(401);

        $this->assertDatabaseHas('security_events', [
            'event' => 'login.failed',
            'actor_user_id' => null,
        ]);
    }
}
