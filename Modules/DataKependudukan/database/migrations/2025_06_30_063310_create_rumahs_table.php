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
        Schema::create('rumahs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('no_rumah', 10)->nullable();
            $table->string('rt_rw', 7)->nullable();
            $table->enum('dusun', [
                'WT.Bengo',
                'Barua',
                'Mappasaile',
                'Kampala',
                'Kaluku',
                'Jambua',
                'Bontopanno',
                'Samata'
            ]);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rumahs');
    }
};
