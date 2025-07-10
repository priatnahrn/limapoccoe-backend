<?php

namespace Modules\PengajuanSurat\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SuratResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'kode_surat' => $this->kode_surat,
            'nama_surat' => $this->nama_surat,
            'slug' => $this->slug,
            'deskripsi' => $this->deskripsi,
            'syarat_ketentuan' => $this->syarat_ketentuan,
            'jumlah_ajuan' => $this->ajuan_surat->count(), // Assuming you have a count of related Ajuan
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at, 
        ];
    }
}
