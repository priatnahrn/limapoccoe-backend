<?php

namespace Modules\Informasi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InformasiRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'judul' => 'required',
            'konten' => 'nullable',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:5120', // Maksimal 5MB
            'kategori' => 'nullable|in:berita,pengumuman,artikel,wisata,produk,banner,galeri',
            'created_by' => 'nullable|exists:auth_users,id',
            'updated_by' => 'nullable|exists:auth_users,id',
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
            'judul.required' => 'Judul informasi harus diisi.',
            'gambar.image' => 'File gambar harus berupa gambar.',
            'gambar.mimes' => 'Gambar harus berformat jpeg, png, atau jpg.',
            'gambar.max' => 'Ukuran gambar maksimal 5MB.',
            'kategori.in' => 'Kategori informasi tidak valid.',
        ];
    }
}
