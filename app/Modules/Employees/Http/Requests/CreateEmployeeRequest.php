<?php

namespace App\Modules\Employees\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && in_array($this->user()->role, ['hr', 'admin'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'unique:employees,work_email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'division_id' => ['required', 'integer'],
            'job_title' => ['required', 'string', 'max:255'],
            'manager_id' => ['nullable', 'integer'],
            'employment_start' => ['required', 'date'],
            'employment_status' => ['nullable', 'string', 'in:active,probation,contract,terminated'],
            'shift_id' => ['required', 'integer'],
            'work_location_id' => ['required', 'integer'],
            'compensation' => ['required', 'array'],
            'compensation.base_salary' => ['required', 'numeric', 'min:0'],
            'compensation.transport_allowance' => ['nullable', 'numeric', 'min:0'],
            'compensation.meal_allowance' => ['nullable', 'numeric', 'min:0'],
            'send_invitation' => ['nullable', 'boolean'],
            'invitation_channels' => ['nullable', 'array'],
            'invitation_channels.*' => ['string', 'in:email,whatsapp'],
            'invitation_expires_in_days' => ['nullable', 'integer', 'min:1', 'max:14'],
        ];
    }
}
