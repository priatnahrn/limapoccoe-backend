<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Surat Keterangan Tidak Mampu</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; line-height: 1.6; margin: 40px; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .mt-3 { margin-top: 1rem; }
        .mt-5 { margin-top: 3rem; }
        .text-right { text-align: right; }
        .indent { text-indent: 2em; }
    </style>
</head>
<body>

    <table width="100%">
        <tr>
            <td style="width: 100px;">
               <img src="{{ asset('storage/logo-limapoccoe.png') }}" alt="Logo Desa" style="height: 100px;">
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
        <h2><u>SURAT KETERANGAN TIDAK MAMPU</u></h2>
        <div>Nomor: {{ $ajuan->nomor_surat ?? '___/SKTM/___/__/____' }}</div>
    </div>

    <p class="mt-3">Yang bertanda tangan dibawah ini:</p>
    <table>
        <tr><td>N a m a</td><td>: {{ $data['ttd_nama'] ?? 'MARLINA' }}</td></tr>
        <tr><td>J a b a t a n</td><td>: {{ $data['ttd_jabatan'] ?? 'SEKRETARIS DESA LIMAPOCCOE' }}</td></tr>
    </table>

    <p class="mt-3">Menerangkan bahwa:</p>
    <table>
        <tr><td>N a m a</td><td>: {{ $user->name ?? $data['nama'] ?? '-' }}</td></tr>
        <tr><td>NIK</td><td>: {{ $user->nik ?? $data['nik'] ?? '-' }}</td></tr>
        <tr><td>Tempat/Tanggal Lahir</td>
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
        <tr><td>Nama Ayah</td><td>: {{ $data['nama_ayah'] ?? '-' }}</td></tr>
        <tr><td>Pekerjaan</td><td>: {{ $data['pekerjaan_ayah'] ?? '-' }}</td></tr>
        <tr><td>Nama Ibu</td><td>: {{ $data['nama_ibu'] ?? '-' }}</td></tr>
        <tr><td>Pekerjaan</td><td>: {{ $data['pekerjaan_ibu'] ?? '-' }}</td></tr>
        <tr><td>Jumlah Tanggungan</td><td>: {{ $data['jumlah_tanggungan'] ?? '-' }}</td></tr>
    </table>

    <p class="mt-3 indent">
        Benar penduduk di atas adalah penduduk Dusun {{ optional($profile)->dusun ?? $data['dusun'] ?? '-' }} Desa Limapoccoe tergolong dari keluarga yang kurang mampu.
    </p>

    <p class="indent">
        Demikian surat keterangan ini kami buat dengan sebenarnya untuk digunakan seperlunya.
    </p>

    <div class="text-right mt-5">
        <div>Limapoccoe, {{ \Carbon\Carbon::parse($data['tanggal_surat'] ?? now())->translatedFormat('d F Y') }}</div>
        <div>An. KEPALA DESA LIMAPOCCOE</div>
        <div>KEPALA DESA</div>
        <br><br>

        @if ($ajuan->status === 'approved' && $ajuan->tandaTangan)
        @php
            $ttdPath = storage_path('app/private/tanda-tangan-digital.png');
            $ttdBase64 = file_exists($ttdPath) ? base64_encode(file_get_contents($ttdPath)) : null;
        @endphp

        @if ($ttdBase64)
            <img src="data:image/png;base64,{{ $ttdBase64 }}" alt="Tanda Tangan" style="height: 200px;">
            <div><strong>{{ $ajuan->tandaTangan->user->name ?? 'H ANDI ABU BAKRI' }}</strong></div>
        @else
            <div style="height: 100px;"></div>
            <div><strong style="color: grey">Tanda tangan tidak ditemukan</strong></div>
        @endif
        @else
            <div style="height: 100px;"></div>
            <div><strong style="color: grey">Belum ditandatangani</strong></div>
        @endif

    </div>

</body>
</html>
