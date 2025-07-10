<?php

namespace Modules\DataKependudukan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DataRumahRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna diizinkan untuk membuat permintaan ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi untuk permintaan ini.
     */
    public function rules(): array
    {
        return [
            'no_rumah' => 'nullable|string|max:10',
            'rt_rw' => 'nullable|string|max:7',
            'dusun' => 'required|in:WT.Bengo,Barua,Mappasaile,Kampala,Kaluku,Jambua,Bontopanno,Samata',
        ];
    }

    /**
     * Pesan kustom untuk validasi (opsional).
     */
    public function messages(): array
    {
        return [
            'dusun.required' => 'Dusun wajib dipilih.',
            'dusun.in' => 'Dusun yang dipilih tidak valid.',
        ];
    }
}
