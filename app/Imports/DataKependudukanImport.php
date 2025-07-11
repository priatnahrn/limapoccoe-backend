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

            foreach ($grouped as $kk => $dataKeluarga) {
                $firstRow = $dataKeluarga->first();

                // Validasi & normalisasi dusun
                $validatedDusun = $this->formatDusun($firstRow['dusun']);
                if (!$validatedDusun) {
                    throw new \Exception("Dusun tidak valid: " . $firstRow['dusun']);
                }

                $rumah = Rumah::firstOrCreate([
                    'no_rumah' => $firstRow['no_rumah'],
                    'rt_rw' => $firstRow['rt_rw'],
                    'dusun' => $validatedDusun,
                ], [
                    'id' => Str::uuid(),
                ]);

                $keluarga = Keluarga::firstOrCreate([
                    'nomor_kk' => $kk,
                ], [
                    'id' => Str::uuid(),
                    'rumah_id' => $rumah->id,
                ]);

                foreach ($dataKeluarga as $row) {
                    Penduduk::updateOrCreate([
                        'nik' => $row['nik'],
                    ], [
                        'id' => Str::uuid(),
                        'keluarga_id' => $keluarga->id,
                        'no_urut' => $row['no_urut'],
                        'nama_lengkap' => $row['nama_lengkap'],
                        'hubungan' => $this->formatHubungan($row['hubungan']),
                        'tempat_lahir' => $row['tempat_lahir'],
                        'tgl_lahir' => $this->formatTanggal($row['thn'], $row['bln'], $row['tgl']),
                        'jenis_kelamin' => $this->formatJenisKelamin($row['jenis_kelamin']),
                        'status_perkawinan' => $this->formatStatusPerkawinan($row['status_perkawinan']),
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

    private function formatDusun($val): ?string
    {
        return match(str_replace([' ', '.', ','], '', strtolower($val))) {
            'wtbengo', 'wt.bengo' => 'WT.Bengo',
            'barua' => 'Barua',
            'mappasaile' => 'Mappasaile',
            'samata' => 'Samata',
            'kampala' => 'Kampala',
            'kaluku' => 'Kaluku',
            'jambua' => 'Jambua',
            'bontopanno' => 'Bontopanno',
            default => null,
        };
    }

    private function formatTanggal($thn, $bln, $tgl): ?string
    {
        if (!$thn || !$bln || !$tgl) return null;
        return sprintf('%04d-%02d-%02d', $thn, $bln, $tgl);
    }

    private function formatHubungan($val): ?string
    {
        return match(strtolower(trim($val))) {
            'kk', 'kepala keluarga' => 'Kepala Keluarga',
            'istri' => 'Istri',
            'anak' => 'Anak',
            'cucu' => 'Cucu',
            'famili', 'famili lain' => 'Famili Lain',
            'saudara' => 'Saudara',
            'orang tua' => 'Orang Tua',
            default => null,
        };
    }

    private function formatJenisKelamin($val): ?string
    {
        return match(strtolower(trim($val))) {
            'l', 'lk', 'laki', 'laki-laki' => 'Laki-laki',
            'p', 'pr', 'perempuan' => 'Perempuan',
            default => null,
        };
    }

    private function formatStatusPerkawinan($val): ?string
    {
        return match(strtolower(trim($val))) {
            'bk', 'belum kawin', 'b.k' => 'Belum Kawin',
            'k', 'kawin' => 'Kawin',
            'cerai hidup' => 'Cerai Hidup',
            'cerai mati' => 'Cerai Mati',
            default => null,
        };
    }

    private function formatAgama($val): ?string
    {
        return match(strtolower(trim($val))) {
            'islam' => 'Islam',
            'kristen' => 'Kristen',
            'katolik' => 'Katolik',
            'hindu' => 'Hindu',
            'buddha' => 'Buddha',
            'konghucu' => 'Konghucu',
            default => 'Lainnya',
        };
    }

    private function formatPendidikan($val): ?string
    {
        return match(strtoupper(trim($val))) {
            '', '-' => null,
            'TK' => 'Tidak/Belum Sekolah',
            'BTSD', 'BELUM TAMAT SD' => 'Belum Tamat SD/Sederajat',
            'SD' => 'Tamat SD/Sederajat',
            'SMP', 'SLTP' => 'SLTP/Sederajat',
            'SMA', 'SMK', 'SLTA' => 'SLTA/Sederajat',
            'D1', 'D-1', 'D2', 'D-2' => 'D-1/D-2',
            'D3', 'D-3' => 'D-3',
            'S1', 'S-1' => 'S-1',
            'S2', 'S-2' => 'S-2',
            'S3', 'S-3' => 'S-3',
            default => null,
        };
    }
}
