<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PergantianAlat extends Model
{
    use HasFactory;

    protected $table = 'pergantian_alats';

    protected $fillable = [
        'item_id',
        'nama_barang',
        'satuan',
        'tanggal',
        'nominal',
        'pic',
        'tanda_tangan',
        'foto_lama',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }
}
