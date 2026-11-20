<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Modules\Identity\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TokenExpiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_token_expires_in_seven_days(): void
    {
        $user = User::factory()->create([
            'email' => 'karyawan@hadirmetrik.local',
            'password' => 'rahasia123',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'karyawan@hadirmetrik.local',
            'password' => 'rahasia123',
            'device_name' => 'Feature Test',
        ])->assertOk();

        $expiresAt = Carbon::parse($response->json('data.expires_at'));

        $this->assertTrue(
            $expiresAt->between(now()->addDays(7)->subMinute(), now()->addDays(7)->addMinute()),
            "Expected 7-day expiry, got {$expiresAt->toIso8601String()}.",
        );

        $this->assertNotNull($user->tokens()->first()->expires_at);
    }

    public function test_token_older_than_seven_days_is_rejected(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('old-device');
        $token->accessToken->forceFill(['created_at' => now()->subDays(8)])->save();

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/me')
            ->assertStatus(401);
    }
}
