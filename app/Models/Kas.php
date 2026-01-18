<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kas extends Model
{
    protected $table = 'kas_2210003';

    // 🔴 PENTING
    protected $primaryKey = 'notrans_2210003';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'notrans_2210003',
        'tanggal_2210003',
        'jumlahuang_2210003',
        'keterangan_2210003',
        'jenis_2210003',
        'iduser_2210003',
    ];
}
