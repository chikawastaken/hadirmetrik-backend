<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Modules\Identity\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordChangeGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_temporary_password_blocks_protected_endpoints(): void
    {
        $hr = User::factory()->create([
            'role' => 'hr',
            'must_change_password' => true,
        ]);

        $this->actingAs($hr, 'sanctum')
            ->postJson('/api/v1/hr/employees', [
                'full_name' => 'Karyawan Baru',
                'email' => 'baru@hadirmetrik.local',
            ])
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.code.0', 'PASSWORD_CHANGE_REQUIRED');

        $this->assertDatabaseMissing('employees', [
            'work_email' => 'baru@hadirmetrik.local',
        ]);
    }

    public function test_guard_lifts_after_the_password_has_been_changed(): void
    {
        $hr = User::factory()->create([
            'role' => 'hr',
            'password' => 'sementara123',
            'must_change_password' => true,
        ]);

        $this->actingAs($hr, 'sanctum')
            ->postJson('/api/v1/auth/change-password', [
                'current_password' => 'sementara123',
                'new_password' => 'rahasiaBaru123',
                'new_password_confirmation' => 'rahasiaBaru123',
            ])
            ->assertOk();

        // The guard no longer intercepts; validation now rejects the empty payload.
        $this->actingAs($hr->fresh(), 'sanctum')
            ->postJson('/api/v1/hr/employees', [
                'full_name' => 'Karyawan Baru',
                'email' => 'baru@hadirmetrik.local',
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
