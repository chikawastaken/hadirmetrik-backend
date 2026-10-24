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

    public function test_login_requires_email_and_password(): void
    {
        $this->postJson('/api/v1/login', [])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['email', 'password']]);
    }
}
