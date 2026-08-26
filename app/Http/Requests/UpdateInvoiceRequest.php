<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_name'        => ['required', 'string', 'max:100'],
            'patient_phone'       => ['nullable', 'string', 'max:30'],
            'items'               => ['required', 'array', 'min:1'],
            'items.*.item_type'   => ['required', 'string', 'in:consultation,prescription,lab_test,room,other'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.qty'         => ['required', 'integer', 'min:1'],
            'items.*.unit_price'  => ['required', 'numeric', 'min:0'],
            'notes'               => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'patient_name.required'        => 'សូមបញ្ចូលឈ្មោះអ្នកជំងឺ (Patient name is required).',
            'items.required'               => 'សូមបញ្ចូលយ៉ាងហោចណាស់ធាតុទូទាត់មួយ (At least one invoice item is required).',
            'items.*.description.required' => 'សូមបញ្ចូលបរិយាយសេវាកម្ម (Item description is required).',
            'items.*.qty.min'              => 'ចំនួនត្រូវតែធំជាង 0 (Quantity must be at least 1).',
            'items.*.unit_price.min'       => 'តម្លៃត្រូវតែធំជាង ឬស្មើ 0 (Unit price must be at least 0).',
        ];
    }

    /**
     * Belt-and-braces guard: even though the controller re-checks this
     * inside a locked transaction, reject early here too so the user gets
     * a clean 422 instead of a generic 500/403 deep in the request cycle.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $invoice = Invoice::find($this->route('id'));

            if ($invoice && !$invoice->isEditable()) {
                $validator->errors()->add(
                    'status',
                    'វិក្កយបត្រនេះលែងអាចកែប្រែបានទៀតហើយ (This invoice can no longer be edited).'
                );
            }
        });
    }
}
