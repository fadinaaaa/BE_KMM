<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->renameColumn('stok', 'saldo');
            $table->renameColumn('min_stok', 'minimal_saldo');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->renameColumn('saldo', 'stok');
            $table->renameColumn('minimal_saldo', 'min_stok');
        });
    }
};
