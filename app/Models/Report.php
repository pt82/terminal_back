<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;
    protected $table='reports';
    protected $casts = [
        'data' => 'array',
         ];
    protected $fillable = [
        'user_id',
        'chain_id',
        'data',
        'type',
        'title'
    ];
}
