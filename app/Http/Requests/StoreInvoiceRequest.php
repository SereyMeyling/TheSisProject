<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreInvoiceRequest extends FormRequest
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
            'patient_id'          => ['nullable', 'exists:patients,patient_id'],

            // NULL = OPD invoice. When present, must be a real admission.
            'admission_id'        => ['nullable', 'exists:admissions,admission_id'],

            'items'               => ['required', 'array', 'min:1'],
            'items.*.item_type'   => ['required', 'string', 'in:consultation,prescription,lab_test,room,service,medicine,lab,other'],
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
            'admission_id.exists'          => 'ការចូលសម្រាកព្យាបាលនេះមិនត្រឹមត្រូវទេ (Selected admission is invalid).',
            'items.required'               => 'សូមបញ្ចូលយ៉ាងហោចណាស់ធាតុទូទាត់មួយ (At least one invoice item is required).',
            'items.*.description.required' => 'សូមបញ្ចូលបរិយាយសេវាកម្ម (Item description is required).',
            'items.*.qty.min'              => 'ចំនួនត្រូវតែធំជាង 0 (Quantity must be at least 1).',
            'items.*.unit_price.min'       => 'តម្លៃត្រូវតែធំជាង ឬស្មើ 0 (Unit price must be at least 0).',
        ];
    }

    /**
     * Cross-field check: an admission, if provided, must belong to the same
     * patient the invoice is being raised for.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->filled('admission_id') && $this->filled('patient_id')) {
                $belongsToPatient = \App\Models\Admission::where('admission_id', $this->admission_id)
                    ->where('patient_id', $this->patient_id)
                    ->exists();

                if (!$belongsToPatient) {
                    $validator->errors()->add(
                        'admission_id',
                        'ការចូលសម្រាកព្យាបាលនេះមិនមែនជារបស់អ្នកជំងឺដែលបានជ្រើសរើសទេ (This admission does not belong to the selected patient).'
                    );
                }
            }
        });
    }
}
