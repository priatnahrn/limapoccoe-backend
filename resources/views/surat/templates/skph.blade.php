<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Surat Keterangan Penghasilan Orang Tua</title>
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
            width: 200px;
        }

        table tr td:nth-child(2) {
            padding-left: 20px;
        }

        .text-small {
            font-size: 9pt;
        }
    </style>
</head>
<body>

@php
    $nomorSurat = $ajuan->nomor_surat_tersimpan ?? '___/SKPOT/LPC/CRN/__/____';
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
    <h4><u>SURAT KETERANGAN PENGHASILAN ORANG TUA</u></h4>
    <div>Nomor: {{ $nomorSurat }}</div>
</div>

<p class="mt-3 indent">
    Yang bertanda tangan di bawah ini, Kepala Desa Limapoccoe Kecamatan Cenrana Kabupaten Maros, menerangkan dengan sebenar-benarnya bahwa:
</p>

<p class="mt-2"><strong>Data Diri Calon Mahasiswa</strong></p>
<table style="margin-left: 20px;">
    <tr><td>Nama Calon Mahasiswa</td><td>: {{ $data['nama'] ?? '-' }}</td></tr>
    <tr><td>Jenis Kelamin</td><td>: {{ $data['jenis_kelamin'] ?? '-' }}</td></tr>
    <tr><td>Tempat/Tanggal Lahir</td><td>: {{ $data['tempat_lahir'] ?? '-' }}, {{ \Carbon\Carbon::parse($data['tanggal_lahir'] ?? now())->format('d-m-Y') }}</td></tr>
    <tr><td>Agama</td><td>: {{ $data['agama'] ?? '-' }}</td></tr>
    <tr><td>Asal Sekolah</td><td>: {{ $data['asal_sekolah'] ?? '-' }}</td></tr>
    <tr><td>Jurusan</td><td>: {{ $data['jurusan'] ?? '-' }}</td></tr>
</table>

<p class="mt-2"><strong>Data Orang Tua</strong></p>
<table style="margin-left: 20px;">
    <tr><td>Nama Orang Tua/Wali (Ayah)</td><td>: {{ $data['nama_ayah'] ?? '-' }}</td></tr>
    <tr><td>Alamat</td><td>: Dusun {{ $data['dusun'] ?? '-' }}, {{ $data['alamat'] ?? '-' }}</td></tr>
    <tr><td>Pekerjaan</td><td>: {{ $data['pekerjaan_ayah'] ?? '-' }}</td></tr>
    <tr>
        <td>Penghasilan (Per Bulan)</td>
        <td>: Rp{{ number_format($data['penghasilan_ayah_rp'] ?? 0, 0, ',', '.') }}
            <span class="text-small">({{ $data['penghasilan_ayah_terbilang'] ?? '-' }}) /Bulan</span>
        </td>
    </tr>

    <tr><td>Nama Orang Tua/Wali (Ibu)</td><td>: {{ $data['nama_ibu'] ?? '-' }}</td></tr>
    <tr><td>Alamat</td><td>: Dusun {{ $data['dusun'] ?? '-' }}, {{ $data['alamat'] ?? '-' }}</td></tr>
    <tr><td>Pekerjaan</td><td>: {{ $data['pekerjaan_ibu'] ?? '-' }}</td></tr>
    <tr>
        <td>Penghasilan (Per Bulan)</td>
        <td>
            @if(!empty($data['penghasilan_ibu_rp']))
                : Rp{{ number_format($data['penghasilan_ibu_rp'], 0, ',', '.') }}
                <span class="text-small">({{ $data['penghasilan_ibu_terbilang'] ?? '-' }}) /Bulan</span>
            @else
                : -
            @endif
        </td>
    </tr>
</table>

<p class="mt-3 indent">
    Demikian surat keterangan ini dibuat untuk digunakan seperlunya.
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
            <div style="margin-top: 10px;">
                @php
                    $ttdPath = storage_path('app/private/tanda-tangan-digital.png');
                    $ttdBase64 = file_exists($ttdPath) ? base64_encode(file_get_contents($ttdPath)) : null;
                @endphp

                @if ($ajuan->status === 'approved' && $ttdBase64)
                    <img src="data:image/png;base64,{{ $ttdBase64 }}" style="height: 180px;" alt="Tanda Tangan"><br>
                    <strong>{{ $ajuan->tandaTangan->user->name ?? 'H. ANDI ABU BAKRI' }}</strong>
                @else
                    <div style="height: 100px;"></div>
                    <strong style="color: grey;">Belum ditandatangani</strong>
                @endif
            </div>
        </td>
    </tr>
</table>

</body>
</html>
