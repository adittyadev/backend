<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaldoUser extends Model
{
    protected $table = 'saldouser_2210003';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'iduser_2210003',
        'jumlahsaldo_2210003',
    ];
}
