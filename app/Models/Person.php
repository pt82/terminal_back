<?php

namespace App\Models;

use App\Traits\HasChains;
use App\Traits\HasItems;
use App\Traits\HasRoles;
use App\Traits\HasYcitems;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Person extends Model
{
    use HasFactory, HasRoles, HasYcitems, HasChains, SoftDeletes;
    protected $table='users';
    protected $dates = ['deleted_at'];
    protected $fillable = [
        'id',
        'person_id',
        'person_ivideon_id',
        'face_gallery_id',
        'work_posts_id',
        'role_id',
        'login',
        'password',
        'birth_date',
        'firstname',
        'lastname',
        'fatherland',
        'phone',
        'terminal_name',
        'avatar',
        'email',
        'yc_id',
        'yc_name',
        'comment',
        'sex',
        'description_person',
        'created_at',
        'updated_at'];

    public function getFullNameAttribute()
    {
        return $this->lastname." ".$this->firstname." ".$this->fatherland;
    }
    protected $casts = [
        'birth_date' => 'datetime',
    ];





}

