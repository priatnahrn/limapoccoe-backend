<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Surat Keterangan Tidak Mampu</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            line-height: 1.5;
            margin: 30px;
            position: relative;
            max-width: 210mm; /* aman untuk A4–F4 */
        }

        .center { text-align: center; }
        .bold { font-weight: bold; }
        .mt-3 { margin-top: 1rem; }
        .mt-4 { margin-top: 2rem; }
        .text-right { text-align: right; }
        .indent { text-indent: 2em; }
        table { width: 100%; page-break-inside: avoid; }
        td { vertical-align: top; }
    </style>
</head>
<body>

    <table>
        <tr>
            <td style="width: 90px;">
                <img src="{{ $isPreview ? asset('logo-limapoccoe.png') : public_path('logo-limapoccoe.png') }}" alt="Logo" style="height: 85px;">
            </td>
            <td class="center">
                <div class="bold">PEMERINTAH DESA LIMAPOCCOE</div>
                <div class="bold">KECAMATAN CENRANA</div>
                <div class="bold">KABUPATEN MAROS</div>
                <div>Alamat: Jl Poros Maros-Bone Km 36 Kode Pos 90562</div>
                <div>Email: desalimapoccoe07@gmail.com</div>
            </td>
        </tr>
    </table>

    <hr>

    <div class="center">
        <h3><u>SURAT KETERANGAN TIDAK MAMPU</u></h3>
        <div>Nomor: {{ $ajuan->nomor_surat_tersimpan ?? '___/SKTM/___/__/____' }}</div>
    </div>

    <p class="mt-3">Yang bertanda tangan di bawah ini:</p>
    <table>
        <tr><td style="width: 150px;">Nama</td><td>: {{ $ajuan->tandaTangan->user->name ?? 'H ANDI ABU BAKRI' }}</td></tr>
        <tr><td>Jabatan</td><td>: Kepala Desa Limapoccoe</td></tr>
    </table>

    <p class="mt-3">Menerangkan bahwa:</p>
    <table>
        <tr><td style="width: 150px;">Nama</td><td>: {{ $user->name ?? $data['nama'] ?? '-' }}</td></tr>
        <tr><td>NIK</td><td>: {{ $user->nik ?? $data['nik'] ?? '-' }}</td></tr>
        <tr>
            <td>Tempat/Tanggal Lahir</td>
            <td>: {{ optional($profile)->tempat_lahir ?? $data['tempat_lahir'] ?? '-' }},
                {{ \Carbon\Carbon::parse(optional($profile)->tanggal_lahir ?? $data['tanggal_lahir'] ?? now())->format('d-m-Y') }}
            </td>
        </tr>
        <tr><td>Jenis Kelamin</td><td>: {{ optional($profile)->jenis_kelamin ?? $data['jenis_kelamin'] ?? '-' }}</td></tr>
        <tr><td>Pekerjaan</td><td>: {{ optional($profile)->pekerjaan ?? $data['pekerjaan'] ?? '-' }}</td></tr>
        <tr><td>Alamat</td><td>: {{ optional($profile)->alamat ?? $data['alamat'] ?? '-' }}</td></tr>
    </table>

    <p class="mt-3">Anak dari Pasangan:</p>
    <table>
        <tr><td style="width: 150px;">Nama Ayah</td><td>: {{ $data['nama_ayah'] ?? '-' }}</td></tr>
        <tr><td>Pekerjaan Ayah</td><td>: {{ $data['pekerjaan_ayah'] ?? '-' }}</td></tr>
        <tr><td>Nama Ibu</td><td>: {{ $data['nama_ibu'] ?? '-' }}</td></tr>
        <tr><td>Pekerjaan Ibu</td><td>: {{ $data['pekerjaan_ibu'] ?? '-' }}</td></tr>
        <tr><td>Jumlah Tanggungan</td><td>: {{ $data['jumlah_tanggungan'] ?? '-' }}</td></tr>
    </table>

    <p class="mt-3 indent">
        Benar penduduk di atas adalah penduduk Dusun {{ optional($profile)->dusun ?? $data['dusun'] ?? '-' }} Desa Limapoccoe tergolong dari keluarga yang kurang mampu.
    </p>

    <p class="indent">
        Demikian surat keterangan ini kami buat dengan sebenarnya untuk digunakan seperlunya.
    </p>

   <div style="margin-top: 3rem; display: flex; justify-content: space-between; align-items: flex-start; height: 160px;">
        
        {{-- Kiri: QR Code --}}
        @php
            $showQrFromFile = !$isPreview && $ajuan->status === 'approved' && isset($qrCodePath) && file_exists($qrCodePath);
        @endphp

        <div style="width: 80px; display: flex; flex-direction: column; justify-content: flex-end;">
            @if($isPreview && isset($qrCodeSvg))
                <div style="width: 100%; height: 80px; margin-top: auto;">
                    {!! $qrCodeSvg !!}
                </div>
            @elseif($showQrFromFile)
                <img src="file://{{ $qrCodePath }}" style="width: 100%; height: 80px; margin-top: auto;" alt="QR Code">
            @endif
        </div>

        {{-- Kanan: Tanda Tangan --}}
        <div style="text-align: center; display: flex; flex-direction: column; justify-content: flex-end; height: 160px;">
            <div>
                <div>Limapoccoe, {{ \Carbon\Carbon::parse($data['tanggal_surat'] ?? now())->translatedFormat('d F Y') }}</div>
                <div class="bold mt-1">KEPALA DESA LIMAPOCCOE</div>

                <div style="margin-top: 10px;">
                    @php
                        $ttdPath = storage_path('app/private/tanda-tangan-digital.png');
                        $ttdBase64 = file_exists($ttdPath) ? base64_encode(file_get_contents($ttdPath)) : null;
                    @endphp

                    @if ($ajuan->status === 'approved' && $ttdBase64)
                        <img src="data:image/png;base64,{{ $ttdBase64 }}" style="height: 100px;" alt="Tanda Tangan"><br>
                        <strong>{{ $ajuan->tandaTangan->user->name ?? 'H ANDI ABU BAKRI' }}</strong>
                    @else
                        <div style="height: 100px;"></div>
                        <strong style="color: grey;">Belum ditandatangani</strong>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="text-right mt-3">
        <hr>
        <p><em>Catatan:</em> Surat ini berlaku selama 1 bulan sejak tanggal terbit.</p>
    </div>

</body>
</html>
