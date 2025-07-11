<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Surat Keterangan Tidak Mampu</title>
    <style>
        @page {
        size: A4;
        margin: 20mm;
    }

       body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.5;
            margin: 0;
            padding: 0;
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
            padding-left: 25px; /* ✅ Indentasi diperjelas */
        }

        .footer-note {
            margin-top: 2rem;
            font-size: 12px;
            text-align: right;
            border-top: 1px solid #000;
            padding-top: 5px;
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
    <div class="indent">
        <table style="margin-left: 25px;">
            <tr><td>Nama</td><td>: {{ $ajuan->tandaTangan->user->name ?? 'H ANDI ABU BAKRI' }}</td></tr>
            <tr><td>Jabatan</td><td>: Kepala Desa Limapoccoe</td></tr>
        </table>
    </div>

    <p class="mt-3">Menerangkan bahwa:</p>
    <div class="indent">
        <table style="margin-left: 25px;">
            <tr><td>Nama</td><td>: {{ $user->name ?? $data['nama'] ?? '-' }}</td></tr>
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
    </div>

    <p class="mt-3">Anak dari Pasangan:</p>
    <div class="indent">
        <table style="margin-left: 25px;">
           
            <tr><td>Nama Ayah</td><td>: {{ $data['nama_ayah'] ?? '-' }}</td></tr>
            <tr ><td>Pekerjaan Ayah</td><td>: {{ $data['pekerjaan_ayah'] ?? '-' }}</td></tr>
            <tr ><td>Nama Ibu</td><td>: {{ $data['nama_ibu'] ?? '-' }}</td></tr>
            <tr ><td>Pekerjaan Ibu</td><td>: {{ $data['pekerjaan_ibu'] ?? '-' }}</td></tr>
            <tr ><td>Jumlah Tanggungan</td><td>: {{ $data['jumlah_tanggungan'] ?? '-' }}</td></tr>
        </table>
    </div>

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
        {{-- QR Code --}}
        <td style="width: 50%; vertical-align: top;">
            @if($isPreview && isset($qrCodeSvg))
                <div style="width: 30px; height: 30px;">
                    {!! $qrCodeSvg !!}
                </div>
            @elseif($showQrFromFile)
                <img src="file://{{ $qrCodePath }}" style="width: 50px; height: auto;" alt="QR Code">
            @endif
        </td>

        {{-- Tanda Tangan --}}
        <td style="width: 50%; text-align: center;">
            <div>Limapoccoe, {{ \Carbon\Carbon::parse($data['tanggal_surat'] ?? now())->translatedFormat('d F Y') }}</div>
            <div class="bold">KEPALA DESA LIMAPOCCOE</div>
            <div style="margin-top: 10px;">
                @php
                    $ttdPath = storage_path('app/private/tanda-tangan-digital.png');
                    $ttdBase64 = file_exists($ttdPath) ? base64_encode(file_get_contents($ttdPath)) : null;
                @endphp

                @if ($ajuan->status === 'approved' && $ttdBase64)
                    <img src="data:image/png;base64,{{ $ttdBase64 }}" style="height: 200px;" alt="Tanda Tangan"><br>
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
            <p><em>Catatan:</em> Surat ini berlaku selama 1 bulan sejak tanggal terbit.</p>
        </div>
    @endif


</body>
</html>
