<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    use HasFactory;

    use HasFactory;
    protected $table='histories';
    protected $fillable = [
        'user_id',
        'ycrecord_id',
        'time_ring',
        'success_ring',
        'comments'
    ];
}
