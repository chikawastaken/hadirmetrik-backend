<?php

namespace App\Modules\Employees\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ResendInvitationRequest extends FormRequest
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
            'channels' => ['nullable', 'array', 'min:1'],
            'channels.*' => ['string', 'in:email,whatsapp'],
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:14'],
        ];
    }
}
