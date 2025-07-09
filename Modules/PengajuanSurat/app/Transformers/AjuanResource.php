<?php

namespace Modules\PengajuanSurat\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\Transformers\AuthResource;

class AjuanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'surat_id' => $this->surat_id,
            'user' => new AuthResource($this->user),
            'nomor_surat' => $this->nomor_surat,
            'nomor_surat_tersimpan' => $this->nomor_surat_tersimpan,
            'data_surat' => is_string($this->data_surat)
                ? json_decode($this->data_surat, true)
                : $this->data_surat,
            'lampiran' => $this->lampiran,
            'status' => $this->status,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
