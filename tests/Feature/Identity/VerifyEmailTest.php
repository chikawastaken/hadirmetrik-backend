<?php

namespace Tests\Feature\Identity;

use App\Modules\Identity\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class VerifyEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_verification_link_marks_the_email_as_verified(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ],
        );

        $this->get($url)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Email berhasil diverifikasi.');

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_verification_link_with_an_invalid_signature_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();

        $this->get("/email/verify/{$user->id}/".sha1($user->email))
            ->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }
}
