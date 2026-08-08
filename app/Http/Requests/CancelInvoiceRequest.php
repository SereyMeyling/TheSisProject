<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cancel_reason' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'cancel_reason.required' => 'សូមបញ្ចូលមូលហេតុនៃការលុបចោល (A cancellation reason is required).',
            'cancel_reason.min'      => 'មូលហេតុត្រូវតែមានយ៉ាងតិច 5 តួអក្សរ (Reason must be at least 5 characters).',
        ];
    }
}
