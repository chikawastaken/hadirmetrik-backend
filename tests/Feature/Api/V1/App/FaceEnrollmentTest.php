<?php

namespace Tests\Feature\Api\V1\App;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesEmployeeData;
use Tests\TestCase;

class FaceEnrollmentTest extends TestCase
{
    use CreatesEmployeeData;
    use RefreshDatabase;

    public function test_submit_enrollment_creates_pending_request_and_template(): void
    {
        $user = $this->makeEmployeeUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/face/enroll', [
                'provider' => 'local_tflite',
                'template_ref' => 'sha256:abc123',
                'sample_count' => 3,
                'metadata' => ['device' => 'pixel-7'],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.enrollment.status', 'pending')
            ->assertJsonPath('data.enrollment.sample_count', 3)
            ->assertJsonPath('data.template.version', 1)
            ->assertJsonPath('data.template.status', 'pending');

        $this->assertDatabaseHas('face_enrollment_requests', [
            'user_id' => $user->id,
            'status' => 'pending',
            'sample_count' => 3,
        ]);
    }

    public function test_re_enrollment_gets_next_template_version(): void
    {
        $user = $this->makeEmployeeUser();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/face/enroll', ['sample_count' => 1])
            ->assertStatus(201);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/face/enroll', ['sample_count' => 2])
            ->assertStatus(201)
            ->assertJsonPath('data.template.version', 2);
    }

    public function test_sample_count_is_required(): void
    {
        $user = $this->makeEmployeeUser();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/face/enroll', [])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_status_list_revoke_and_delete_template(): void
    {
        $user = $this->makeEmployeeUser();

        $created = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/face/enroll', ['sample_count' => 1])
            ->assertStatus(201);

        $enrollmentId = $created->json('data.enrollment.id');
        $templateId = $created->json('data.template.id');

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/face/enrollments/{$enrollmentId}")
            ->assertOk()
            ->assertJsonPath('data.status', 'pending');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/face/templates')
            ->assertOk()
            ->assertJsonPath('data.0.id', $templateId);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/face/templates/{$templateId}/revoke")
            ->assertOk()
            ->assertJsonPath('data.status', 'revoked');

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/face/templates/{$templateId}")
            ->assertOk()
            ->assertJsonPath('data.status', 'deleted');
    }

    public function test_user_cannot_touch_another_users_template(): void
    {
        $owner = $this->makeEmployeeUser();
        $other = $this->makeEmployeeUser();

        $created = $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/face/enroll', ['sample_count' => 1])
            ->assertStatus(201);

        $templateId = $created->json('data.template.id');

        $this->actingAs($other, 'sanctum')
            ->postJson("/api/v1/face/templates/{$templateId}/revoke")
            ->assertStatus(404);
    }

    public function test_face_routes_require_auth(): void
    {
        $this->postJson('/api/v1/face/enroll', ['sample_count' => 1])->assertStatus(401);
    }
}
