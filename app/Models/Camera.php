<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Camera extends Model
{
    use HasFactory;
    protected $table='cameras';
    protected $fillable = [
        'camera_id',
        'camera_ivideon_id',
        'department_id',
        'camera_name',
        'camera_adress',
        'created_at',
        'updated_at'];
}
