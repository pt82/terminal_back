<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Lesson extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table='lessons';

    protected $fillable = [
        'id',
        'course_id',
        'title',
        'description',
        'published',
        'time',
        'test',
        'link_admin_test',
        'total_question_test'
        ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];
    protected $casts = [
        'created_at' => 'datetime:d.m.Y',
        'updated_at' => 'datetime:d.m.Y',
        'deleted_at' => 'datetime:d.m.Y',
    ];


    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps()->withPivotValue('date_begin',Now());
    }
    /**
     * @return boolean
     */
    public function update_date_end()
    {
       return $this->belongsToMany(User::class)->update(['date_end'=>Now()]);
    }
    public function pivotLessonUser()
    {
        return $this->belongsToMany(Lesson::class,'lesson_user')->where('lessons.published',1)->where('lesson_user.user_id',\Auth::id())->withPivot('date_end');
    }

    public function files()
    {
        return $this->hasMany(File::class);
    }

    public function lessonUser()
    {
        return $this->hasOne(LessonUser::class);
    }
}
