<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Modules\Identity\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token_for_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'karyawan@hadirmetrik.local',
            'password' => 'rahasia123',
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'karyawan@hadirmetrik.local',
            'password' => 'rahasia123',
            'device_name' => 'Feature Test',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'name', 'email']]]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_login_rejects_wrong_password_without_revealing_the_account(): void
    {
        User::factory()->create([
            'email' => 'karyawan@hadirmetrik.local',
            'password' => 'rahasia123',
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'karyawan@hadirmetrik.local',
            'password' => 'salah-sekali',
        ])
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Kredensial tidak valid.');
    }

    public function test_login_rejects_unknown_email_the_same_way(): void
    {
        $this->postJson('/api/v1/login', [
            'email' => 'tidak-ada@hadirmetrik.local',
            'password' => 'rahasia123',
        ])
            ->assertStatus(401)
            ->assertJsonPath('message', 'Kredensial tidak valid.');
    }

    public function test_login_requires_identifier_and_password(): void
    {
        $this->postJson('/api/v1/login', [])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['identifier', 'password']]);
    }

    public function test_employee_can_login_with_employee_number(): void
    {
        $user = User::factory()->create([
            'email' => 'karyawan@hadirmetrik.local',
            'password' => 'rahasia123',
            'status' => 'active',
            'must_change_password' => true,
        ]);

        \App\Modules\Employees\Infrastructure\Models\Employee::create([
            'user_id' => $user->id,
            'employee_number' => 'HM-0001',
            'full_name' => 'Karyawan Contoh',
            'work_email' => 'karyawan@hadirmetrik.local',
            'employment_status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'identifier' => 'HM-0001',
            'password' => 'rahasia123',
            'device_name' => 'Pixel 7',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.must_change_password', true)
            ->assertJsonPath('data.user.email', 'karyawan@hadirmetrik.local');

        $this->assertNotNull($user->fresh()->last_login_at);

        $this->assertDatabaseHas('security_events', [
            'event' => 'login.succeeded',
            'actor_user_id' => $user->id,
        ]);
    }

    public function test_login_rejects_suspended_account(): void
    {
        User::factory()->create([
            'email' => 'suspended@hadirmetrik.local',
            'password' => 'rahasia123',
            'status' => 'suspended',
        ]);

        $this->postJson('/api/v1/login', [
            'identifier' => 'suspended@hadirmetrik.local',
            'password' => 'rahasia123',
        ])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Your account has been suspended. Please contact HR.');
    }
}
