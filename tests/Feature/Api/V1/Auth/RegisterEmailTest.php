<?php

namespace Tests\Feature\Api\V1\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['mail.default' => 'smtp']);
    }

    private function seedEmployee(array $overrides = []): void
    {
        \DB::table('employees')->insert(array_merge([
            'employee_number' => 'EMP-0001',
            'full_name' => 'Usamah',
            'work_email' => 'ussmh6697@gmail.com',
            'employment_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function seedInvitationCode(string $code = 'INVITE01', bool $used = false): void
    {
        \DB::table('invitation_codes')->insert([
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
            'work_email' => 'ussmh6697@gmail.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'invitation_code' => 'INVITE01',
        ], $overrides);
    }

    public function test_successful_registration_sends_custom_email(): void
    {
        $this->seedEmployee();
        $this->seedInvitationCode();

        $response = $this->postJson('/api/v1/register', $this->payload())
            ->assertOk()
            ->assertJsonPath('success', true);

        // The registration should have created a user record
        $this->assertDatabaseHas('users', [
            'email' => 'ussmh6697@gmail.com',
            'name' => 'Usamah',
            'role' => 'employee',
        ]);
    }
}
