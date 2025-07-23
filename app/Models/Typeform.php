<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Typeform extends Model
{
    use HasFactory;
    use HasFactory;
    protected $table='typeforms';
    protected $casts = [
        'answers' => 'array',
        'definition' => 'array',
    ];
    protected $fillable = [
        'id',
        'form_id',
        'event_id',
        'chain_id',
        'department_id',
        'ycrecord_id',
        'title',
        'answers',
        'adress',
        'definition',
        'calculated',
        'created_at',
        'updated_at'
    ];
}
