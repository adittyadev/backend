<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KirimUang extends Model
{
    protected $table = 'kirimuang_2210003';
    protected $primaryKey = 'noref_2210003';

    protected $fillable = [
        'tglkirim_2210003',
        'dari_iduser_2210003',
        'ke_iduser_2210003',
        'jumlahuang_2210003',
    ];
    public $incrementing = false;
    public $timestamps = false;
}
