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
    Schema::create('items', function (Blueprint $table) {
    $table->id();
    $table->string('kode')->unique(); // B_001 / A_001
    $table->string('nama');
    $table->enum('jenis', ['barang','alat']);
    $table->string('satuan'); // Bh, pcs, unit
    $table->integer('stok'); // saldo
    $table->integer('min_stok');
    $table->timestamps();
});

}

};
