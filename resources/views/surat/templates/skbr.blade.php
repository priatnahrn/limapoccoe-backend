<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Surat Keterangan Tidak Memiliki PBB</title>
    <style>
        /* ---- Page ---- */
        @page { size: A4 portrait; margin: 12mm; }
        html, body { height: 100%; }
        body {
            margin: 0; padding: 0;
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt; line-height: 1.4;
        }
        .page { min-height: 273mm; display: flex; flex-direction: column; }

        /* ---- Utils ---- */
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .mt-3 { margin-top: 1rem; }
        .mt-2 { margin-top: 0.5rem; }
        .indent { text-indent: 2em; }
        .logo { height: 80px; }
        hr { margin: 6px 0; border: 0; border-top: 1px solid #000; }

        table { width: 100%; border-collapse: collapse; page-break-inside: avoid; }
        td { vertical-align: top; padding: 0; }
        table tr td:first-child { width: 150px; }
        table tr td:nth-child(2) { padding-left: 20px; }

        /* ---- Signature (kanan) ---- */
        .sign-row { display: flex; margin-top: 1.2rem; break-inside: avoid; page-break-inside: avoid; }
        .sig-wrap { margin-left: auto; width: 300px; text-align: center; }
        .sig-title { margin-bottom: 6px; }
        .sig-box { position: relative; width: 270px; height: 175px; margin: 8px auto 0; line-height: 0; }
        .sig-img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: contain; display: block; }
        .sig-date { position: absolute; left:0; right:0; top:50%; transform:translateY(-50%); text-align:center; font-size:12px; font-weight:bold; opacity:.85; }
        .sig-name { margin-top: 6px; font-weight: bold; }

        /* ---- Footer (ikut alur, nempel bawah) ---- */
        .footer-row {
            margin-top: auto;
            display: flex; justify-content: space-between; align-items: flex-end; gap: 12px;
        }
        .footer-qr { width: 20mm; height: 20mm; flex: 0 0 auto; }
        .footer-qr img, .footer-qr svg { width: 100%; height: 100%; object-fit: contain; display: block; }
        .footer-note { font-size: 10px; text-align: right; flex: 1 1 auto; }

        /* ---- Kompres saat cetak (pastikan muat 1 halaman) ---- */
        @media print {
            body { font-size: 10.5pt; line-height: 1.35; }
            .logo { height: 70px; }
            .mt-3 { margin-top: .8rem; }
            .mt-2 { margin-top: .4rem; }
            hr { margin: 4px 0; }
            table tr td:first-child { width: 140px; }
            table tr td:nth-child(2) { padding-left: 16px; }
            .sig-box { height: 165px; }
            .sig-date { font-size: 11px; }
        }
    </style>
</head>
<body>

@php
    $nomorSurat = $ajuan->nomor_surat_tersimpan ?? '___/SKTPBB/LPC/CRN/__/____';
@endphp

<div class="page">
    {{-- Kop Surat --}}
    <table>
        <tr>
            <td style="width:80px;">
                <img class="logo" src="{{ $isPreview ? asset('logo-limapoccoe.png') : public_path('logo-limapoccoe.png') }}" alt="Logo">
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
        <h4 style="margin-bottom:0;"><u>SURAT KETERANGAN TIDAK MEMILIKI PAJAK BUMI DAN BANGUNAN</u></h4>
        <div>Nomor: {{ $nomorSurat }}</div>
    </div>

    <p class="mt-3 indent">Saya yang bertanda tangan di bawah ini menerangkan bahwa:</p>

    <div class="indent">
        <table style="margin-left:20px;">
            <tr><td>Nama</td><td>: {{ $data['nama'] ?? $user->name ?? '-' }}</td></tr>
            <tr>
                <td>Tempat/Tgl Lahir</td>
                <td>:
                    {{ $data['tempat_lahir'] ?? optional($profile)->tempat_lahir ?? '-' }},
                    {{ \Carbon\Carbon::parse($data['tanggal_lahir'] ?? optional($profile)->tanggal_lahir ?? now())->format('d-m-Y') }}
                </td>
            </tr>
            <tr><td>Jenis Kelamin</td><td>: {{ $data['jenis_kelamin'] ?? optional($profile)->jenis_kelamin ?? '-' }}</td></tr>
            <tr><td>Agama</td><td>: {{ $data['agama'] ?? optional($profile)->agama ?? '-' }}</td></tr>
            <tr><td>Pekerjaan</td><td>: {{ $data['pekerjaan'] ?? optional($profile)->pekerjaan ?? '-' }}</td></tr>
            <tr><td>NIK</td><td>: {{ $data['nik'] ?? $user->nik ?? '-' }}</td></tr>
            <tr><td>Alamat</td><td>: {{ $data['alamat'] ?? optional($profile)->alamat ?? '-' }}</td></tr>
        </table>
    </div>

    <p class="mt-2 indent">
        Adalah benar orang tua dari <strong>{{ $data['nama_orang_tua'] ?? '-' }}</strong> menyatakan bahwa tidak memiliki Pajak Bumi dan Bangunan (PBB) di Desa Limapoccoe,
        dan selama ini masih menumpang di tanah milik orang tua.
    </p>

    <p class="indent">
        Demikian surat keterangan ini dibuat untuk melengkapi berkas {{ $data['keperluan'] ?? '-' }}.
    </p>

    {{-- Tanda Tangan (kanan) --}}
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
                <div class="sig-name" style="color:grey;">Belum ditandatangani</div>
            @endif
        </div>
    </div>

    {{-- Footer (ikut alur, nempel bawah) --}}
    @php
        $showQrFromFile = !$isPreview && $ajuan->status === 'approved' && isset($qrCodePath) && file_exists($qrCodePath);
    @endphp
    <div class="footer-row">
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
</div>
</body>
</html>
