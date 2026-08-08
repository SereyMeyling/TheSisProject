<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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

    /**
     * These checks are advisory (fast feedback for the UI). The
     * authoritative check happens inside BillingController::processPayment()
     * under a locked row, because balance can change between this request
     * validating and the transaction actually running.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $invoice = Invoice::find($this->route('id'));

            if (!$invoice) {
                return;
            }

            if ($invoice->isCancelled()) {
                $validator->errors()->add(
                    'status',
                    'វិក្កយបត្រនេះត្រូវបានលុបចោល មិនអាចទទួលការទូទាត់បានទេ (This invoice is cancelled and cannot accept payment).'
                );
                return;
            }

            if ($this->filled('amount') && (float) $this->amount > (float) $invoice->balance) {
                $validator->errors()->add(
                    'amount',
                    'ចំនួនប្រាក់ទូទាត់លើសពីសមតុល្យនៅសល់ (Amount exceeds the remaining balance).'
                );
            }
        });
    }
}
