<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
                $validatedDusun = $this->formatDusun($firstRow['dusun'] ?? '');
                if (!$validatedDusun) {
                    throw new \Exception("Dusun tidak valid: " . ($firstRow['dusun'] ?? '[KOSONG]'));
                }

                // Rumah
                $rumah = Rumah::firstOrCreate(
                    [
                        'no_rumah' => $firstRow['no_rumah'],
                        'rt_rw'    => $firstRow['rt_rw'],
                        'dusun'    => $validatedDusun,
                    ],
                    [
                        'id' => Str::uuid(),
                    ]
                );

                // Keluarga
                $keluarga = Keluarga::firstOrCreate(
                    ['nomor_kk' => $kk],
                    [
                        'id'       => Str::uuid(),
                        'rumah_id' => $rumah->id,
                    ]
                );

                // Anggota keluarga
                foreach ($dataKeluarga as $row) {
                    $tgl = $this->formatTanggal($row['thn'] ?? null, $row['bln'] ?? null, $row['tgl'] ?? null);

                    $hub = $this->formatHubungan($row['hubungan'] ?? null);
                    if (($row['hubungan'] ?? null) && $hub === null) {
                        Log::warning('Hubungan tak terpetakan', [
                            'asli' => $row['hubungan'],
                            'nik'  => $row['nik'] ?? null,
                        ]);
                    }

                    $jk = $this->formatJenisKelamin($row['jenis_kelamin'] ?? null);
                    if (($row['jenis_kelamin'] ?? null) && $jk === null) {
                        Log::warning('Jenis kelamin tak terpetakan', [
                            'asli' => $row['jenis_kelamin'],
                            'nik'  => $row['nik'] ?? null,
                        ]);
                    }

                    $status = $this->formatStatusPerkawinan($row['status_perkawinan'] ?? null);
                    if (($row['status_perkawinan'] ?? null) && $status === null) {
                        Log::warning('Status perkawinan tak terpetakan', [
                            'asli' => $row['status_perkawinan'],
                            'nik'  => $row['nik'] ?? null,
                        ]);
                    }

                    $pend = $this->formatPendidikan($row['pendidikan'] ?? null);
                    if (($row['pendidikan'] ?? null) && $pend === null) {
                        Log::warning('Pendidikan tak terpetakan', [
                            'asli' => $row['pendidikan'],
                            'nik'  => $row['nik'] ?? null,
                        ]);
                    }

                    // Penting: jangan set 'id' di updateOrCreate agar tidak mengubah PK saat update.
                    Penduduk::updateOrCreate(
                        ['nik' => $row['nik']],
                        [
                            'keluarga_id'       => $keluarga->id,
                            'no_urut'           => $row['no_urut'] ?? null,
                            'nama_lengkap'      => $row['nama_lengkap'] ?? null,
                            'hubungan'          => $hub,
                            'tempat_lahir'      => $row['tempat_lahir'] ?? null,
                            'tgl_lahir'         => $tgl,
                            'jenis_kelamin'     => $jk,
                            'status_perkawinan' => $status,
                            'agama'             => $this->formatAgama($row['agama'] ?? null),
                            'pendidikan'        => $pend,
                            'pekerjaan'         => $row['pekerjaan'] ?? null,
                            'no_bpjs'           => $row['no_bpjs'] ?? null,
                            'nama_ayah'         => $row['ayah'] ?? null,
                            'nama_ibu'          => $row['ibu'] ?? null,
                        ]
                    );
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Normalisasi string: lowercase, hapus NBSP, rapikan spasi, buang tanda baca ringan.
     */
    private function norm(?string $val): string
    {
        $s = strtolower($val ?? '');
        // Ganti NBSP → spasi biasa
        $s = str_replace("\xC2\xA0", ' ', $s);
        // Buang karakter selain huruf/angka/spasi/- dan /
        $s = preg_replace('/[^\p{L}\p{N}\s\-\/]/u', '', $s);
        // Rapiin spasi
        $s = preg_replace('/\s+/', ' ', trim($s));
        return $s;
    }

    private function formatDusun($val): ?string
    {
        $key = strtolower(trim(str_replace([' ', '.', ','], '', (string) $val)));
        return match ($key) {
            'wtbengo'    => 'WT.Bengo',
            'barua'      => 'Barua',
            'mappasaile' => 'Mappasaile',
            'samata'     => 'Samata',
            'kampala'    => 'Kampala',
            'kaluku'     => 'Kaluku',
            'jambua'     => 'Jambua',
            'bontopanno' => 'Bontopanno',
            default      => null,
        };
    }

    private function formatTanggal($thn, $bln, $tgl): ?string
    {
        if (!$thn || !$bln || !$tgl) return null;
        return sprintf('%04d-%02d-%02d', (int) $thn, (int) $bln, (int) $tgl);
    }

    private function formatHubungan($val): ?string
    {
        $s = $this->norm((string) $val);
        return match ($s) {
            'kk', 'kepala keluarga' => 'Kepala Keluarga',
            'istri'                 => 'Istri',
            'anak'                  => 'Anak',
            'cucu'                  => 'Cucu',
            'famili', 'famili lain' => 'Famili Lain',
            'saudara'               => 'Saudara',
            'orang tua'             => 'Orang Tua',
            default                 => null,
        };
    }

    private function formatJenisKelamin($val): ?string
    {
        $s = $this->norm((string) $val);
        return match ($s) {
            'l', 'lk', 'laki', 'laki-laki', 'laki laki' => 'Laki-laki',
            'p', 'pr', 'perempuan'                       => 'Perempuan',
            default                                      => null,
        };
    }

    private function formatStatusPerkawinan($val): ?string
    {
        $raw = trim((string) $val);
        $s   = $this->norm($raw);

        // Biarkan label final (sesuai file) langsung lewat
        $finals = [
            'belum kawin'          => 'Belum Kawin',
            'kawin'                => 'Kawin',
            'kawin tercatat'       => 'Kawin Tercatat',
            'kawin belum tercatat' => 'Kawin Belum Tercatat',
            'cerai hidup'          => 'Cerai Hidup',
            'cerai mati'           => 'Cerai Mati',
        ];
        if (isset($finals[$s])) return $finals[$s];

        // Alias umum
        return match ($s) {
            '', '-', 'tidak diketahui' => null,
            'bk', 'b k'                => 'Belum Kawin',
            'k'                        => 'Kawin',
            'kt', 'tercatat'           => 'Kawin Tercatat',
            'kbt', 'belum tercatat'    => 'Kawin Belum Tercatat',
            default                    => null,
        };
    }

    private function formatAgama($val): ?string
    {
        $s = $this->norm((string) $val);
        return match ($s) {
            'islam'    => 'Islam',
            'kristen'  => 'Kristen',
            'katolik'  => 'Katolik',
            'hindu'    => 'Hindu',
            'buddha'   => 'Buddha',
            'konghucu' => 'Konghucu',
            ''         => null,
            default    => 'Lainnya',
        };
    }

    private function formatPendidikan($val): ?string
    {
        $raw = trim((string) $val);
        $s   = $this->norm($raw);

        // Nilai final yang sering muncul di file
        $finals = [
            'tidak/belum sekolah'    => 'Tidak/Belum Sekolah',
            'belum tamat sd/sederajat'=> 'Belum Tamat SD/Sederajat',
            'tamat sd/sederajat'     => 'Tamat SD/Sederajat',
            'sltp/sederajat'         => 'SLTP/Sederajat',
            'slta/sederajat'         => 'SLTA/Sederajat',
        ];
        if (isset($finals[$s])) return $finals[$s];
        if (in_array($s, ['s1', 's-1', 's 1'], true)) return 'S-1';

        // Alias kode → label
        return match ($s) {
            '', '-'                        => null,
            'tk', 'paud'                   => 'Tidak/Belum Sekolah',
            'btsd', 'belum tamat sd'       => 'Belum Tamat SD/Sederajat',
            'sd'                           => 'Tamat SD/Sederajat',
            'smp', 'sltp'                  => 'SLTP/Sederajat',
            'sma', 'smk', 'slta'           => 'SLTA/Sederajat',
            'd1', 'd-1', 'd 1', 'd2', 'd-2', 'd 2' => 'D-1/D-2',
            'd3', 'd-3', 'd 3'             => 'D-3',
            's2', 's-2', 's 2'             => 'S-2',
            's3', 's-3', 's 3'             => 'S-3',
            default                        => null,
        };
    }
}
