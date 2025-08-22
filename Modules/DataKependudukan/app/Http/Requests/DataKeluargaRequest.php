<?php

namespace Modules\DataKependudukan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class DataKeluargaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = in_array($this->method(), ['PUT', 'PATCH']);
        $keluargaId = $this->route('id');

        return [
            // Keluarga
            'nomor_kk' => $isUpdate
                ? ['sometimes', 'string', 'max:20', Rule::unique('keluargas', 'nomor_kk')->ignore($keluargaId)]
                : ['required', 'string', 'max:20', Rule::unique('keluargas', 'nomor_kk')],

            // Rumah
            'no_rumah' => $isUpdate ? ['sometimes', 'string', 'max:10'] : ['required', 'string', 'max:10'],
            'rt_rw' => ['nullable', 'string', 'max:7'],
            'dusun' => [$isUpdate ? 'sometimes' : 'required', Rule::in([
                'WT.Bengo', 'Barua', 'Mappasaile', 'Kampala', 'Kaluku', 'Jambua', 'Bontopanno', 'Samata'
            ])],

            // Anggota
            'anggota' => ['nullable', 'array'],
            'anggota.*.id' => ['sometimes', 'uuid'], // saat update
            'anggota.*.nik' => $isUpdate
                ? ['sometimes', 'string', 'max:20']
                : ['required_with:anggota', 'string', 'max:20', Rule::unique('penduduks', 'nik')],
            'anggota.*.no_urut' => ['nullable', 'string', 'max:10'],
            'anggota.*.nama_lengkap' => ['required_with:anggota', 'string', 'max:100'],
            'anggota.*.hubungan' => ['nullable', Rule::in([
                'Kepala Keluarga', 'Istri', 'Anak', 'Cucu', 'Famili Lain', 'Saudara', 'Orang Tua'
            ])],
            'anggota.*.tempat_lahir' => ['nullable', 'string', 'max:50'],
            'anggota.*.tgl_lahir' => ['nullable', 'date'],
            'anggota.*.jenis_kelamin' => ['nullable', Rule::in(['Laki-laki', 'Perempuan'])],
            'anggota.*.status_perkawinan' => ['nullable', Rule::in(['Belum Kawin', 'Kawin', 'Kawin Tercatat', 'Kawin Belum Tercatat', 'Cerai Hidup', 'Cerai Mati'])],
            'anggota.*.agama' => ['nullable', Rule::in(['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Lainnya'])],
            'anggota.*.pendidikan' => ['nullable', Rule::in([
                'Tidak/Belum Sekolah', 'Belum Tamat SD/Sederajat', 'Tamat SD/Sederajat',
                'SLTP/Sederajat', 'SLTA/Sederajat', 'D-1/D-2', 'D-3', 'S-1', 'S-2', 'S-3'
            ])],
            'anggota.*.pekerjaan' => ['nullable', 'string', 'max:50'],
            'anggota.*.no_bpjs' => ['nullable', 'string', 'max:20'],
            'anggota.*.nama_ayah' => ['nullable', 'string', 'max:100'],
            'anggota.*.nama_ibu' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'nomor_kk.required' => 'Nomor KK wajib diisi.',
            'nomor_kk.string' => 'Nomor KK harus berupa teks.',
            'nomor_kk.max' => 'Nomor KK maksimal 20 karakter.',
            'nomor_kk.unique' => 'Nomor KK sudah digunakan.',

            'no_rumah.string' => 'Nomor rumah harus berupa teks.',
            'no_rumah.max' => 'Nomor rumah maksimal 10 karakter.',
            'rt_rw.string' => 'RT/RW harus berupa teks.',
            'rt_rw.max' => 'RT/RW maksimal 7 karakter.',
            'dusun.required' => 'Dusun wajib diisi.',
            'dusun.in' => 'Dusun tidak valid.',

            'anggota.required' => 'Data anggota wajib diisi.',
            'anggota.array' => 'Data anggota harus berupa array.',

            'anggota.*.nik.required_with' => 'NIK wajib diisi.',
            'anggota.*.nik.max' => 'NIK maksimal 20 karakter.',
            'anggota.*.nik.unique' => 'NIK sudah digunakan.',
            'anggota.*.no_urut.max' => 'Nomor urut maksimal 10 karakter.',
            'anggota.*.nama_lengkap.required_with' => 'Nama lengkap wajib diisi.',
            'anggota.*.nama_lengkap.max' => 'Nama maksimal 100 karakter.',
            'anggota.*.hubungan.in' => 'Hubungan tidak valid.',
            'anggota.*.tempat_lahir.max' => 'Tempat lahir maksimal 50 karakter.',
            'anggota.*.tgl_lahir.date' => 'Tanggal lahir harus format tanggal.',
            'anggota.*.jenis_kelamin.in' => 'Jenis kelamin tidak valid.',
            'anggota.*.status_perkawinan.in' => 'Status perkawinan tidak valid.',
            'anggota.*.agama.in' => 'Agama tidak valid.',
            'anggota.*.pendidikan.in' => 'Pendidikan tidak valid.',
            'anggota.*.pekerjaan.max' => 'Pekerjaan maksimal 50 karakter.',
            'anggota.*.no_bpjs.max' => 'Nomor BPJS maksimal 20 karakter.',
            'anggota.*.nama_ayah.max' => 'Nama ayah maksimal 100 karakter.',
            'anggota.*.nama_ibu.max' => 'Nama ibu maksimal 100 karakter.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->isMethod('put') || $this->isMethod('patch')) {
                $anggotaList = $this->input('anggota', []);

                foreach ($anggotaList as $index => $anggota) {
                    if (!empty($anggota['nik'])) {
                        $exists = DB::table('penduduks')
                            ->where('nik', $anggota['nik'])
                            ->where('id', '!=', $anggota['id'] ?? '')
                            ->exists();

                        if ($exists) {
                            $validator->errors()->add("anggota.$index.nik", 'NIK sudah digunakan.');
                        }
                    }
                }
            }
        });
    }
}
