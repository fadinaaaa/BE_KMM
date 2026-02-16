<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pergantian_alats', function (Blueprint $table) {
            $table->id();

            // ✅ konsisten seperti keluar-masuk
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade');

            // simpan snapshot supaya aman walaupun item berubah nama/satuan
            $table->string('nama_barang');
            $table->string('satuan');

            $table->date('tanggal');
            $table->integer('nominal');

            $table->string('pic');
            $table->string('tanda_tangan');

            // path file di storage/app/public/...
            $table->string('foto_lama')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pergantian_alats');
    }
};
