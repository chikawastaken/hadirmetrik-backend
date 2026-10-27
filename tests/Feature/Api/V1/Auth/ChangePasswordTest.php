<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Modules\Identity\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'karyawan@hadirmetrik.local',
            'password' => 'sementara123',
            'must_change_password' => true,
        ]);
    }

    public function test_user_with_temporary_password_can_change_it(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/auth/change-password', [
                'current_password' => 'sementara123',
                'new_password' => 'rahasiaBaru123',
                'new_password_confirmation' => 'rahasiaBaru123',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.must_change_password', false);

        $this->user->refresh();

        $this->assertFalse($this->user->must_change_password);
        $this->assertTrue(Hash::check('rahasiaBaru123', $this->user->password));

        $this->assertDatabaseHas('security_events', [
            'event' => 'password.changed',
            'actor_user_id' => $this->user->id,
        ]);
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/auth/change-password', [
                'current_password' => 'salah-sekali',
                'new_password' => 'rahasiaBaru123',
                'new_password_confirmation' => 'rahasiaBaru123',
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.current_password.0', 'Kata sandi saat ini tidak sesuai.');

        $this->user->refresh();

        $this->assertTrue($this->user->must_change_password);
        $this->assertTrue(Hash::check('sementara123', $this->user->password));

        $this->assertDatabaseHas('security_events', [
            'event' => 'password.change_failed',
            'actor_user_id' => $this->user->id,
        ]);
    }

    public function test_password_confirmation_must_match(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/auth/change-password', [
                'current_password' => 'sementara123',
                'new_password' => 'rahasiaBaru123',
                'new_password_confirmation' => 'beda-sendiri-123',
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['new_password']]);

        $this->assertTrue(Hash::check('sementara123', $this->user->fresh()->password));
    }

    public function test_new_password_must_differ_from_the_current_password(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/auth/change-password', [
                'current_password' => 'sementara123',
                'new_password' => 'sementara123',
                'new_password_confirmation' => 'sementara123',
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['new_password']]);

        $this->assertTrue(Hash::check('sementara123', $this->user->fresh()->password));
    }

    public function test_change_password_requires_authentication(): void
    {
        $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'sementara123',
            'new_password' => 'rahasiaBaru123',
            'new_password_confirmation' => 'rahasiaBaru123',
        ])
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }
}
