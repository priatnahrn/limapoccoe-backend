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
                'deskripsi' => 'Surat resmi dari pemerintah desa yang menyatakan seseorang berasal dari keluarga kurang mampu untuk keperluan bantuan sosial, beasiswa, layanan kesehatan, dan administrasi lainnya.',
                'syarat_ketentuan' => 'Fotokopi KK, Fotokopi KTP Pemohon/Orang Tua, Surat Pengantar RT/RW, Tujuan pembuatan SKTM, Mengisi formulir permohonan',
            ],
            [
                'kode_surat' => 'SKU',
                'nama_surat' => 'Surat Keterangan Usaha',
                'deskripsi' => 'Surat resmi yang menerangkan bahwa seseorang memiliki dan menjalankan usaha di wilayah desa, dibutuhkan untuk kredit, izin, pajak, dan bantuan UMKM.',
                'syarat_ketentuan' => 'Fotokopi KTP, Fotokopi KK, Surat Pengantar RT/RW, Bukti keberadaan usaha (foto tempat usaha), Mengisi formulir permohonan',
            ],
            [
                'kode_surat' => 'SKCK',
                'nama_surat' => 'Surat Pengantar SKCK',
                'deskripsi' => 'Surat pengantar dari pemerintah desa sebagai syarat administrasi dalam pengurusan SKCK di kepolisian.',
                'syarat_ketentuan' => 'Fotokopi KTP, Fotokopi KK, Surat Pengantar RT/RW, Pas foto terbaru sesuai ketentuan',
            ],
            [
                'kode_surat' => 'SKD',
                'nama_surat' => 'Surat Keterangan Domisili',
                'deskripsi' => 'Surat keterangan yang menyatakan bahwa seseorang berdomisili di wilayah desa, digunakan untuk berbagai keperluan administratif seperti BPJS, sekolah, bank.',
                'syarat_ketentuan' => 'Fotokopi KTP, Fotokopi KK, Surat Pengantar RT/RW, Bukti tempat tinggal (sewa/kontrak rumah)',
            ],
            [
                'kode_surat' => 'SKPH',
                'nama_surat' => 'Surat Keterangan Penghasilan',
                'deskripsi' => 'Surat yang menyatakan kisaran penghasilan pemohon berdasarkan keterangan lingkungan, digunakan untuk beasiswa, bantuan sosial, atau bank.',
                'syarat_ketentuan' => 'Fotokopi KTP, Fotokopi KK, Surat Pengantar RT/RW, Keterangan jenis pekerjaan/usaha, Mengisi formulir permohonan',
            ],
            [
                'kode_surat' => 'SKH',
                'nama_surat' => 'Surat Keterangan Kehilangan Dokumen',
                'deskripsi' => 'Surat resmi dari desa yang menyatakan seseorang kehilangan dokumen penting, digunakan sebagai dasar laporan polisi dan pengurusan ulang dokumen.',
                'syarat_ketentuan' => 'Fotokopi KTP, Surat Pengantar RT/RW, Uraian/kronologi kehilangan, Jenis dokumen yang hilang, Mengisi formulir permohonan',
            ],
            [
                'kode_surat' => 'SKBN',
                'nama_surat' => 'Surat Keterangan Belum Nikah',
                'deskripsi' => 'Surat yang menyatakan bahwa seseorang belum pernah menikah secara hukum atau agama, dibutuhkan untuk CPNS, beasiswa, dan keperluan administrasi lainnya.',
                'syarat_ketentuan' => 'Fotokopi KTP, Fotokopi KK, Surat Pengantar RT/RW, Pernyataan belum menikah dari desa, Mengisi formulir permohonan',
            ],
            [
                'kode_surat' => 'SKN',
                'nama_surat' => 'Surat Keterangan Nikah',
                'deskripsi' => 'Surat dari desa yang menyatakan pasangan telah menikah secara agama namun belum tercatat resmi di instansi pemerintah, digunakan untuk administrasi keluarga.',
                'syarat_ketentuan' => 'Fotokopi KTP suami dan istri, Fotokopi KK, Surat nikah dari tokoh agama, Surat Pengantar RT/RW, Mengisi formulir permohonan',
            ],
            [
                'kode_surat' => 'SKL',
                'nama_surat' => 'Surat Keterangan Kelahiran',
                'deskripsi' => 'Surat resmi dari desa yang menyatakan kelahiran seorang bayi, menjadi syarat untuk membuat akta kelahiran dan dokumen kependudukan lainnya.',
                'syarat_ketentuan' => 'Fotokopi KTP orang tua, Fotokopi KK, Surat keterangan dari bidan/rumah sakit, Surat Pengantar RT/RW, Mengisi formulir permohonan',
            ],
            [
                'kode_surat' => 'SKBR',
                'nama_surat' => 'Surat Keterangan Belum Memiliki Rumah',
                'deskripsi' => 'Surat resmi dari desa yang menyatakan seseorang atau keluarganya belum memiliki rumah pribadi, digunakan untuk program bantuan/subsidi rumah.',
                'syarat_ketentuan' => 'Fotokopi KTP, Fotokopi KK, Surat Pengantar RT/RW, Pernyataan belum memiliki rumah, Mengisi formulir permohonan',
            ],
            [
                'kode_surat' => 'SKBBM',
                'nama_surat' => 'Surat Rekomendasi Pembelian BBM Bersubsidi',
                'deskripsi' => 'Surat rekomendasi resmi dari desa agar seseorang dapat membeli BBM bersubsidi untuk keperluan usaha seperti pertanian, nelayan, atau transportasi.',
                'syarat_ketentuan' => 'Fotokopi KTP, Fotokopi KK, Surat Pengantar RT/RW, Data kendaraan atau alat usaha, Tujuan pembelian BBM subsidi, Mengisi formulir permohonan',
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
