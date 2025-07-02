<?php

namespace Modules\PengajuanSurat\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Modules\PengajuanSurat\Models\Surat;
use Illuminate\Support\Facades\DB;


class PengajuanSuratDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $surats = [
            [
                'kode_surat' => 'SKTM',
                'nama_surat' => 'Surat Keterangan Tidak Mampu',
                'deskripsi' => 'Untuk keperluan bantuan pendidikan, kesehatan, atau sosial.',
                'syarat_ketentuan' => 'Fotokopi KTP, Fotokopi KK, Surat pengantar RT/RW',
            ],
            [
                'kode_surat' => 'SKD',
                'nama_surat' => 'Surat Keterangan Domisili',
                'deskripsi' => 'Digunakan sebagai bukti tempat tinggal resmi.',
                'syarat_ketentuan' => 'Fotokopi KTP, Surat pengantar RT/RW',
            ],
            [
                'kode_surat' => 'SKU',
                'nama_surat' => 'Surat Keterangan Usaha',
                'deskripsi' => 'Untuk keperluan legalitas usaha warga.',
                'syarat_ketentuan' => 'Fotokopi KTP, Foto tempat usaha, Surat pengantar RT/RW',
            ],
            [
                'kode_surat' => 'SPKK',
                'nama_surat' => 'Surat Pengantar KK',
                'deskripsi' => 'Surat pengantar untuk pengurusan KK baru atau perubahan.',
                'syarat_ketentuan' => 'Fotokopi KTP, Surat pengantar RT/RW, Bukti perubahan data',
            ],
            [
                'kode_surat' => 'SPKTP',
                'nama_surat' => 'Surat Pengantar KTP',
                'deskripsi' => 'Untuk keperluan pembuatan atau pembaruan KTP.',
                'syarat_ketentuan' => 'Fotokopi KK, Surat pengantar RT/RW',
            ],
            [
                'kode_surat' => 'SKL',
                'nama_surat' => 'Surat Keterangan Lahir',
                'deskripsi' => 'Untuk pengurusan akta kelahiran.',
                'syarat_ketentuan' => 'Fotokopi KK, Surat dari bidan/rumah sakit, Fotokopi KTP orang tua',
            ],
            [
                'kode_surat' => 'SKM',
                'nama_surat' => 'Surat Keterangan Kematian',
                'deskripsi' => 'Untuk pengurusan akta kematian atau administrasi lainnya.',
                'syarat_ketentuan' => 'Fotokopi KK, Surat dari rumah sakit, Fotokopi KTP ahli waris',
            ],
            [
                'kode_surat' => 'SPPD',
                'nama_surat' => 'Surat Pengantar Pindah Domisili',
                'deskripsi' => 'Untuk pindah keluar desa atau kecamatan.',
                'syarat_ketentuan' => 'Fotokopi KK, Fotokopi KTP, Surat pengantar RT/RW',
            ],
        ];

        foreach ($surats as $item) {
            // Generate slug manually
            $slug = 'surat-' . Str::of($item['nama_surat'])
                ->lower()
                ->replaceFirst('surat ', '')
                ->slug('-');

            Surat::create([
                'id' => Str::uuid(),
                'kode_surat' => $item['kode_surat'],
                'nama_surat' => $item['nama_surat'],
                'slug' => $slug,
                'deskripsi' => $item['deskripsi'],
                'syarat_ketentuan' => $item['syarat_ketentuan'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
