<?php

namespace Modules\Pengaduan\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PengaduanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'location' => $this->location,
            'category' => $this->category,
            'status' => $this->status,
            'evidence_url' => $this->evidence 
                ? asset('storage/' . $this->evidence)
                : null,
            'created_at' => $this->created_at->toDateTimeString(),
            'user' => [
                'id' => optional($this->user)->id,
                'name' => optional($this->user)->name,
                'nik' => optional($this->user)->nik,
            ],
            'response' => $this->response,
            'response_date' => $this->response_date,
            'responded_by' => $this->whenLoaded('responseBy', function () {
                return [
                    'id' => optional($this->responseBy)->id,
                    'name' => optional($this->responseBy)->name,
                ];
            }),
        ];
    }
}
