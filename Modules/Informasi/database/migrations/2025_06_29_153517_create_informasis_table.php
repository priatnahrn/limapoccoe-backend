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
        Schema::create('informasis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('judul');
            $table->text('konten')->nullable();
            $table->string('slug')->unique();
            $table->string('gambar')->nullable();

            // Boleh null, tanpa default(null)
            $table->enum('kategori', ['berita', 'pengumuman', 'artikel', 'wisata', 'produk', 'banner', 'galeri'])->nullable();

            $table->foreignUuid('created_by')
                ->nullable()
                ->constrained('auth_users')
                ->nullOnDelete(); // lebih aman dibanding cascade

            $table->foreignUuid('updated_by')
                ->nullable()
                ->constrained('auth_users')
                ->nullOnDelete();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('informasis');
    }
};
