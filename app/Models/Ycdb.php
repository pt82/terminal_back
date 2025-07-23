<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Psy\Util\Json;

class Ycdb extends Model
{
    use HasFactory;
    protected $table='ycvatutina';
    protected $casts = [
        'custom_fields' => 'array',
        'categories'=> 'array',
    ];
    protected $fillable = [
        'ycitem_id',
        'person_id ',
        'department_id',
        'chain_id',
        'yc_id',
        'name',
        'phone',
        'email',
        'birth_date',
        'categories',
        'sex',
        'sex_id',
        'discount',
        'card',
        'importance_id',
        'importance',
        'comment',
        'sms_check',
        'sms_bot',
        'paid',
        'spent',
        'balance',
        'visits',
        'last_change_date',
        'custom_fields'
    ];

}
