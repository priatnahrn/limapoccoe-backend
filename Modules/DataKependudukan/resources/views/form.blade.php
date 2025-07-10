<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Form Data Keluarga</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
  <div class="max-w-4xl mx-auto bg-white p-6 rounded-2xl shadow-md space-y-6">
    <h1 class="text-2xl font-bold text-gray-700">Form Input Data Keluarga</h1>

    <!-- Informasi Rumah dan Nomor KK -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label for="rumah_id" class="block text-sm font-medium text-gray-600">Pilih Rumah</label>
        <select id="rumah_id" name="rumah_id" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm">
          <option value="">-- Pilih Rumah --</option>
          <option value="uuid1">WT.Bengo</option>
          <option value="uuid2">Barua</option>
          <option value="uuid3">Mappasaile</option>
          <!-- Tambahkan sesuai kebutuhan -->
        </select>
      </div>
      <div>
        <label for="nomor_kk" class="block text-sm font-medium text-gray-600">Nomor KK</label>
        <input type="text" id="nomor_kk" name="nomor_kk" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm" placeholder="Contoh: 1234567890123456">
      </div>
    </div>

    <!-- Anggota Keluarga -->
    <div>
      <h2 class="text-lg font-semibold text-gray-700 mb-2">Anggota Keluarga</h2>
      <div id="anggota-container" class="space-y-4">
        <!-- Anggota template disisipkan di sini -->
      </div>
      <button type="button" onclick="tambahAnggota()" class="mt-3 px-4 py-2 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700">+ Tambah Anggota</button>
    </div>

    <!-- Submit -->
    <div class="pt-4">
      <button class="px-6 py-2 bg-green-600 text-white rounded-lg shadow hover:bg-green-700">Simpan Data</button>
    </div>
  </div>

  <!-- Template Anggota -->
  <template id="anggota-template">
    <div class="p-4 bg-gray-50 border rounded-xl relative">
      <button type="button" onclick="hapusAnggota(this)" class="absolute top-2 right-2 text-red-500 hover:text-red-700">&times;</button>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm text-gray-600">Nama</label>
          <input type="text" name="anggota_keluargas[][nama]" class="mt-1 block w-full rounded border-gray-300">
        </div>
        <div>
          <label class="block text-sm text-gray-600">NIK</label>
          <input type="text" name="anggota_keluargas[][nik]" class="mt-1 block w-full rounded border-gray-300">
        </div>
        <div>
          <label class="block text-sm text-gray-600">Tanggal Lahir</label>
          <input type="date" name="anggota_keluargas[][tanggal_lahir]" class="mt-1 block w-full rounded border-gray-300">
        </div>
        <div class="md:col-span-3">
          <label class="block text-sm text-gray-600">Status</label>
          <input type="text" name="anggota_keluargas[][status]" class="mt-1 block w-full rounded border-gray-300" placeholder="Contoh: Ayah, Ibu, Anak">
        </div>
      </div>
    </div>
  </template>

  <script>
    function tambahAnggota() {
      const container = document.getElementById('anggota-container');
      const template = document.getElementById('anggota-template');
      const clone = template.content.cloneNode(true);
      container.appendChild(clone);
    }

    function hapusAnggota(button) {
      button.closest('.p-4').remove();
    }

    // Auto-tambah satu form anggota saat halaman dibuka
    window.onload = () => tambahAnggota();
  </script>
</body>
</html>
