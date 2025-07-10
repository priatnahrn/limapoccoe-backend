<?php

namespace Modules\DataKependudukan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DataPendudukRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'keluarga_id' => 'required|uuid|exists:keluargas,id',
            'nik' => 'required|string|max:20|unique:penduduks,nik,' . $this->route('penduduk'),
            'no_urut' => 'nullable|string|max:10',
            'nama_lengkap' => 'required|string|max:100',
            'hubungan' => 'nullable|in:Kepala Keluarga,Istri,Anak,Cucu,Famili Lain,Saudara,Orang Tua',
            'tempat_lahir' => 'nullable|string|max:50',
            'tgl_lahir' => 'nullable|date',
            'jenis_kelamin' => 'nullable|in:Laki-laki,Perempuan',
            'status_perkawinan' => 'nullable|in:Belum Kawin,Kawin,Cerai Hidup,Cerai Mati',
            'agama' => 'nullable|in:Islam,Kristen,Katolik,Hindu,Buddha,Konghucu,Lainnya',
            'pendidikan' => 'nullable|in:Tidak/Belum Sekolah,Belum Tamat SD/Sederajat,Tamat SD/Sederajat,SLTP/Sederajat,SLTA/Sederajat,D-1/D-2,D-3,S-1,S-2,S-3',
            'pekerjaan' => 'nullable|string|max:50',
            'no_bpjs' => 'nullable|string|max:20',
            'nama_ayah' => 'nullable|string|max:100',
            'nama_ibu' => 'nullable|string|max:100',
        ];
    }

    /**
     * Custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'keluarga_id.required' => 'ID keluarga wajib diisi.',
            'keluarga_id.uuid' => 'ID keluarga tidak valid.',
            'keluarga_id.exists' => 'ID keluarga tidak ditemukan dalam sistem.',

            'nik.required' => 'NIK wajib diisi.',
            'nik.string' => 'NIK harus berupa teks.',
            'nik.max' => 'NIK maksimal 20 karakter.',
            'nik.unique' => 'NIK sudah digunakan.',

            'no_urut.max' => 'No urut maksimal 10 karakter.',

            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'nama_lengkap.max' => 'Nama lengkap maksimal 100 karakter.',

            'hubungan.in' => 'Hubungan tidak valid.',

            'tempat_lahir.max' => 'Tempat lahir maksimal 50 karakter.',
            'tgl_lahir.date' => 'Tanggal lahir harus berupa tanggal yang valid.',
            'jenis_kelamin.in' => 'Jenis kelamin harus Laki-laki atau Perempuan.',
            'status_perkawinan.in' => 'Status perkawinan tidak valid.',
            'agama.in' => 'Agama tidak valid.',
            'pendidikan.in' => 'Tingkat pendidikan tidak valid.',
            'pekerjaan.max' => 'Pekerjaan maksimal 50 karakter.',
            'no_bpjs.max' => 'No BPJS maksimal 20 karakter.',
            'nama_ayah.max' => 'Nama ayah maksimal 100 karakter.',
            'nama_ibu.max' => 'Nama ibu maksimal 100 karakter.',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
