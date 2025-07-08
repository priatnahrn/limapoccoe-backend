<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Verifikasi Dokumen Surat Desa Limapoccoe</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center py-12 px-4">
    <div class="bg-white shadow-lg rounded-xl p-8 max-w-xl w-full text-center">
        <img src="{{ asset('logo-limapoccoe.png') }}" class="mx-auto h-20 mb-4" alt="Logo">

        <h1 class="text-2xl font-bold text-gray-800 mb-2">Verifikasi Keaslian Dokumen Surat</h1>
        <p class="text-gray-600 mb-6">Situs resmi untuk verifikasi keaslian dokumen surat oleh Pemerintah Desa Limapoccoe</p>

        <div class="text-lg font-semibold {{ $valid ? 'text-green-600' : 'text-red-600' }} mb-4">
            {{ $message }}
        </div>

        @if ($valid && isset($ajuan))
            <table class="table-auto w-full text-left text-sm border border-gray-300 rounded-lg overflow-hidden">
                <tbody>
                    <tr class="border-b">
                        <th class="p-3 bg-gray-50">Nama Pemohon</th>
                        <td class="p-3">{{ optional($ajuan->user)->name ?? 'Tidak diketahui' }}</td>
                    </tr>
                    <tr class="border-b">
                        <th class="p-3 bg-gray-50">Jenis Surat</th>
                        <td class="p-3">{{ optional($ajuan->surat)->nama_surat ?? '-' }}</td>
                    </tr>
                    <tr class="border-b">
                        <th class="p-3 bg-gray-50">Tanggal Pengajuan</th>
                        <td class="p-3">{{ optional($ajuan->created_at)->translatedFormat('d F Y H:i:s') ?? '-' }}</td>
                    </tr>
                    <tr class="border-b">
                        <th class="p-3 bg-gray-50">Nomor Surat</th>
                        <td class="p-3">{{ $ajuan->nomor_surat ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th class="p-3 bg-gray-50">Waktu TTD</th>
                        <td class="p-3">
                            {{ optional($ajuan->tandaTangan)->signed_at ? \Carbon\Carbon::parse($ajuan->tandaTangan->signed_at)->translatedFormat('d F Y H:i:s') : '-' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        @endif

        <div class="mt-6 text-sm text-gray-400">
            &copy; {{ now()->year }} Pemerintah Desa Limapoccoe
        </div>
    </div>
</body>
</html>
