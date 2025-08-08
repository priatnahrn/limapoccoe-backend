<?php

namespace Modules\Auth\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Profile\Transformers\ProfileMasyarakatResource;

class AuthResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'nik'          => $this->nik,
            'no_whatsapp'  => $this->no_whatsapp,
            'username'     => $this->username ?? null,   // untuk internal user
            'email'        => $this->email ?? null,      // untuk staff/kepala desa
            'roles'        => $this->roles->pluck('name'), // hanya ambil nama rolenya
            'profile_masyarakat' => new ProfileMasyarakatResource($this->profileMasyarakat), 
        ];
    }
}
