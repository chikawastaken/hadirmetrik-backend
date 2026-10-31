<?php

namespace App\Modules\Employees\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone_e164' => ['nullable', 'string', 'max:20', 'regex:/^\+[1-9]\d{1,14}$/'],
            'work_email' => ['nullable', 'email', 'max:255', 'lowercase'],
            'division_id' => ['nullable', 'integer', 'exists:divisions,id'],
            'job_title' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone_e164.regex' => 'Format telepon tidak valid (E.164).',
            'work_email.unique' => 'Email sudah digunakan karyawan lain.',
            'work_email.lowercase' => 'Email harus dalam huruf kecil.',
            'division_id.exists' => 'Divisi tidak aktif atau tidak ditemukan.',
        ];
    }
}
