<?php

namespace Modules\PengajuanSurat\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Modules\PengajuanSurat\Models\Surat;

class PengajuanSuratDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $surats = [
            [
                'kode_surat' => 'SKTM',
                'nama_surat' => 'Surat Keterangan Tidak Mampu',
                'deskripsi' => 'Surat resmi dari desa yang menyatakan seseorang berasal dari keluarga kurang mampu untuk keperluan bantuan sosial, pendidikan, kesehatan, dan administratif lainnya.',
                'syarat_ketentuan' => 'Fotokopi KK, Fotokopi KTP Pemohon/Orang Tua, Surat Pengantar RT/RW, Tujuan pembuatan SKTM, Mengisi formulir permohonan',
            ],
            [
                'kode_surat' => 'SKU',
                'nama_surat' => 'Surat Keterangan Usaha',
                'deskripsi' => 'Surat keterangan legalitas usaha warga untuk pengajuan kredit, perizinan, atau bantuan usaha mikro.',
                'syarat_ketentuan' => 'Fotokopi KTP, Fotokopi KK, Surat Pengantar RT/RW, Bukti keberadaan usaha, Mengisi formulir permohonan',
            ],
            [
                'kode_surat' => 'SKCK',
                'nama_surat' => 'Surat Pengantar SKCK',
                'deskripsi' => 'Surat pengantar dari desa untuk pengurusan SKCK di kepolisian.',
                'syarat_ketentuan' => 'Fotokopi KTP, Fotokopi KK, Surat Pengantar RT/RW, Pas foto terbaru',
            ],
            [
                'kode_surat' => 'SKD',
                'nama_surat' => 'Surat Keterangan Domisili',
                'deskripsi' => 'Surat keterangan domisili resmi untuk keperluan administratif seperti sekolah, rekening bank, BPJS.',
                'syarat_ketentuan' => 'Fotokopi KTP, Fotokopi KK, Surat Pengantar RT/RW, Bukti tempat tinggal',
            ],
            [
                'kode_surat' => 'SKPH',
                'nama_surat' => 'Surat Keterangan Penghasilan',
                'deskripsi' => 'Surat keterangan penghasilan untuk keperluan beasiswa, bantuan sosial, atau perbankan.',
                'syarat_ketentuan' => 'Fotokopi KTP, Fotokopi KK, Surat Pengantar RT/RW, Keterangan pekerjaan, Mengisi formulir permohonan',
            ],
            [
                'kode_surat' => 'SKH',
                'nama_surat' => 'Surat Keterangan Kehilangan Dokumen',
                'deskripsi' => 'Surat pernyataan kehilangan dokumen penting untuk dasar laporan polisi atau pengurusan ulang.',
                'syarat_ketentuan' => 'Fotokopi KTP, Surat Pengantar RT/RW, Kronologi kehilangan, Jenis dokumen hilang, Mengisi formulir permohonan',
            ],
            [
                'kode_surat' => 'SKBN',
                'nama_surat' => 'Surat Keterangan Belum Nikah',
                'deskripsi' => 'Surat pernyataan belum menikah, digunakan untuk CPNS, beasiswa, atau persyaratan administratif lain.',
                'syarat_ketentuan' => 'Fotokopi KTP, Fotokopi KK, Surat Pengantar RT/RW, Pernyataan belum menikah, Mengisi formulir permohonan',
            ],
            [
                'kode_surat' => 'SKN',
                'nama_surat' => 'Surat Keterangan Nikah',
                'deskripsi' => 'Surat yang menyatakan pasangan telah menikah secara agama tapi belum tercatat di instansi pemerintah.',
                'syarat_ketentuan' => 'Fotokopi KTP suami & istri, Fotokopi KK, Surat nikah dari tokoh agama, Surat Pengantar RT/RW, Mengisi formulir permohonan',
            ],
            [
                'kode_surat' => 'SKL',
                'nama_surat' => 'Surat Keterangan Kelahiran',
                'deskripsi' => 'Surat keterangan kelahiran bayi sebagai syarat pembuatan akta kelahiran dan dokumen kependudukan lainnya.',
                'syarat_ketentuan' => 'Fotokopi KTP orang tua, Fotokopi KK, Surat dari bidan/rumah sakit, Surat Pengantar RT/RW, Mengisi formulir permohonan',
            ],
            [
                'kode_surat' => 'SKBR',
                'nama_surat' => 'Surat Keterangan Belum Memiliki Rumah',
                'deskripsi' => 'Surat pernyataan belum memiliki rumah untuk program bantuan rumah atau subsidi.',
                'syarat_ketentuan' => 'Fotokopi KTP, Fotokopi KK, Surat Pengantar RT/RW, Pernyataan belum memiliki rumah, Mengisi formulir permohonan',
            ],
            [
                'kode_surat' => 'SKBBM',
                'nama_surat' => 'Surat Rekomendasi Pembelian BBM Bersubsidi',
                'deskripsi' => 'Surat rekomendasi dari desa untuk pembelian bahan bakar bersubsidi untuk usaha atau pertanian.',
                'syarat_ketentuan' => 'Fotokopi KTP, Fotokopi KK, Surat Pengantar RT/RW, Data kendaraan/alat usaha, Tujuan pembelian, Mengisi formulir permohonan',
            ],
        ];

        foreach ($surats as $item) {
            $slug = 'surat-' . Str::of($item['nama_surat'])->lower()->replaceFirst('surat ', '')->slug('-');

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
