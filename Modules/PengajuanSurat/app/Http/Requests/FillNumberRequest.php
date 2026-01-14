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
            'nomor_surat_tersimpan' => 'required|string|max:255|unique:ajuans,nomor_surat_tersimpan',
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
            'nomor_surat_tersimpan.required' => 'Nomor surat harus diisi.',
            'nomor_surat_tersimpan.string'   => 'Nomor surat harus berupa teks.',
            'nomor_surat_tersimpan.unique'   => 'Nomor surat sudah digunakan.',
            'nomor_surat_tersimpan.max'      => 'Nomor surat terlalu panjang.',
        ];
    }
}
