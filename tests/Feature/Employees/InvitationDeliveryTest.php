<?php

namespace Tests\Feature\Employees;

use App\Mail\InvitationMail;
use App\Modules\Employees\Contracts\InvitationTokenGenerator;
use App\Modules\Employees\Infrastructure\Jobs\SendInvitationDelivery;
use App\Modules\Employees\Infrastructure\Models\EmployeeInvitation;
use App\Modules\Employees\Infrastructure\Models\InvitationDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\Support\CreatesEmployeeData;
use Tests\TestCase;

class InvitationDeliveryTest extends TestCase
{
    use CreatesEmployeeData;
    use RefreshDatabase;

    public function test_email_delivery_sends_the_invitation_and_marks_records_as_sent(): void
    {
        Mail::fake();

        [$invitation, $delivery, $plainToken] = $this->makeInvitation(channel: 'email');

        (new SendInvitationDelivery($delivery->id, $plainToken))->handle();

        Mail::assertSent(InvitationMail::class, function (InvitationMail $mail) use ($plainToken): bool {
            return $mail->hasTo('putri.r@sinarmetrik.co.id')
                && str_contains($mail->invitationUrl, $plainToken);
        });

        $delivery->refresh();
        $this->assertSame('sent', $delivery->status);
        $this->assertNotNull($delivery->sent_at);
        $this->assertSame(config('mail.default'), $delivery->provider);

        $invitation->refresh();
        $this->assertSame('sent', $invitation->status);
        $this->assertNotNull($invitation->sent_at);
        $this->assertNotNull($invitation->last_sent_at);
    }

    public function test_whatsapp_delivery_is_marked_failed_when_no_gateway_is_configured(): void
    {
        Mail::fake();

        [$invitation, $delivery, $plainToken] = $this->makeInvitation(channel: 'whatsapp');

        (new SendInvitationDelivery($delivery->id, $plainToken))->handle();

        Mail::assertNothingSent();

        $delivery->refresh();
        $this->assertSame('failed', $delivery->status);
        $this->assertSame('CHANNEL_NOT_CONFIGURED', $delivery->error_code);
        $this->assertNotNull($delivery->failed_at);
        $this->assertSame('pending', $invitation->fresh()->status);
    }

    public function test_delivery_for_a_revoked_invitation_is_not_sent(): void
    {
        Mail::fake();

        [$invitation, $delivery, $plainToken] = $this->makeInvitation(
            channel: 'email',
            invitationAttributes: ['status' => 'revoked', 'revoked_at' => now()],
        );

        (new SendInvitationDelivery($delivery->id, $plainToken))->handle();

        Mail::assertNothingSent();

        $delivery->refresh();
        $this->assertSame('failed', $delivery->status);
        $this->assertSame('INVITATION_INACTIVE', $delivery->error_code);
        $this->assertSame('revoked', $invitation->fresh()->status);
    }

    public function test_mail_transport_failure_marks_the_delivery_as_failed_without_leaking_recipient(): void
    {
        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new RuntimeException('smtp rejected putri.r@sinarmetrik.co.id'));

        [$invitation, $delivery, $plainToken] = $this->makeInvitation(channel: 'email');

        (new SendInvitationDelivery($delivery->id, $plainToken))->handle();

        $delivery->refresh();
        $this->assertSame('failed', $delivery->status);
        $this->assertSame('DELIVERY_FAILED', $delivery->error_code);
        $this->assertStringNotContainsString('putri.r@sinarmetrik.co.id', (string) $delivery->error_message);
        $this->assertSame('pending', $invitation->fresh()->status);
    }

    /**
     * @param  array<string, mixed>  $invitationAttributes
     * @return array{0: EmployeeInvitation, 1: InvitationDelivery, 2: string}
     */
    private function makeInvitation(string $channel, array $invitationAttributes = []): array
    {
        $hr = $this->makeHrUser();
        $employee = $this->makeEmployeeRecord([
            'employee_number' => 'HM-0251',
            'full_name' => 'Putri Ramadhani',
            'work_email' => 'putri.r@sinarmetrik.co.id',
            'phone_e164' => '+6281227453318',
        ]);

        $tokenPair = app(InvitationTokenGenerator::class)->generate();

        $invitation = EmployeeInvitation::create(array_merge([
            'employee_id' => $employee->id,
            'purpose' => 'face_enrollment',
            'token_hash' => $tokenPair['token_hash'],
            'status' => 'pending',
            'requested_channels' => [$channel],
            'expires_at' => now()->addDays(3),
            'created_by' => $hr->id,
        ], $invitationAttributes));

        $delivery = InvitationDelivery::create([
            'invitation_id' => $invitation->id,
            'channel' => $channel,
            'recipient' => $channel === 'email' ? 'putri.r@sinarmetrik.co.id' : '+6281227453318',
            'status' => 'queued',
            'attempt' => 1,
        ]);

        return [$invitation, $delivery, $tokenPair['plain_token']];
    }
}
