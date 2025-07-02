<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('profile_masyarakats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('auth_users')->onDelete('cascade');
            $table->string('tempat_lahir');
            $table->date('tanggal_lahir');
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan']);
            $table->enum('golongan_darah', ["A", "B", "AB", "O"])->nullable();
            $table->enum('dusun', ["WT.Bengo", "Barua", "Mappasaile", "Kampala", "Kaluku", "Jambua", "Bontopanno", "Samata"]);
            $table->text('alamat');
            $table->string('rt_rw', 12)->nullable();
            $table->string('kelurahan')->default('Limapoccoe');
            $table->string('kecamatan')->default('Cenrana');
            $table->string('kabupaten_kota')->default('Maros');
            $table->string('provinsi')->default('Sulawesi Selatan');
            $table->enum('agama', ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Lainnya'])->nullable();
            $table->enum('status_perkawinan', ['belum kawin', 'kawin'])->nullable();
            $table->string('pekerjaan')->nullable();
            $table->string('kewarganegaraan')->default('WNI');
            $table->enum('pendidikan_terakhir', ["Tidak/Belum Sekolah", "Belum Tamat SD/Sederajat", "Tamat SD/Sederajat", "SLTP/Sederajat", "SLTA/Sederajat", "Diploma I/II", "Akademi/Diploma III/Sarjana Muda", "Diploma IV/Strata I", "Diploma V/Strata II", "Diploma VI/Strata III"])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_masyarakats');
    }
};
