<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;

    protected $table='files';
    protected $fillable = [
        'id',
        'lesson_id',
        'type',
        'name',
        'path',
       ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function lesson()
    {
         return $this->belongsTo(Lesson::class);
    }
}


