<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    use HasFactory;

    protected $table='photos';
    protected $fillable = [
        'id',
        'user_id',
        'ycrecord_id ',
        'type',
        'type_title',
        'name',
        'path',
        'num',
        'created_at',
        'updated_at'
    ];

}
