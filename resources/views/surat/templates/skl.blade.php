<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Surat Keterangan Kelahiran</title>
    <style>
        /* -------- Halaman & Body -------- */
        @page { size: A4 portrait; margin: 12mm; }
        body {
            margin: 0; padding: 0;
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt; line-height: 1.4;
        }
        /* ruang aman agar konten tidak ketimpa footer fixed saat preview */
        .content { padding-bottom: 36mm; }

        /* -------- Util -------- */
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .mt-2 { margin-top: 0.5rem; }
        .indent { text-indent: 2em; }
        table { width: 100%; page-break-inside: avoid; border-collapse: collapse; }
        td { vertical-align: top; padding: 0; }
        table tr td:first-child { width: 220px; }
        table tr td:nth-child(2) { padding-left: 20px; }
        hr { margin: 6px 0; border: 0; border-top: 1px solid #000; }

        /* -------- Kolom TTD mepet kanan -------- */
        .sign-row { display: flex; margin-top: 1.5rem; }
        .sig-wrap {
            margin-left: auto;         /* nempel kanan */
            width: 300px;              /* lebar kolom TTD */
            text-align: center;
        }
        .sig-title { margin-bottom: 6px; }
        .sig-box {
            position: relative;
            width: 270px; height: 180px; /* kunci ukuran gambar */
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
            opacity: .85; /* mix-blend-mode: multiply;  jika engine mendukung */
        }
        .sig-name { margin-top: 6px; font-weight: bold; }

        /* -------- FOOTER: QR kiri & catatan kanan dalam satu baris -------- */
        .page-footer {
            position: fixed;
            left: 12mm; right: 12mm; bottom: 12mm; /* sejajar margin @page */
            display: flex; justify-content: space-between; align-items: flex-end;
            z-index: 10;
        }
        .footer-qr { width: 60px; height: 60px; flex: 0 0 auto; }
        .footer-qr img, .footer-qr svg { width: 100%; height: 100%; object-fit: contain; display: block; }
        .footer-note { font-size: 10px; text-align: right; flex: 1 1 auto; margin-left: 12px; }
    </style>
</head>
<body>

<div class="content">
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
        <h4 style="margin-bottom: 0;"><u>SURAT KETERANGAN KELAHIRAN</u></h4>
        <div>Nomor: {{ $ajuan->nomor_surat_tersimpan ?? '___/SKKL/LPC/CRN/__/____' }}</div>
    </div>

    <p class="mt-2">Yang bertanda tangan di bawah ini:</p>
    <table style="margin-left: 20px;">
        <tr><td>Nama</td><td>: {{ $ajuan->tandaTangan->user->name ?? 'H ANDI ABU BAKRI' }}</td></tr>
        <tr><td>Jabatan</td><td>: Kepala Desa Limapoccoe</td></tr>
    </table>

    <p class="mt-2">Menerangkan bahwa:</p>
    <table style="margin-left: 20px;">
        <tr><td>Nama</td><td>: {{ $data['nama_anak'] ?? '-' }}</td></tr>
        <tr>
            <td>Tempat, Tanggal Lahir</td>
            <td>: {{ $data['tempat_lahir'] ?? '-' }}, {{ \Carbon\Carbon::parse($data['tanggal_lahir'] ?? now())->format('d-m-Y') }}</td>
        </tr>
        <tr><td>Pukul</td><td>: {{ $data['pukul'] ?? '-' }}</td></tr>
        <tr><td>Jenis Kelamin</td><td>: {{ $data['jenis_kelamin'] ?? '-' }}</td></tr>
        <tr><td>Anak Ke</td><td>: {{ $data['anak_ke'] ?? '-' }}</td></tr>
        <tr><td>Panjang Bayi</td><td>: {{ $data['panjang_bayi'] ?? '-' }} CM</td></tr>
        <tr><td>Berat Bayi</td><td>: {{ $data['berat_bayi'] ?? '-' }} KG</td></tr>
    </table>

    <p class="mt-2"><strong>Nama Orang Tua:</strong></p>
    <table style="margin-left: 20px;">
        <tr><td>Ayah</td><td>: {{ $data['nama_ayah'] ?? '-' }}</td></tr>
        <tr><td>Pekerjaan</td><td>: {{ $data['pekerjaan_ayah'] ?? '-' }}</td></tr>
        <tr><td>Ibu</td><td>: {{ $data['nama_ibu'] ?? '-' }}</td></tr>
        <tr><td>Pekerjaan</td><td>: {{ $data['pekerjaan_ibu'] ?? '-' }}</td></tr>
        <tr><td>Alamat</td><td>: Dusun {{ $data['dusun'] ?? optional($profile)->dusun ?? '-' }}, {{ $data['alamat'] ?? optional($profile)->alamat ?? '-' }}</td></tr>
    </table>

    <p class="mt-2 indent">
        Surat keterangan ini dibuat atas dasar yang sebenarnya.
    </p>

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
</div> {{-- /content --}}

{{-- FOOTER: QR kiri + catatan kanan (fixed dan sejajar) --}}
@php
    $showQrFromFile = !$isPreview && $ajuan->status === 'approved' && isset($qrCodePath) && file_exists($qrCodePath);
@endphp
<div class="page-footer">
    <div class="footer-qr">
        @if($isPreview && isset($qrCodeSvg))
            {!! $qrCodeSvg !!}
        @elseif($showQrFromFile)
            <img src="file://{{ $qrCodePath }}" alt="QR Code">
        @endif
    </div>
    @if(!$isPreview || $ajuan->status === 'approved')
        <div class="footer-note">
            <em>Catatan:</em> Surat ini berlaku selama 1 bulan sejak tanggal terbit.
        </div>
    @endif
</div>

</body>
</html>
