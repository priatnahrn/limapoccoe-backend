<?php

namespace Modules\PengajuanSurat\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TandaTanganResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ajuan_id' => $this->ajuan_id,
            'signed_by' => $this->signed_by,
            'signed_at' => $this->signed_at->toIso8601String(),
            'nomor_surat' => $this->ajuan->nomor_surat ?? null, // butuh eager load relasi ajuan
            'signed_by_name' => $this->signedBy?->name, // jika ada relasi ke user
        ];
    }
}
