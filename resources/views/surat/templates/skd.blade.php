<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Surat Keterangan Domisili</title>
    <style>
        /* -------- Halaman & Body -------- */
        @page {
            size: A4 portrait;
            margin: 12mm;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.4;
        }

        /* -------- Util -------- */
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .mt-3 { margin-top: 1rem; }
        .mt-2 { margin-top: 0.5rem; }
        .indent { text-indent: 2em; }

        table { width: 100%; page-break-inside: avoid; border-collapse: collapse; }
        td { vertical-align: top; padding: 0; }
        table tr td:first-child { width: 150px; }
        table tr td:nth-child(2) { padding-left: 20px; }

        hr { margin: 6px 0; border: 0; border-top: 1px solid #000; }

        .footer-note {
            margin-top: 1rem;
            font-size: 10px;
            text-align: right;
            padding-top: 3px;
        }

        /* -------- Area QR & TTD (ANTI GESER) -------- */
        .sign-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-top: 1.5rem;
        }
        .qr-box {
            position: relative;
            width: 60px;      /* kunci lebar */
            height: 60px;     /* kunci tinggi */
        }
        .qr-box img, .qr-box svg { 
            position: absolute; 
            inset: 0; 
            width: 100%; 
            height: 100%; 
            object-fit: contain;
        }

        /* Kontainer tanda tangan berukuran pasti */
        .sig-wrap { text-align: center; flex: 1; }
        .sig-title { margin-bottom: 6px; }
        .sig-box {
            position: relative;
            width: 270px;     /* KUNCI LEBAR supaya engine PDF tidak reflow */
            height: 180px;    /* KUNCI TINGGI sesuai tinggi gambar */
            margin: 10px auto 0;
            line-height: 0;   /* hilangkan line-box */
        }
        .sig-img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;  /* jaga rasio */
            display: block;
        }
        .sig-date {
            position: absolute;
            left: 0; right: 0; top: 50%;
            transform: translateY(-50%); /* center vertikal */
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            color: #000;
            opacity: .85;               /* fallback jika blend-mode tidak didukung */
            /* mix-blend-mode: multiply; */ /* aktifkan jika engine kamu support */
            /* background: rgba(255,255,255,.3); padding: 1px 4px; */ /* opsi bila perlu kontras */
        }
        .sig-name { margin-top: 6px; font-weight: bold; }
    </style>
</head>
<body>

    {{-- Kop Surat --}}
    <table>
        <tr>
            <td style="width: 80px;">
                <img src="{{ $isPreview ? asset('logo-limapoccoe.png') : public_path('logo-limapoccoe.png') }}" alt="Logo" style="height: 80px;">
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
        <h4 style="margin-bottom: 0;"><u>SURAT KETERANGAN DOMISILI</u></h4>
        <div>Nomor: {{ $ajuan->nomor_surat_tersimpan ?? '___/SKD/LPC/CRN/__/____' }}</div>
    </div>

    <p class="mt-3">Yang bertanda tangan di bawah ini:</p>
    <div class="indent">
        <table style="margin-left: 20px;">
            <tr><td>Nama</td><td>: {{ $ajuan->tandaTangan->user->name ?? 'H ANDI ABU BAKRI' }}</td></tr>
            <tr><td>Jabatan</td><td>: Kepala Desa Limapoccoe</td></tr>
        </table>
    </div>

    <p class="mt-2">Menerangkan bahwa:</p>
    <div class="indent">
        <table style="margin-left: 20px;">
            <tr><td>Nama</td><td>: {{ $data['nama'] ?? $user->name ?? '-' }}</td></tr>
            <tr><td>NIK</td><td>: {{ $data['nik'] ?? $user->nik ?? '-' }}</td></tr>
            <tr>
                <td>Tempat/Tanggal Lahir</td>
                <td>:
                    {{ $data['tempat_lahir'] ?? optional($profile)->tempat_lahir ?? '-' }},
                    {{ \Carbon\Carbon::parse($data['tanggal_lahir'] ?? optional($profile)->tanggal_lahir ?? now())->format('d-m-Y') }}
                </td>
            </tr>
            <tr><td>Jenis Kelamin</td><td>: {{ $data['jenis_kelamin'] ?? optional($profile)->jenis_kelamin ?? '-' }}</td></tr>
            <tr><td>Pekerjaan</td><td>: {{ $data['pekerjaan'] ?? optional($profile)->pekerjaan ?? '-' }}</td></tr>
            <tr><td>Alamat</td><td>: {{ $data['alamat'] ?? optional($profile)->alamat ?? '-' }}</td></tr>
        </table>
    </div>

    <p class="mt-2 indent">
        Benar nama tersebut di atas adalah penduduk {{ $data['alamat'] ?? optional($profile)->alamat ?? '-' }} yang berdomisili di Dusun {{ $data['dusun'] ?? optional($profile)->dusun ?? '-' }}, Desa Limapoccoe, Kecamatan Cenrana, Kabupaten Maros.
    </p>

    <p class="indent">
        Demikian surat keterangan ini kami buat dengan sebenarnya untuk digunakan seperlunya.
    </p>

    {{-- QR Code & Tanda Tangan (pakai FLEX, bukan tabel) --}}
    @php
        $showQrFromFile = !$isPreview && $ajuan->status === 'approved' && isset($qrCodePath) && file_exists($qrCodePath);

        $ttdPath    = storage_path('app/private/tanda-tangan-digital.png');
        $ttdBase64  = file_exists($ttdPath) ? base64_encode(file_get_contents($ttdPath)) : null;
        $tanggalTtd = \Carbon\Carbon::parse($ajuan->updated_at ?? ($data['tanggal_surat'] ?? now()))->format('d/m/Y');
    @endphp

    <div class="sign-row">
        <!-- QR -->
        <div class="qr-box">
            @if($isPreview && isset($qrCodeSvg))
                <div>{!! $qrCodeSvg !!}</div>
            @elseif($showQrFromFile)
                <img src="file://{{ $qrCodePath }}" alt="QR Code">
            @endif
        </div>

        <!-- Tanda Tangan -->
        <div class="sig-wrap">
            <div class="sig-title">
                Limapoccoe, {{ \Carbon\Carbon::parse($data['tanggal_surat'] ?? now())->translatedFormat('d F Y') }}<br>
                <span class="bold">KEPALA DESA LIMAPOCCOE</span>
            </div>

            @if ($ajuan->status === 'approved' && $ttdBase64)
                <div class="sig-box">
                    <img class="sig-img" src="data:image/png;base64,{{ $ttdBase64 }}" alt="Tanda Tangan">
                    <div class="sig-date">{{ $tanggalTtd }}</div>
                </div>
                <div class="sig-name">{{ $ajuan->tandaTangan->user->name ?? 'H ANDI ABU BAKRI' }}</div>
            @else
                <div class="sig-box"></div>
                <div class="sig-name" style="color: grey;">Belum ditandatangani</div>
            @endif
        </div>
    </div>

    {{-- Catatan --}}
    @if(!$isPreview || $ajuan->status === 'approved')
        <div class="footer-note">
            <p><em>Catatan:</em> Surat ini berlaku selama 1 bulan sejak tanggal terbit.</p>
        </div>
    @endif

</body>
</html>
