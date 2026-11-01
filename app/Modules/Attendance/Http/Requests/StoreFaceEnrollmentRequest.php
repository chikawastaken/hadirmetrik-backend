<?php

namespace App\Modules\Attendance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFaceEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'provider' => ['nullable', 'string', 'max:50'],
            'template_ref' => ['nullable', 'string', 'max:255'],
            'sample_count' => ['required', 'integer', 'min:1', 'max:20'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
