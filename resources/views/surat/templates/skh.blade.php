<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Surat Keterangan Kehilangan</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        .center { text-align: center; }
        .bold { font-weight: bold; }
        .mt-3 { margin-top: 1rem; }
        .mt-2 { margin-top: 0.5rem; }
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
            padding-left: 20px;
        }

        .footer-note {
            margin-top: 1rem;
            font-size: 10px;
            text-align: right;
            padding-top: 3px;
        }
    </style>
</head>
<body>

@php
    $jenis = strtolower($data['jenis_dokumen'] ?? '');
    $kodeMap = [
        'ktp' => 'SKHK',
        'kk' => 'SKHKK',
        'akte' => 'SKHA',
    ];
    $kodeSurat = $kodeMap[$jenis] ?? 'SKH';
    $nomorSurat = $ajuan->nomor_surat_tersimpan ?? "___/{$kodeSurat}/LPC/CRN/__/____";
@endphp

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
    <h4 style="margin-bottom: 0;"><u>SURAT KETERANGAN HILANG {{ strtoupper($data['jenis_dokumen'] ?? 'DOKUMEN') }}</u></h4>
    <div>Nomor: {{ $nomorSurat }}</div>
</div>

<p class="mt-3">Yang bertanda tangan di bawah ini:</p>
<div class="indent">
    <table style="margin-left: 20px;">
        <tr><td>Nama</td><td>: {{ $ajuan->tandaTangan->user->name ?? 'H. ANDI ABU BAKRI' }}</td></tr>
        <tr><td>Jabatan</td><td>: Kepala Desa Limapoccoe</td></tr>
    </table>
</div>

<p class="mt-2">Menerangkan bahwa:</p>
<div class="indent">
    <table style="margin-left: 20px;">
        <tr><td>Nama</td><td>: {{ $user->name ?? $data['nama'] ?? '-' }}</td></tr>
        <tr>
            <td>Tempat/Tanggal Lahir</td>
            <td>: {{ optional($profile)->tempat_lahir ?? $data['tempat_lahir'] ?? '-' }},
                {{ \Carbon\Carbon::parse(optional($profile)->tanggal_lahir ?? $data['tanggal_lahir'] ?? now())->format('d-m-Y') }}
            </td>
        </tr>
        <tr><td>Jenis Kelamin</td><td>: {{ optional($profile)->jenis_kelamin ?? $data['jenis_kelamin'] ?? '-' }}</td></tr>
        <tr><td>Pekerjaan</td><td>: {{ optional($profile)->pekerjaan ?? $data['pekerjaan'] ?? '-' }}</td></tr>
        <tr><td>Alamat</td><td>: Dusun {{ optional($profile)->dusun ?? $data['dusun'] ?? '-' }}, {{ optional($profile)->alamat ?? $data['alamat'] ?? '-' }}</td></tr>
        <tr><td>No KK</td><td>: {{ $data['no_kk'] ?? '-' }}</td></tr>
        <tr><td>No KTP</td><td>: {{ $user->nik ?? $data['nik'] ?? '-' }}</td></tr>
    </table>
</div>

<p class="mt-2 indent">
    Adalah benar nama tersebut di atas adalah warga yang berdomisili di Dusun {{ optional($profile)->dusun ?? $data['dusun'] ?? '-' }}, Desa Limapoccoe, Kecamatan Cenrana, Kabupaten Maros, yang telah kehilangan dokumen <strong>{{ strtoupper($data['jenis_dokumen'] ?? '-') }}</strong> dengan nomor <strong>{{ $data['no_dokumen'] ?? '-' }}</strong>. Dokumen tersebut diperkirakan hilang/tercecer di <strong>{{ $data['perkiraan_lokasi_hilang'] ?? '-' }}</strong>.
</p>

<p class="indent">
    Demikian surat keterangan ini kami buat dengan sebenarnya untuk digunakan seperlunya.
</p>

 {{-- QR Code & Tanda Tangan --}}
    @php
        $showQrFromFile = !$isPreview && $ajuan->status === 'approved' && isset($qrCodePath) && file_exists($qrCodePath);
    @endphp

    <table style="width: 100%; margin-top: 1.5rem;">
        <tr>
            {{-- QR Code --}}
            <td style="width: 50%; vertical-align: top;">
                @if($isPreview && isset($qrCodeSvg))
                    <div style="width: 30px; height: 30px;">
                        {!! $qrCodeSvg !!}
                    </div>
                @elseif($showQrFromFile)
                    <img src="file://{{ $qrCodePath }}" style="width: 50px; height: auto; bottom: 10mm; left: 10mm; position: absolute;" alt="QR Code">
                @endif
            </td>

            {{-- Tanda Tangan --}}
            <td style="width: 50%; text-align: center;">
                <div>Limapoccoe, {{ \Carbon\Carbon::parse($data['tanggal_surat'] ?? now())->translatedFormat('d F Y') }}</div>
                <div class="bold">KEPALA DESA LIMAPOCCOE</div>

                {{-- siapkan source tanda tangan --}}
                @php
                    $ttdPath   = storage_path('app/private/tanda-tangan-digital.png');
                    $ttdBase64 = file_exists($ttdPath) ? base64_encode(file_get_contents($ttdPath)) : null;

                    // tanggal yang mau ditulis di atas tanda tangan (silakan sesuaikan sumbernya)
                    $tanggalTtd = \Carbon\Carbon::parse($ajuan->updated_at ?? now())->format('d/m/Y');
                @endphp

                <div style="margin-top: 10px; position: relative; display: inline-block;">
                    @if ($ajuan->status === 'approved' && $ttdBase64)
                        <!-- Gambar tanda tangan -->
                        <img src="data:image/png;base64,{{ $ttdBase64 }}" style="height: 180px;" alt="Tanda Tangan">

                         <!-- Tanggal overlap di tengah gambar -->
                        <div style="
                            position: absolute;
                            top: 50%;
                            left: 50%;
                            transform: translate(-50%, -50%);
                            font-size: 12px;
                            font-weight: bold;
                            color: black;
                            mix-blend-mode: multiply; /* biar kayak nyatu sama tinta */
                        ">
                            {{ \Carbon\Carbon::parse($data['tanggal_surat'] ?? now())->translatedFormat('d/m/Y') }}
                        </div>

                        <br>
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
