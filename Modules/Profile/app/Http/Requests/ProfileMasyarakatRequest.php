<?php

namespace Modules\Profile\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileMasyarakatRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'tempat_lahir' => 'required|string|max:100',
            'tanggal_lahir' => 'required|date|before_or_equal:today',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'dusun' => 'required|in:WT.Bengo,Barua,Mappasaile,Kampala,Kaluku,Jambua,Bontopanno,Samata',
            'pekerjaan' => 'nullable|string|max:100',
            'rt_rw' => 'nullable|string|max:12',
            'alamat' => 'required|string|max:255',
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
            'tempat_lahir.required' => 'Tempat lahir harus diisi.',
            'tanggal_lahir.required' => 'Tanggal lahir harus diisi.',
            'tanggal_lahir.date' => 'Tanggal lahir harus berupa tanggal yang valid.',
            'tanggal_lahir.before_or_equal' => 'Tanggal lahir tidak boleh di masa depan.',
            'jenis_kelamin.required' => 'Jenis kelamin harus dipilih.',
            'jenis_kelamin.in' => 'Jenis kelamin harus salah satu dari Laki-laki atau Perempuan.',
            'dusun.required' => 'Dusun harus dipilih.',
            'dusun.in' => 'Dusun harus salah satu dari WT.Bengo, Barua, Mappasaile, Kampala, Kaluku, Jambua, Bontopanno, atau Samata.',
            'pekerjaan.max' => 'Pekerjaan tidak boleh lebih dari 100 karakter.',
            'rt_rw.max' => 'RT/RW tidak boleh lebih dari 12 karakter.',
            'alamat.required' => 'Alamat harus diisi.',
            'alamat.max' => 'Alamat tidak boleh lebih dari 255 karakter.',
        ];
    }
}
