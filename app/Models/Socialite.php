<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Socialite extends Model
{
    use HasFactory;

    protected $table = 'socialites';
    protected $fillable = [
        'user_id',
        'provider',
        'uid'
    ];


}

