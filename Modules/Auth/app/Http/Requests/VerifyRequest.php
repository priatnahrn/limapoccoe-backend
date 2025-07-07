<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'registration_token' => 'required|string',
            'otp_code' => 'required|digits:6',
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
            'registration_token.required' => 'Token pendaftaran harus diisi. Pastikan Anda memasukkan token yang benar.',
            'otp_code.required' => 'Kode OTP harus diisi. Pastikan Anda memasukkan kode OTP yang benar.',
            'otp_code.digits' => 'Kode OTP harus terdiri dari 6 digit.',
        ];
    }
}
