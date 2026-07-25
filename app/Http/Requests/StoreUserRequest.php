<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8'],
            'role'     => ['required', 'string', 'exists:roles,name'],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'     => 'សូមបញ្ចូលឈ្មោះ (Name is required).',
            'name.max'          => 'ឈ្មោះមិនអាចលើសពី ២៥៥ តួអក្សរឡើយ (Name must not exceed 255 characters).',
            'email.required'    => 'សូមបញ្ចូលអ៊ីមែល (Email is required).',
            'email.email'       => 'ទម្រង់អ៊ីមែលមិនត្រឹមត្រូវ (Must be a valid email format).',
            'email.unique'      => 'អ៊ីមែលនេះមានក្នុងប្រព័ន្ធរួចហើយ (Email already registered).',
            'username.required' => 'សូមបញ្ចូលឈ្មោះអ្នកប្រើប្រាស់ (Username is required).',
            'username.unique'   => 'ឈ្មោះអ្នកប្រើប្រាស់នេះមានក្នុងប្រព័ន្ធរួចហើយ (Username already taken).',
            'password.required' => 'សូមបញ្ចូលពាក្យសម្ងាត់ (Password is required).',
            'password.min'      => 'ពាក្យសម្ងាត់យ៉ាងហោចណាស់ ៨ តួអក្សរ (Password must be at least 8 characters).',
            'role.required'     => 'សូមជ្រើសរើសតួនាទី (Role is required).',
            'role.exists'       => 'តួនាទីដែលបានជ្រើសរើសមិនត្រឹមត្រូវ (Selected role is invalid).',
        ];
    }
}
