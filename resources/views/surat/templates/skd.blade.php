<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Surat Keterangan Domisili</title>
    <style>
        /* -------- Halaman & Body -------- */
        @page { size: A4 portrait; margin: 12mm; }
        body {
            margin: 0; padding: 0;
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt; line-height: 1.4;
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

        /* -------- Kolom TTD mepet kanan -------- */
        .sign-row {
            display: flex;
            margin-top: 1.5rem;
        }
        /* Kolom TTD didorong ke kanan dan diberi lebar tetap */
        .sig-wrap {
            margin-left: auto;         /* kunci: nempel kanan */
            width: 300px;              /* lebar kolom */
            text-align: center;
        }
        .sig-title { margin-bottom: 6px; }
        .sig-box {
            position: relative;
            width: 270px;              /* kunci sesuai gambar */
            height: 180px;
            margin: 10px auto 0;
            line-height: 0;
        }
        .sig-img {
            position: absolute; inset: 0;
            width: 100%; height: 100%;
            object-fit: contain; display: block;
        }
        .sig-date {
            position: absolute; left: 0; right: 0; top: 50%;
            transform: translateY(-50%);
            text-align: center; font-size: 12px; font-weight: bold; color: #000;
            opacity: .85; /* mix-blend-mode:multiply; jika engine mendukung */
        }
        .sig-name { margin-top: 6px; font-weight: bold; }

        /* -------- QR & Catatan: fixed saat print, normal saat screen -------- */
        /* default untuk screen: tidak fixed agar tidak menutupi konten */
        .qr-fixed, .footer-fixed { position: static; }
        .qr-fixed { width: 60px; height: 60px; margin-top: 24px; }
        .qr-fixed img, .qr-fixed svg { width: 100%; height: 100%; object-fit: contain; display: block; }
        .footer-fixed { font-size: 10px; text-align: right; margin-top: 8px; }

        @media print {
          .qr-fixed {
            position: fixed;
            left: 12mm;                 /* sejajar margin kiri */
            bottom: 12mm;               /* nempel bawah */
            width: 60px; height: 60px; z-index: 5;
          }
          .footer-fixed {
            position: fixed;
            right: 12mm;                /* sejajar margin kanan */
            bottom: 12mm;               /* nempel bawah */
            font-size: 10px; text-align: right; z-index: 4;
          }
        }
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
        Benar nama tersebut di atas adalah penduduk {{ $data['alamat'] ?? optional($profile)->alamat ?? '-' }}
        yang berdomisili di Dusun {{ $data['dusun'] ?? optional($profile)->dusun ?? '-' }},
        Desa Limapoccoe, Kecamatan Cenrana, Kabupaten Maros.
    </p>

    <p class="indent">
        Demikian surat keterangan ini kami buat dengan sebenarnya untuk digunakan seperlunya.
    </p>

    {{-- QR (screen: ikut flow; print/PDF: fixed kiri bawah) --}}
    @php
        $showQrFromFile = !$isPreview && $ajuan->status === 'approved' && isset($qrCodePath) && file_exists($qrCodePath);
    @endphp
    <div class="qr-fixed">
        @if($isPreview && isset($qrCodeSvg))
            {!! $qrCodeSvg !!}
        @elseif($showQrFromFile)
            <img src="file://{{ $qrCodePath }}" alt="QR Code">
        @endif
    </div>

    {{-- Tanda Tangan (nempel kanan) --}}
    @php
        $ttdPath    = storage_path('app/private/tanda-tangan-digital.png');
        $ttdBase64  = file_exists($ttdPath) ? base64_encode(file_get_contents($ttdPath)) : null;
        $tanggalTtd = \Carbon\Carbon::parse($ajuan->updated_at ?? ($data['tanggal_surat'] ?? now()))->format('d/m/Y');
    @endphp

    <div class="sign-row">
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

    {{-- Catatan (screen: ikut flow; print/PDF: fixed kanan bawah) --}}
    @if(!$isPreview || $ajuan->status === 'approved')
        <div class="footer-fixed">
            <em>Catatan:</em> Surat ini berlaku selama 1 bulan sejak tanggal terbit.
        </div>
    @endif

</body>
</html>
