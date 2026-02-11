<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class keluarmasukbarang extends Model
{
    use HasFactory;

    protected $table = 'keluarmasukbarangs';
    protected $primaryKey = 'id';

     protected $fillable = [
        'nama',
        'satuan',
        'keluarmasuk',
        'tanggal',
        'nominal',
        'PIC',
        'keterangan',
        'item_id', // foreign key ke tabel items
    ];

       public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }
}
