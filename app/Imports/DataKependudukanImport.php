<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\DataKependudukan\Models\Keluarga;
use Modules\DataKependudukan\Models\Penduduk;
use Modules\DataKependudukan\Models\Rumah;

class DataKependudukanImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        DB::beginTransaction();

        try {
            $grouped = $rows->groupBy('nomor_kk');

            foreach ($grouped as $kk => $keluargaRows) {
                $first = $keluargaRows->first();

                // Simpan Rumah
                $rumah = Rumah::firstOrCreate([
                    'no_rumah' => $first['no_rumah'],
                    'rt_rw' => $first['rt_rw'],
                    'dusun' => $this->formatDusun($first['dusun']),
                ], ['id' => Str::uuid()]);

                // Simpan Keluarga
                $keluarga = Keluarga::firstOrCreate([
                    'nomor_kk' => $kk,
                ], [
                    'id' => Str::uuid(),
                    'rumah_id' => $rumah->id,
                ]);

                foreach ($keluargaRows as $row) {
                    $tglLahir = $this->formatTanggal($row['thn'], $row['bln'], $row['tgl']);

                    Penduduk::updateOrCreate([
                        'nik' => $row['nik'],
                    ], [
                        'keluarga_id' => $keluarga->id,
                        'nama_lengkap' => $row['nama_lengkap'],
                        'no_urut' => $row['no_urut'],
                        'hubungan' => $row['hubungan'],
                        'tempat_lahir' => $row['tempat_lahir'],
                        'tgl_lahir' => $tglLahir,
                        'jenis_kelamin' => $this->formatJK($row['jenis_kelamin']),
                        'status_perkawinan' => $this->formatStatus($row['status_perkawinan']),
                        'agama' => $this->formatAgama($row['agama']),
                        'pendidikan' => $this->formatPendidikan($row['pendidikan']),
                        'pekerjaan' => $row['pekerjaan'],
                        'no_bpjs' => $row['no_bpjs'],
                        'nama_ayah' => $row['ayah'],
                        'nama_ibu' => $row['ibu'],
                    ]);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function formatTanggal($thn, $bln, $tgl)
    {
        if (!$thn || !$bln || !$tgl) return null;
        return sprintf('%04d-%02d-%02d', $thn, $bln, $tgl);
    }

    private function formatJK($val)
    {
        return match(strtolower(trim($val))) {
            'laki-laki', 'l' => 'Laki-laki',
            'perempuan', 'p' => 'Perempuan',
            default => null,
        };
    }

    private function formatStatus($val)
    {
        return match(strtoupper(trim($val))) {
            'K', 'KAWIN' => 'Kawin',
            'BK', 'B.K', 'BELUM KAWIN' => 'Belum Kawin',
            'CM', 'CERAI MATI' => 'Cerai Mati',
            'CH', 'CERAI HIDUP' => 'Cerai Hidup',
            default => null,
        };
    }

    private function formatAgama($val)
    {
        return match(strtoupper(trim($val))) {
            'ISLAM', 'MUSLIM' => 'Islam',
            'KRISTEN' => 'Kristen',
            'KATOLIK' => 'Katolik',
            'HINDU' => 'Hindu',
            'BUDDHA' => 'Buddha',
            'KONGHUCU' => 'Konghucu',
            default => null,
        };
    }

    private function formatPendidikan($val)
    {
        return match(strtoupper(trim($val))) {
            'SD' => 'Tamat SD/Sederajat',
            'SMP', 'SLTP' => 'SLTP/Sederajat',
            'SMA', 'SLTA' => 'SLTA/Sederajat',
            'TK', 'TAMAN KANAK-KANAK', '-' => 'Tidak/Belum Sekolah',
            'S1' => 'S-1',
            'S2' => 'S-2',
            'S3' => 'S-3',
            'D1', 'D-1', 'D2', 'D-2' => 'D-1/D-2',
            'D3', 'D-3' => 'D-3',
            default => null,
        };
    }

    private function formatDusun($val)
    {
        return str_replace(['Wt. Bengo', 'WT. Bengo'], 'WT.Bengo', trim($val));
    }
}
