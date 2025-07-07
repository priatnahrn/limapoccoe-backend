<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'nik' => 'required|digits:16|unique:auth_users,nik',
            'no_whatsapp' => 'required|string|min:11|unique:auth_users,no_whatsapp',
            'password' => 'required|string|min:12|confirmed',
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
            'name.required' => 'Nama lengkap harus diisi. Pastikan Anda mengisi nama lengkap dengan benar.',
            'name.max' => 'Nama tidak boleh lebih dari 255 karakter.',
            'nik.required' => 'NIK harus diisi. Pastikan Anda memasukkan NIK yang benar.',
            'nik.digits' => 'NIK harus terdiri dari 16 digit.',
            'nik.unique' => 'NIK sudah terdaftar. Mohon gunakan NIK lain.',
            'no_whatsapp.required' => 'Nomor WhatsApp harus diisi. Pastikan Anda memasukkan nomor WhatsApp yang benar.',
            'no_whatsapp.min' => 'Nomor WhatsApp harus terdiri dari minimal 11 angka.',
            'no_whatsapp.unique' => 'Nomor WhatsApp sudah terdaftar. Mohon gunakan nomor WhatsApp lain.',
            'password.required' => 'Password harus diisi. Pastikan password Anda kuat dan aman.',
            'password.min' => 'Password harus terdiri dari minimal 12 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok. Pastikan Anda memasukkan password yang sesuai.',
        ];
    }
}
