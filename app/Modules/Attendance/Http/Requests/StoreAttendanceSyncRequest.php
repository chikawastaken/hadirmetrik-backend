<?php

namespace App\Modules\Attendance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceSyncRequest extends FormRequest
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
            'idempotency_key' => ['required', 'string', 'max:100'],
            'device_id' => ['nullable', 'string', 'max:100'],
            'events' => ['required', 'array', 'min:1', 'max:100'],
            'events.*.client_event_id' => ['required', 'string', 'max:100'],
            'events.*.idempotency_key' => ['nullable', 'string', 'max:100'],
            'events.*.type' => ['required', 'in:clock_in,clock_out'],
            'events.*.occurred_at' => ['required', 'date'],
            'events.*.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'events.*.longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
