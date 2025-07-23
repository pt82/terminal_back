<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YcService extends Model
{
    use HasFactory;
    protected $table='ycservices';
    protected $fillable = [
        'department_id',
        'chain_id',
        'service_id',
        'title',
      ];
}
