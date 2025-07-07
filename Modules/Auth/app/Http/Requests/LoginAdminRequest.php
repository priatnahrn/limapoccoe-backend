<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginAdminRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'username' => 'required|string|max:255',
            'password' => 'required|string|min:12',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'username.required' => 'Username harus diisi. Pastikan Anda memasukkan username yang benar.',
            'username.max' => 'Username tidak boleh lebih dari 255 karakter.',
            'password.required' => 'Password harus diisi. Pastikan password Anda kuat dan aman.',
            'password.min' => 'Password harus terdiri dari minimal 12 karakter.',
        ];
    }
}
