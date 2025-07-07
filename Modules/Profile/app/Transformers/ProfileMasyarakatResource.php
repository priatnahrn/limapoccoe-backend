<?php

namespace Modules\Profile\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileMasyarakatResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
         return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'tempat_lahir' => $this->tempat_lahir,
            'tanggal_lahir' => $this->tanggal_lahir,
            'jenis_kelamin' => $this->jenis_kelamin,
            'golongan_darah' => $this->golongan_darah,
            'dusun' => $this->dusun,
            'alamat' => $this->alamat,
            'rt_rw' => $this->rt_rw,
            'kelurahan' => $this->kelurahan,
            'kecamatan' => $this->kecamatan,
            'kabupaten_kota' => $this->kabupaten_kota,
            'provinsi' => $this->provinsi,
            'agama' => $this->agama,
            'status_perkawinan' => $this->status_perkawinan,
            'pekerjaan' => $this->pekerjaan,
            'kewarganegaraan' => $this->kewarganegaraan,
            'pendidikan_terakhir' => $this->pendidikan_terakhir,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
