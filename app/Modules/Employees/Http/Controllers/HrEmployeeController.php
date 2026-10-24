<?php

namespace App\Modules\Employees\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Employees\Contracts\Data\CreateEmployeeInput;
use App\Modules\Employees\Contracts\EmployeeCreator;
use App\Modules\Employees\Http\Requests\CreateEmployeeRequest;
use App\Shared\Http\ApiResponse;

final class HrEmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeCreator $creator,
    ) {}

    public function store(CreateEmployeeRequest $request): ApiResponse
    {
        $input = new CreateEmployeeInput(
            fullName: (string) $request->validated('full_name'),
            email: (string) $request->validated('email'),
            phone: $request->validated('phone'),
            divisionId: (int) $request->validated('division_id'),
            jobTitle: (string) $request->validated('job_title'),
            managerId: $request->validated('manager_id') ? (int) $request->validated('manager_id') : null,
            employmentStart: (string) $request->validated('employment_start'),
            employmentStatus: (string) ($request->validated('employment_status') ?? 'probation'),
            shiftId: (int) $request->validated('shift_id'),
            workLocationId: (int) $request->validated('work_location_id'),
            baseSalary: (float) $request->input('compensation.base_salary'),
            transportAllowance: (float) ($request->input('compensation.transport_allowance') ?? 0),
            mealAllowance: (float) ($request->input('compensation.meal_allowance') ?? 0),
            sendInvitation: (bool) ($request->validated('send_invitation') ?? true),
            invitationChannels: (array) ($request->validated('invitation_channels') ?? ['email', 'whatsapp']),
            invitationExpiresInDays: (int) ($request->validated('invitation_expires_in_days') ?? 3),
        );

        $result = $this->creator->create($input, $request->user()?->id);

        $payload = [
            'employee' => $result->employeeData,
            'compensation' => $result->compensationData,
            'shift_assignment' => $result->shiftAssignmentData,
            'work_location_assignment' => $result->workLocationAssignmentData,
            'initial_password' => $result->initialPassword,
            'must_change_password' => $result->mustChangePassword,
        ];

        if ($result->invitationData !== null) {
            $payload['invitation'] = $result->invitationData;
        }

        return ApiResponse::success($payload, null, 201);
    }
}
