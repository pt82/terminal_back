<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        $this->call(TestSeeder::class);
        $this->call(StudentRoleSeeder::class);
        $this->call(CourseSeeder::class);
        $this->call(LessonSeeder::class);
        $this->call(ClientRoleSeeder::class);
        $this->call(CreateFranchisePermSeeder::class);
        $this->call(CreateChainPermSeeder::class);
        $this->call(EditLessonCourseSeeder::class);
        $this->call(CreateLessonCourseSeeder::class);
        $this->call(CreateAnalyticsCommonListPermSeeder::class);
    }
}
