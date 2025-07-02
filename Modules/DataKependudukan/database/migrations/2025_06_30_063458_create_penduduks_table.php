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
        Schema::create('penduduks', function (Blueprint $table) {
            $table->id();

            $table->foreignUuid('keluarga_id')->constrained('keluargas')->onDelete('cascade');

            $table->string('nik', 20)->unique();
            $table->string('no_urut', 10)->nullable();             // No urut dalam KK
            $table->string('nama_lengkap', 100);
            $table->enum('hubungan', ['Kepala Keluarga', 'Istri', 'Anak', 'Cucu', 'Famili Lain', 'Saudara', 'Orang Tua'])->nullable();            // Hubungan dalam KK
            $table->string('tempat_lahir', 50)->nullable();
            $table->date('tgl_lahir')->nullable();
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan'])->nullable();                  // Jenis kelamin
            $table->enum('status_perkawinan',['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati'])->nullable();
            $table->enum('agama', ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Lainnya'])->nullable();
            $table->enum('pendidikan', [
                'Tidak/Belum Sekolah',
                'Belum Tamat SD/Sederajat',
                'Tamat SD/Sederajat',
                'SLTP/Sederajat',
                'SLTA/Sederajat',
                'D-1/D-2',
                'D-3',
                'S-1',
                'S-2',
                'S-3'
            ])->nullable();

            $table->string('pekerjaan', 50)->nullable();
            $table->string('no_bpjs', 20)->nullable();
            $table->string('nama_ayah', 100)->nullable();
            $table->string('nama_ibu', 100)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penduduks');
    }
};
