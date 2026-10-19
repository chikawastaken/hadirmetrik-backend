<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Modules\Identity\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/me')
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_me_rejects_an_invalid_token(): void
    {
        $this->withHeader('Authorization', 'Bearer not-a-real-token')
            ->getJson('/api/v1/me')
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_me_returns_the_authenticated_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Karyawan Contoh',
            'email' => 'karyawan@hadirmetrik.local',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.name', 'Karyawan Contoh')
            ->assertJsonPath('data.email', 'karyawan@hadirmetrik.local');
    }
}
