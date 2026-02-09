<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $table = 'items';
    protected $primaryKey = 'id';

    protected $fillable = [
        'kode',
        'nama',
        'jenis',
        'satuan',
        'saldo',
        'minimal_saldo',
    ];

    public function keluarMasukBarangs()
    {
        return $this->hasMany(KeluarMasukBarang::class, 'item_id', 'id');
    }
}
