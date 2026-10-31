<?php

namespace App\Modules\Employees\Infrastructure\Jobs;

use App\Mail\InvitationMail;
use App\Modules\Employees\Infrastructure\Models\EmployeeInvitation;
use App\Modules\Employees\Infrastructure\Models\InvitationDelivery;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class SendInvitationDelivery implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $deliveryId,
        public string $plainToken,
    ) {}

    public function handle(): void
    {
        $delivery = InvitationDelivery::query()
            ->with(['invitation.employee'])
            ->find($this->deliveryId);

        if ($delivery === null || $delivery->status !== 'queued') {
            return;
        }

        $invitation = $delivery->invitation;

        if (
            $invitation === null
            || $invitation->employee === null
            || ! in_array($invitation->status, ['pending', 'sent'], true)
            || $invitation->expires_at->isPast()
        ) {
            $this->markFailed($delivery, 'INVITATION_INACTIVE', 'Undangan tidak aktif.');

            return;
        }

        if ($delivery->channel !== 'email') {
            $this->markFailed($delivery, 'CHANNEL_NOT_CONFIGURED', 'Kanal pengiriman belum dikonfigurasi.');

            return;
        }

        try {
            Mail::to($delivery->recipient)->send($this->mailable($invitation));
        } catch (Throwable) {
            // Transport errors may contain the recipient address; never persist
            // the raw exception message (docs/architecture.md §5.7, §12.6).
            $this->markFailed($delivery, 'DELIVERY_FAILED', 'Pengiriman email gagal.');

            return;
        }

        $sentAt = now();

        $delivery->update([
            'status' => 'sent',
            'provider' => (string) config('mail.default'),
            'sent_at' => $sentAt,
        ]);

        $invitation->update([
            'status' => $invitation->status === 'pending' ? 'sent' : $invitation->status,
            'sent_at' => $invitation->sent_at ?? $sentAt,
            'last_sent_at' => $sentAt,
        ]);
    }

    private function mailable(EmployeeInvitation $invitation): InvitationMail
    {
        return new InvitationMail(
            employeeName: $invitation->employee->full_name,
            employeeNumber: $invitation->employee->employee_number,
            invitationUrl: rtrim((string) config('invitations.url'), '/').'/'.$this->plainToken,
            expiresAt: $invitation->expires_at,
        );
    }

    private function markFailed(InvitationDelivery $delivery, string $code, string $message): void
    {
        $delivery->update([
            'status' => 'failed',
            'error_code' => $code,
            'error_message' => $message,
            'failed_at' => now(),
        ]);
    }
}
