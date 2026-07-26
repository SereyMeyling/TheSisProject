<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method'  => ['required', 'string', 'in:cash,khqr,card'],
            'amount'          => ['required', 'numeric', 'min:0.01'],
            'transaction_ref' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => 'សូមជ្រើសរើសវិធីសាស្ត្រទូទាត់ (Payment method is required).',
            'amount.required'         => 'សូមបញ្ចូលចំនួនប្រាក់ទូទាត់ (Amount is required).',
            'amount.min'              => 'ចំនួនប្រាក់ទូទាត់ត្រូវតែធំជាង 0 (Amount must be at least 0.01).',
        ];
    }
}
