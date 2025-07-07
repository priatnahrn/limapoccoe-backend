<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginMasyarakatRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'nik' => 'required|digits:16',
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
            'nik.required' => 'NIK harus diisi. Pastikan Anda memasukkan NIK yang benar.',
            'nik.digits' => 'NIK harus terdiri dari 16 digit.',
            'password.required' => 'Password harus diisi. Pastikan password Anda kuat dan aman.',
            'password.min' => 'Password harus terdiri dari minimal 12 karakter.',
        ];
    }
}
