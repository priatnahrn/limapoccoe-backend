<?php

namespace Modules\PengajuanSurat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AjuanRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'data_surat' => 'required|array',
            'lampiran' => 'nullable|array',
            "lampiran.*" => 'nullable|file|mimes:jpg,jpeg,png|max:5120',

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
            'data_surat.required' => 'Data Surat harus diisi.',
            'lampiran.*.file' => 'Lampiran harus berupa file.',
            'lampiran.*.mimes' => 'Lampiran harus berupa file dengan format jpg, jpeg, atau png.',
            'lampiran.*.max' => 'Lampiran tidak boleh lebih dari 5MB.',
        ];
    }
}
