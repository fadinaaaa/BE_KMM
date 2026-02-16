<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('peminjaman_barangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade');

            // snapshot dari items agar kalau items berubah, log peminjaman tetap aman
            $table->string('nama_barang');
            $table->string('satuan')->nullable();

            $table->date('tanggal_pinjam');
            $table->date('tanggal_kembali')->nullable();

            $table->string('pic'); // peminjam / PIC

            $table->string('foto_barang')->nullable();     // path file
            $table->string('tanda_tangan')->nullable();    // path file ttd (png)

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peminjaman_barangs');
    }
};
