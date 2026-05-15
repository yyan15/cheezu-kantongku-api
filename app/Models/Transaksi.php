<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    protected $fillable = [
        'judul',
        'kategori',
        'tipe',
        'nominal',
        'catatan',
        'tanggal',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'nominal' => 'double',
    ];
}
