<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Surat Keterangan Tidak Mampu</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            line-height: 1.15;
            margin: 30px;
            max-width: 210mm; /* A4-safe for F4 */
        }

        .center { text-align: center; }
        .bold { font-weight: bold; }
        .mt-3 { margin-top: 1rem; }
        .mt-4 { margin-top: 2rem; }
        .text-right { text-align: right; }
        .indent { text-indent: 2em; }

        table {
            width: 100%;
            page-break-inside: avoid;
            border-collapse: collapse;
        }

         td {
        vertical-align: top;
        padding: 0;
    }

        table tr td:first-child {
            width: 150px;
        }

        table tr td:nth-child(2) {
            padding-left: 10px; /* 👉 indentasi nilai */
        }

        .footer-note {
        position: absolute;
        bottom: 30px;
        right: 30px;
        font-size: 12px;
        text-align: right;
        width: calc(100% - 60px);
    }
    </style>
</head>
<body>

    {{-- Kop Surat --}}
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

    {{-- QR Code & Tanda Tangan --}}
    @php
        $showQrFromFile = !$isPreview && $ajuan->status === 'approved' && isset($qrCodePath) && file_exists($qrCodePath);
    @endphp

    <table style="width: 100%; margin-top: 2rem;">
        <tr>
            {{-- Kolom Kiri: QR --}}
            <td style="width: 60mm; vertical-align: bottom;">
                @if($isPreview && isset($qrCodeSvg))
                    <div style="width: 80px; height: 80px;">
                        {!! $qrCodeSvg !!}
                    </div>
                @elseif($showQrFromFile)
                    <img src="file://{{ $qrCodePath }}" style="width: 80px; height: auto;" alt="QR Code">
                @endif
            </td>

            {{-- Kolom Kanan: Tanda Tangan --}}
            <td style="text-align: center; padding-left: 50px;">
                <div>Limapoccoe, {{ \Carbon\Carbon::parse($data['tanggal_surat'] ?? now())->translatedFormat('d F Y') }}</div>
                <div class="bold">KEPALA DESA LIMAPOCCOE</div>
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
            </td>
        </tr>
    </table>


    {{-- Catatan --}}
    @if(!$isPreview || $ajuan->status === 'approved')
        <div class="footer-note">
            <hr>
            <p><em>Catatan:</em> Surat ini berlaku selama 1 bulan sejak tanggal terbit.</p>
        </div>
    @endif


</body>
</html>
