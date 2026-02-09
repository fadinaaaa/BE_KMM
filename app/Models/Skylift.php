<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skylift extends Model
{
    
    use HasFactory;

    protected $table = 'skylifts';
    protected $primaryKey = 'id';

    protected $fillable = [
        'nama',
        'quantity'
    ];
}
