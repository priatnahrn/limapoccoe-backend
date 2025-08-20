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
            'judul'     => 'sometimes|string|max:225',
            'konten'    => 'nullable|string',
            'kategori'  => 'nullable|in:berita,pengumuman,artikel,wisata,produk,banner,galeri',
            'gambar'    => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            // Tidak perlu validasi slug karena tidak dikirim dari frontend
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
            'gambar.image' => 'Format gambar tidak valid.',
            'gambar.max' => 'Ukuran gambar tidak boleh lebih dari 5MB.',
            'kategori.in' => 'Kategori tidak valid.',
        ];
    }
}
