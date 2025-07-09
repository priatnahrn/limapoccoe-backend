<?php

namespace Modules\PengajuanSurat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FillNumberRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'nomor_surat' => 'required|integer|min:1|unique:ajuans,nomor_surat',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation messages that apply to the request.
     */
    public function messages(): array
    {
        return [
            'nomor_surat.required' => 'Nomor surat harus diisi.',
            'nomor_surat.unique' => 'Nomor surat sudah digunakan.',
            'nomor_surat.integer' => 'Nomor surat harus berupa angka.',
            'nomor_surat.min' => 'Nomor surat harus lebih besar atau sama dengan 1.',
        ];
    }
}
