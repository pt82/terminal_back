<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffComment extends Model
{
    use HasFactory;
    protected $table='staff_comments';
    protected $dates = [
        'date'
    ];
    protected $fillable = [
        'id',
        'user_id',
        'chain_id',
        'department_id',
        'yc_id',
        'salon_id',
        'type',
        'master_id',
        'text',
        'date',
        'rating',
        'yc_user_id',
        'user_name',
        'user_avatar',
        'user_email',
        'user_phone',
        'created_at',
        'updated_at'
    ];

}
