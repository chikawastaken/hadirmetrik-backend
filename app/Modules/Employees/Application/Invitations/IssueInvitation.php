<?php

namespace App\Modules\Employees\Application\Invitations;

use App\Modules\Audit\Contracts\AuditLogger;
use App\Modules\Employees\Application\Exceptions\InvitationPhoneRequired;
use App\Modules\Employees\Contracts\InvitationDeliveryDispatcher;
use App\Modules\Employees\Contracts\InvitationTokenGenerator;
use App\Modules\Employees\Infrastructure\Models\Employee;
use App\Modules\Employees\Infrastructure\Models\EmployeeInvitation;
use App\Modules\Employees\Infrastructure\Models\InvitationDelivery;

/**
 * Creates an invitation, its per-channel delivery rows, audit record, and
 * queued delivery jobs. Callers own the surrounding transaction; jobs are
 * dispatched after that transaction commits.
 */
final class IssueInvitation
{
    public function __construct(
        private readonly InvitationTokenGenerator $tokens,
        private readonly InvitationDeliveryDispatcher $dispatcher,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  list<string>  $channels
     * @param  array<string, mixed>  $auditContext
     * @return array<string, mixed>
     */
    public function execute(
        Employee $employee,
        array $channels,
        int $expiresInDays,
        ?int $actorUserId,
        string $auditAction = 'invitation.created',
        array $auditContext = [],
    ): array {
        $tokenPair = $this->tokens->generate();
        $expiresAt = now()->addDays($expiresInDays);

        $invitation = EmployeeInvitation::query()->create([
            'employee_id' => $employee->id,
            'purpose' => 'face_enrollment',
            'token_hash' => $tokenPair['token_hash'],
            'status' => 'pending',
            'requested_channels' => $channels,
            'expires_at' => $expiresAt,
            'attempts' => 0,
            'created_by' => $actorUserId ?? $employee->user_id,
        ]);

        $deliveries = [];

        foreach ($channels as $channel) {
            $recipient = $channel === 'whatsapp'
                ? $employee->phone_e164
                : $employee->work_email;

            if ($recipient === null || $recipient === '') {
                throw new InvitationPhoneRequired;
            }

            $delivery = InvitationDelivery::query()->create([
                'invitation_id' => $invitation->id,
                'channel' => $channel,
                'recipient' => $recipient,
                'status' => 'queued',
                'attempt' => 1,
            ]);

            $this->dispatcher->dispatch($delivery->id, $tokenPair['plain_token']);

            $deliveries[] = [
                'channel' => $delivery->channel,
                'recipient' => $delivery->recipient,
                'status' => $delivery->status,
            ];
        }

        $this->auditLogger->log(
            entityType: 'invitation',
            entityId: (string) $invitation->id,
            action: $auditAction,
            afterSnapshot: array_merge([
                'employee_id' => $employee->id,
                'purpose' => $invitation->purpose,
                'status' => $invitation->status,
                'channels' => $channels,
            ], $auditContext),
            actorUserId: $actorUserId,
        );

        return [
            'id' => $invitation->id,
            'status' => $invitation->status,
            'requested_channels' => $invitation->requested_channels,
            'expires_at' => $invitation->expires_at->toIso8601String(),
            'deliveries' => $deliveries,
        ];
    }
}
