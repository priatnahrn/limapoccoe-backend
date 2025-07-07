<?php

namespace Modules\Pengaduan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessedAduanRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'response' => 'required|string|max:1000',
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
            'response.required' => 'Respon wajib diisi.',
        ];
    }
}
