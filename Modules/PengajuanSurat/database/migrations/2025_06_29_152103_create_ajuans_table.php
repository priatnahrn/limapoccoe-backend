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
        Schema::create('ajuans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('auth_users')->onDelete('cascade');
            $table->foreignUuid('surat_id')->constrained('surats')->onDelete('cascade');
            $table->string('nomor_surat')->nullable();
            $table->json('data_surat')->nullable();
            $table->json('lampiran')->nullable();
            $table->string('file')->nullable();
            $table->enum('status', ['processed', 'confirmed', 'approved', 'rejected'])->default('processed');
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ajuans');
    }
};
