<?php

namespace App\Modules\Employees\Application\CreateEmployee;

use App\Modules\Audit\Contracts\AuditLogger;
use App\Modules\Employees\Contracts\Data\CreateEmployeeInput;
use App\Modules\Employees\Contracts\Data\CreateEmployeeResult;
use App\Modules\Employees\Contracts\EmployeeCreator;
use App\Modules\Employees\Contracts\EmployeeNumberGenerator;
use App\Modules\Employees\Contracts\InvitationTokenGenerator;
use App\Modules\Employees\Contracts\TemporaryPasswordGenerator;
use App\Modules\Employees\Contracts\UserAccountProvisioner;
use App\Modules\Employees\Infrastructure\Models\Division;
use App\Modules\Employees\Infrastructure\Models\Employee;
use App\Modules\Employees\Infrastructure\Models\EmployeeCompensation;
use App\Modules\Employees\Infrastructure\Models\EmployeeInvitation;
use App\Modules\Employees\Infrastructure\Models\EmployeeShiftAssignment;
use App\Modules\Employees\Infrastructure\Models\EmployeeWorkLocationAssignment;
use App\Modules\Employees\Infrastructure\Models\InvitationDelivery;
use App\Modules\Employees\Infrastructure\Models\Shift;
use App\Modules\Employees\Infrastructure\Models\WorkLocation;
use App\Shared\Contracts\TransactionManager;
use App\Shared\Exceptions\ApiException;

final class CreateEmployeeUseCase implements EmployeeCreator
{
    public function __construct(
        private readonly UserAccountProvisioner $userProvisioner,
        private readonly EmployeeNumberGenerator $numberGenerator,
        private readonly TemporaryPasswordGenerator $passwordGenerator,
        private readonly InvitationTokenGenerator $tokenGenerator,
        private readonly AuditLogger $auditLogger,
        private readonly TransactionManager $transaction,
    ) {}

    public function create(CreateEmployeeInput $input, ?int $actorUserId = null): CreateEmployeeResult
    {
        // 1. Validation checks
        $division = Division::query()->find($input->divisionId);
        if (! $division || ! $division->is_active) {
            throw new ApiException('DIVISION_NOT_FOUND', 'Selected division is not found or inactive.', 422);
        }

        $shift = Shift::query()->find($input->shiftId);
        if (! $shift || ! $shift->is_active) {
            throw new ApiException('SHIFT_NOT_FOUND', 'Selected shift is not found or inactive.', 422);
        }

        $location = WorkLocation::query()->find($input->workLocationId);
        if (! $location || ! $location->is_active) {
            throw new ApiException('LOCATION_NOT_FOUND', 'Selected work location is not found or inactive.', 422);
        }

        $manager = null;
        if ($input->managerId !== null) {
            $manager = Employee::query()->find($input->managerId);
            if (! $manager || $manager->employment_status === 'terminated') {
                throw new ApiException('MANAGER_NOT_FOUND', 'Selected manager is not found or inactive.', 422);
            }
        }

        // Normalize phone to E.164
        $phoneE164 = null;
        if ($input->phone !== null && trim($input->phone) !== '') {
            $cleanPhone = preg_replace('/[^\d+]/', '', trim($input->phone));
            if (str_starts_with($cleanPhone, '0')) {
                $phoneE164 = '+62'.substr($cleanPhone, 1);
            } elseif (str_starts_with($cleanPhone, '62')) {
                $phoneE164 = '+'.$cleanPhone;
            } elseif (str_starts_with($cleanPhone, '+')) {
                $phoneE164 = $cleanPhone;
            } else {
                $phoneE164 = '+'.$cleanPhone;
            }
        }

        if ($input->sendInvitation && in_array('whatsapp', $input->invitationChannels, true) && empty($phoneE164)) {
            throw new ApiException('PHONE_REQUIRED', 'Phone number is required when WhatsApp channel is selected.', 422);
        }

        $initialPassword = $this->passwordGenerator->generate(16);
        $employeeNumber = $this->numberGenerator->next();

        return $this->transaction->run(function () use (
            $input,
            $division,
            $shift,
            $location,
            $manager,
            $phoneE164,
            $initialPassword,
            $employeeNumber,
            $actorUserId
        ) {
            // Provision user account
            $account = $this->userProvisioner->provision(
                name: $input->fullName,
                email: strtolower(trim($input->email)),
                plainPassword: $initialPassword,
                role: 'employee',
                status: 'active',
                mustChangePassword: true,
            );

            // Create employee
            $employee = Employee::query()->create([
                'user_id' => $account->id,
                'employee_number' => $employeeNumber,
                'full_name' => $input->fullName,
                'work_email' => strtolower(trim($input->email)),
                'phone_e164' => $phoneE164,
                'division_id' => $division->id,
                'job_title' => $input->jobTitle,
                'manager_id' => $manager?->id,
                'employment_start' => $input->employmentStart,
                'employment_status' => $input->employmentStatus,
            ]);

            // Create compensations
            $totalMonthly = $input->baseSalary + $input->transportAllowance + $input->mealAllowance;
            $compensation = EmployeeCompensation::query()->create([
                'employee_id' => $employee->id,
                'currency' => 'IDR',
                'base_salary' => $input->baseSalary,
                'transport_allowance' => $input->transportAllowance,
                'meal_allowance' => $input->mealAllowance,
                'effective_from' => $input->employmentStart,
                'created_by' => $actorUserId,
            ]);

            // Create shift assignment
            $shiftAssignment = EmployeeShiftAssignment::query()->create([
                'employee_id' => $employee->id,
                'shift_id' => $shift->id,
                'effective_from' => $input->employmentStart,
                'created_by' => $actorUserId,
            ]);

            // Create location assignment
            $locationAssignment = EmployeeWorkLocationAssignment::query()->create([
                'employee_id' => $employee->id,
                'work_location_id' => $location->id,
                'effective_from' => $input->employmentStart,
                'created_by' => $actorUserId,
            ]);

            // Handle invitation
            $invitationData = null;
            if ($input->sendInvitation) {
                $tokenPair = $this->tokenGenerator->generate();
                $expiresAt = now()->addDays($input->invitationExpiresInDays);

                $invitation = EmployeeInvitation::query()->create([
                    'employee_id' => $employee->id,
                    'purpose' => 'face_enrollment',
                    'token_hash' => $tokenPair['token_hash'],
                    'status' => 'pending',
                    'requested_channels' => $input->invitationChannels,
                    'expires_at' => $expiresAt,
                    'attempts' => 0,
                    'created_by' => $actorUserId ?? $account->id,
                ]);

                $deliveries = [];
                foreach ($input->invitationChannels as $channel) {
                    $recipient = $channel === 'whatsapp' ? $phoneE164 : strtolower(trim($input->email));
                    $delivery = InvitationDelivery::query()->create([
                        'invitation_id' => $invitation->id,
                        'channel' => $channel,
                        'recipient' => (string) $recipient,
                        'status' => 'queued',
                        'attempt' => 1,
                    ]);
                    $deliveries[] = [
                        'channel' => $delivery->channel,
                        'recipient' => $delivery->recipient,
                        'status' => $delivery->status,
                    ];
                }

                $invitationData = [
                    'id' => $invitation->id,
                    'status' => $invitation->status,
                    'requested_channels' => $invitation->requested_channels,
                    'expires_at' => $invitation->expires_at->toIso8601String(),
                    'deliveries' => $deliveries,
                ];

                $this->auditLogger->log(
                    entityType: 'invitation',
                    entityId: (string) $invitation->id,
                    action: 'invitation.created',
                    afterSnapshot: [
                        'employee_id' => $employee->id,
                        'purpose' => $invitation->purpose,
                        'status' => $invitation->status,
                        'channels' => $input->invitationChannels,
                    ],
                    actorUserId: $actorUserId,
                );
            }

            // Audit logs
            $this->auditLogger->log(
                entityType: 'employee',
                entityId: (string) $employee->id,
                action: 'employee.created',
                afterSnapshot: [
                    'employee_number' => $employee->employee_number,
                    'full_name' => $employee->full_name,
                    'work_email' => $employee->work_email,
                    'division_id' => $employee->division_id,
                ],
                actorUserId: $actorUserId,
            );

            $this->auditLogger->log(
                entityType: 'compensation',
                entityId: (string) $compensation->id,
                action: 'compensation.created',
                afterSnapshot: [
                    'employee_id' => $employee->id,
                    'base_salary' => $compensation->base_salary,
                ],
                actorUserId: $actorUserId,
            );

            $this->auditLogger->log(
                entityType: 'assignment',
                entityId: (string) $shiftAssignment->id,
                action: 'assignment.created',
                afterSnapshot: [
                    'type' => 'shift',
                    'employee_id' => $employee->id,
                    'shift_id' => $shift->id,
                ],
                actorUserId: $actorUserId,
            );

            return new CreateEmployeeResult(
                employeeData: [
                    'id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'full_name' => $employee->full_name,
                    'work_email' => $employee->work_email,
                    'phone_e164' => $employee->phone_e164,
                    'division' => ['id' => $division->id, 'name' => $division->name],
                    'job_title' => $employee->job_title,
                    'manager' => $manager ? [
                        'id' => $manager->id,
                        'full_name' => $manager->full_name,
                        'employee_number' => $manager->employee_number,
                    ] : null,
                    'employment_start' => $employee->employment_start->format('Y-m-d'),
                    'employment_status' => $employee->employment_status,
                ],
                compensationData: [
                    'base_salary' => number_format((float) $compensation->base_salary, 2, '.', ''),
                    'transport_allowance' => number_format((float) $compensation->transport_allowance, 2, '.', ''),
                    'meal_allowance' => number_format((float) $compensation->meal_allowance, 2, '.', ''),
                    'total_monthly' => number_format((float) $totalMonthly, 2, '.', ''),
                    'currency' => 'IDR',
                ],
                shiftAssignmentData: [
                    'shift_id' => $shift->id,
                    'name' => $shift->name,
                    'is_overnight' => $shift->is_overnight,
                    'effective_from' => $shiftAssignment->effective_from->format('Y-m-d'),
                ],
                workLocationAssignmentData: [
                    'work_location_id' => $location->id,
                    'name' => $location->name,
                    'effective_from' => $locationAssignment->effective_from->format('Y-m-d'),
                ],
                invitationData: $invitationData,
                initialPassword: $initialPassword,
                mustChangePassword: true,
            );
        });
    }
}
