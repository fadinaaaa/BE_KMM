<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeminjamanBarang extends Model
{
    use HasFactory;

    protected $table = 'peminjaman_barangs';

    protected $fillable = [
        'item_id',
        'nama_barang',
        'satuan',
        'tanggal_pinjam',
        'tanggal_kembali',
        'pic',
        'foto_barang',
        'tanda_tangan',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }
}
