<!DOCTYPE html>
<html>
<head>
    <title>Verifikasi Dokumen Surat</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 40px; }
        .status { font-size: 22px; font-weight: bold; margin-bottom: 20px; }
        .valid { color: green; }
        .invalid { color: red; }
        table { margin: 0 auto; border-collapse: collapse; }
        th, td { padding: 10px 15px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <h1>Hasil Verifikasi Dokumen</h1>
    <div class="status {{ $valid ? 'valid' : 'invalid' }}">
        {{ $message }}
    </div>

    @if ($valid && isset($data))
        <table>
            <tr><th>Ajuan ID</th><td>{{ $data['ajuan_id'] }}</td></tr>
            <tr><th>User ID</th><td>{{ $data['user_id'] }}</td></tr>
            <tr><th>Nomor Surat</th><td>{{ $data['nomor_surat'] }}</td></tr>
            <tr><th>Waktu TTD</th><td>{{ \Carbon\Carbon::parse($data['timestamp'])->format('d F Y H:i:s') }}</td></tr>
        </table>
    @endif
</body>
</html>
