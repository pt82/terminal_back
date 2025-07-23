<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Franchise extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Uuids;
    protected $guarded = [''];
    protected $casts = [
        'uuid' => 'string',
    ];
    protected $fillable = [
        'id',
        'name',
        'use_system',
        'login',
        'user_token',
    ];
    protected $hidden = [
        'password',
        ];
 protected $dates = [
     'created_at',
     'updated_at',
     'deleted_at',
 ];
}
