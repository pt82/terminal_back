<?php


namespace Database\Seeders;


use Illuminate\Database\Seeder;
use Ranium\SeedOnce\Traits\SeedOnce;

class CreateLessonCourseSeeder extends Seeder
{
    use SeedOnce;

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $permissionLesson = config('roles.models.permission')::create([
            'name' => 'Lesson Create',
            'slug' => 'lesson.create',
            'description' => '', // optional
        ]);
        $permissionCourse = config('roles.models.permission')::create([
            'name' => 'Course Create',
            'slug' => 'course.Create',
            'description' => '', // optional
        ]);
        $role = config('roles.models.role')::whereIn('slug', ['admin_bis','account_bis','admin_company_group'])->get();
        foreach ($role as $roleOne){
            $roleOne->attachPermission($permissionLesson);
            $roleOne->attachPermission($permissionCourse);
        }

    }
}
