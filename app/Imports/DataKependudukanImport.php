<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Str;
use Modules\DataKependudukan\Models\Keluarga;
use Modules\DataKependudukan\Models\Penduduk;
use Modules\DataKependudukan\Models\Rumah;
/**
 * Class DataKependudukanImport
 * Import data kependudukan dari file Excel.
 *
 * @package App\Imports
 */


class DataKependudukanImport implements ToCollection
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $rows): void
    {
        DB::beginTransaction();

        try {
            $grouped = $rows->skip(1)->groupBy(6); // grup berdasarkan kolom 'NOMOR KK'

            foreach ($grouped as $kk => $dataKeluarga) {
                $firstRow = $dataKeluarga->first();

                // Simpan rumah
                $rumah = Rumah::firstOrCreate([
                    'no_rumah' => $firstRow[2],    // NO RMH
                    'rt_rw' => $firstRow[1],       // RT/RW
                    'dusun' => $firstRow[0],       // Dusun
                ], ['id' => Str::uuid()]);

                // Simpan keluarga
                $keluarga = Keluarga::firstOrCreate([
                    'nomor_kk' => $kk,
                ], [
                    'id' => Str::uuid(),
                    'rumah_id' => $rumah->id,
                ]);

                foreach ($dataKeluarga as $row) {
                    Penduduk::updateOrCreate([
                        'nik' => $row[7]
                    ], [
                        'keluarga_id' => $keluarga->id,
                        'no_urut' => $row[4] ?? null,
                        'nama_lengkap' => $row[5],
                        'hubungan' => $row[9],
                        'tempat_lahir' => $row[10],
                        'tgl_lahir' => sprintf('%04d-%02d-%02d', $row[13], $row[12], $row[11]),
                        'jenis_kelamin' => $row[14],
                        'status_perkawinan' => $row[15],
                        'agama' => $row[16],
                        'pendidikan' => $row[17],
                        'pekerjaan' => $row[18],
                        'no_bpjs' => $row[8],
                        'nama_ayah' => $row[19],
                        'nama_ibu' => $row[20],
                    ]);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
