<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Modules\Identity\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private function seedEmployee(array $overrides = []): void
    {
        DB::table('employees')->insert(array_merge([
            'employee_number' => 'EMP-0001',
            'full_name' => 'Karyawan Uji',
            'work_email' => 'uji@hadirmetrik.local',
            'employment_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function seedInvitationCode(string $code = 'INVITE01', bool $used = false): void
    {
        DB::table('invitation_codes')->insert([
            'code' => $code,
            'is_used' => $used,
            'used_at' => $used ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'employee_number' => 'EMP-0001',
            'work_email' => 'uji@hadirmetrik.local',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'invitation_code' => 'INVITE01',
        ], $overrides);
    }

    public function test_employee_registers_with_valid_invitation_code(): void
    {
        $this->seedEmployee();
        $this->seedInvitationCode();

        $this->postJson('/api/v1/register', $this->payload())
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'token_type', 'user' => ['id', 'name', 'email']]]);

        $this->assertDatabaseHas('users', [
            'email' => 'uji@hadirmetrik.local',
            'name' => 'Karyawan Uji',
            'role' => 'employee',
        ]);
        $this->assertDatabaseHas('invitation_codes', ['code' => 'INVITE01', 'is_used' => true]);
    }

    public function test_registration_rejects_unknown_work_email(): void
    {
        $this->seedInvitationCode();

        $this->postJson('/api/v1/register', $this->payload())
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['work_email']]);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_employee_number_that_does_not_match_the_employee_record(): void
    {
        $this->seedEmployee();
        $this->seedInvitationCode();

        $this->postJson('/api/v1/register', $this->payload(['employee_number' => 'EMP-9999']))
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['employee_number']]);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_used_invitation_code(): void
    {
        $this->seedEmployee();
        $this->seedInvitationCode('INVITE01', used: true);

        $this->postJson('/api/v1/register', $this->payload())
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_existing_account(): void
    {
        $this->seedEmployee();
        $this->seedInvitationCode();
        User::factory()->create(['email' => 'uji@hadirmetrik.local']);

        $this->postJson('/api/v1/register', $this->payload())
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_registration_ignores_privileged_fields_from_payload(): void
    {
        $this->seedEmployee();
        $this->seedInvitationCode();

        $this->postJson('/api/v1/register', $this->payload([
            'role' => 'admin',
            'is_admin' => true,
        ]))->assertOk();

        $this->assertDatabaseHas('users', [
            'email' => 'uji@hadirmetrik.local',
            'role' => 'employee',
        ]);
    }
}
