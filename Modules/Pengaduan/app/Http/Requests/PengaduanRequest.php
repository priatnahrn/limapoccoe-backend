<?php

namespace Modules\Pengaduan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PengaduanRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'location' => 'nullable|string|max:255',
            'category' => [
                'required',
                Rule::in([
                    'Administrasi',
                    'Infrastruktur & Fasilitas',
                    'Kesehatan',
                    'Keamanan & Ketertiban',
                    'Pendidikan',
                    'Lingkungan',
                    'Kinerja Perangkat Desa',
                    'Ekonomi & Pekerjaan',
                    'Teknologi',
                    'Lainnya',
                ]),
            ],
            'evidence' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
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
            'title.required' => 'Judul pengaduan harus diisi.',
            'content.required' => 'Isi pengaduan harus diisi.',
            'location.max' => 'Lokasi tidak boleh lebih dari 255 karakter.',
            'category.required' => 'Kategori pengaduan harus dipilih.',
            'category.in' => 'Kategori pengaduan tidak valid.',
            'evidence.file' => 'Bukti harus berupa file.',
            'evidence.mimes' => 'Bukti harus berupa file dengan format jpg, jpeg, atau png.',
            'evidence.max' => 'Bukti tidak boleh lebih dari 2MB.',
        ];
    }
}
