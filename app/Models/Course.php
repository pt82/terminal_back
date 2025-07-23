<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class Course extends Model
{
    use HasFactory;
    use SoftDeletes;


    protected $table = 'courses';

    protected $fillable = [
        'id',
        'franchise_id',
        'user_id',
        'role_id',
        'title',
        'description',
        'published',
        'random_view'
    ];
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'created_at' => 'datetime:d.m.Y',
        'updated_at' => 'datetime:d.m.Y',
        'deleted_at' => 'datetime:d.m.Y',
    ];


    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('sequence', 'asc');
    }

    protected static function boot()
    {
            parent::boot();

            static::addGlobalScope('search', function (Builder $builder) {
                $builder->where('franchise_id', Auth::user()->franchise()->id);
            });
    }

}
