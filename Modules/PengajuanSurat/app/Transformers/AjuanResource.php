<?php

namespace Modules\PengajuanSurat\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AjuanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'surat_id' => $this->surat_id,
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
