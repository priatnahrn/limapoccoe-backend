<!-- resources/views/test-pdf.blade.php -->

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test PDF Gambar</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 20px;
        }
        .center { text-align: center; }
        .logo { width: 120px; margin-bottom: 20px; }
    </style>
</head>
<body>

    <div class="center">
        <h2>PDF Test dengan Gambar</h2>

        <p>Logo di bawah ini diambil dari public folder (JPG lebih aman dari PNG):</p>

        <img src="{{ public_path('logo-limapoccoe.png') }}" alt="Logo" class="logo">

        <p>Jika PDF ini berhasil didownload tanpa error, artinya DomPDF bisa render gambar tanpa imagick 😎</p>
    </div>

</body>
</html>
