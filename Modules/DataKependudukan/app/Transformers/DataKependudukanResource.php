<?php

namespace Modules\DataKependudukan\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DataKependudukanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nomor_kk' => $this->nomor_kk,
            'rumah_id' => $this->rumah_id,
            'rumah' => [
                'no_rumah' => $this->rumah->no_rumah ?? null,
                'dusun' => $this->rumah->dusun ?? null,
                'rt_rw' => $this->rumah->rt_rw ?? null,
            ],
            'jumlah_anggota' => $this->penduduks->count(),
            'anggota' => $this->penduduks->map(function ($anggota) {
                return [
                    'id' => $anggota->id,
                    'nik' => $anggota->nik,
                    'no_urut' => $anggota->no_urut,
                    'nama_lengkap' => $anggota->nama_lengkap,
                    'hubungan' => $anggota->hubungan,
                    'tempat_lahir' => $anggota->tempat_lahir,
                    'tgl_lahir' => $anggota->tgl_lahir,
                    'jenis_kelamin' => $anggota->jenis_kelamin,
                    'status_perkawinan' => $anggota->status_perkawinan,
                    'agama' => $anggota->agama,
                    'pendidikan' => $anggota->pendidikan,
                    'pekerjaan' => $anggota->pekerjaan,
                    'no_bpjs' => $anggota->no_bpjs,
                    'nama_ayah' => $anggota->nama_ayah,
                    'nama_ibu' => $anggota->nama_ibu,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
