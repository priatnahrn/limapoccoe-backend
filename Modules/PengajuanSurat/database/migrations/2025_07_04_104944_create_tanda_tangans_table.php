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
        Schema::create('tanda_tangans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ajuan_id')->constrained('ajuans')->onDelete('cascade');
            $table->foreignUuid('signed_by')->constrained('auth_users')->onDelete('cascade');
            $table->text('signature');
            $table->text('signature_data');
            $table->timestamp('signed_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tanda_tangans');
    }
};
