<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MintaUang extends Model
{
    protected $table = 'mintauang_2210003';
    protected $primaryKey = 'noref_2210003';

    protected $fillable = [
        'noref_2210003',
        'dari_iduser_2210003',
        'ke_iduser_2210003',
        'iduser_2210003',
        'jumlahuang_2210003',
        'stt_2210003',
    ];
    public $incrementing = false;
    public $timestamps = false;
}
