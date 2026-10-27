<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Modules\Employees\Infrastructure\Models\Division;
use App\Modules\Employees\Infrastructure\Models\Employee;
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
            ->assertJsonPath('data.email', 'karyawan@hadirmetrik.local')
            ->assertJsonPath('data.must_change_password', false)
            ->assertJsonPath('data.employee_number', null)
            ->assertJsonPath('data.employee', null);
    }

    public function test_me_returns_employee_summary_and_face_status(): void
    {
        $user = User::factory()->create([
            'name' => 'Putri Ramadhani',
            'email' => 'putri.r@sinarmetrik.co.id',
            'must_change_password' => true,
        ]);

        $division = Division::create([
            'name' => 'Operasional',
            'code' => 'OPS',
            'is_active' => true,
        ]);

        Employee::create([
            'user_id' => $user->id,
            'employee_number' => 'HM-0251',
            'full_name' => 'Putri Ramadhani',
            'work_email' => 'putri.r@sinarmetrik.co.id',
            'division_id' => $division->id,
            'job_title' => 'Staf Admin Gudang',
            'employment_status' => 'probation',
            'face_registered_at' => null,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.must_change_password', true)
            ->assertJsonPath('data.employee_number', 'HM-0251')
            ->assertJsonPath('data.employee.id', Employee::first()->id)
            ->assertJsonPath('data.employee.full_name', 'Putri Ramadhani')
            ->assertJsonPath('data.employee.work_email', 'putri.r@sinarmetrik.co.id')
            ->assertJsonPath('data.employee.job_title', 'Staf Admin Gudang')
            ->assertJsonPath('data.employee.division.id', $division->id)
            ->assertJsonPath('data.employee.division.name', 'Operasional')
            ->assertJsonPath('data.employee.employment_status', 'probation')
            ->assertJsonPath('data.employee.face_registered', false)
            ->assertJsonPath('data.employee.face_registered_at', null);
    }

    public function test_me_reports_face_registered_when_a_template_exists(): void
    {
        $user = User::factory()->create();

        Employee::create([
            'user_id' => $user->id,
            'employee_number' => 'HM-0252',
            'full_name' => 'Budi Santoso',
            'work_email' => $user->email,
            'employment_status' => 'active',
            'face_registered_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.employee.face_registered', true);

        $this->assertNotNull($response->json('data.employee.face_registered_at'));
    }
}
