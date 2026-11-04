<?php

namespace App\Modules\Tasks\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreTaskRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', 'string', 'in:high,medium,low'],
            'division_id' => ['nullable', 'integer'],
            'assignee_ids' => ['required', 'array', 'min:1'],
            'assignee_ids.*' => ['required', 'integer', 'distinct'],
            'checklist_template' => ['nullable', 'array'],
            'checklist_template.*.key' => ['required', 'string', 'max:64'],
            'checklist_template.*.label' => ['required', 'string', 'max:255'],
            'requires_photo_proof' => ['nullable', 'boolean'],
            'due_at' => ['nullable', 'date'],
        ];
    }
}
