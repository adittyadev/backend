<?php

// app/Models/Scan.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Scan extends Model
{
    protected $fillable = ['user_id', 'data', 'type'];
}
