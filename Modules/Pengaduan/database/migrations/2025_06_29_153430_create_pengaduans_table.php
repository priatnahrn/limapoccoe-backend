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
        Schema::create('pengaduans', function (Blueprint $table) {
           $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('auth_users')->onDelete('cascade');
            $table->string('title');
            $table->text('content');
            $table->string('location');
            $table->enum('category', ['Administrasi', 'Infrastruktur & Fasilitas', 'Kesehatan', 'Keamanan & Ketertiban', 'Pendidikan', 'Lingkungan', 'Kinerja Perangkat Desa', 'Ekonomi & Pekerjaan', 'Teknologi', 'Lainnya']);
            $table->string('evidence')->nullable();
            $table->enum('status', ['waiting', 'processed', 'approved'])->default('waiting');
            $table->text('response')->nullable();
            $table->foreignUuid('response_by')->nullable()->constrained('auth_users')->onDelete('cascade');
            $table->date('response_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengaduans');
    }
};
