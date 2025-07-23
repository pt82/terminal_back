<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Terminal extends Model
{
    use HasFactory;
    protected $table='terminals';
    protected $fillable = [
        'id',
        'user_id',
        'cameras_id',
        'department_id',
        'name',
        'adress',
        'yc_account_card',
        'yc_account_cash',
        'yc_storage_product',
        'created_at',
        'updated_at'
    ];
}
