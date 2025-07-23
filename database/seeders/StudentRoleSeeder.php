<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Ranium\SeedOnce\Traits\SeedOnce;

class StudentRoleSeeder extends Seeder
{
    use SeedOnce;

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        config('roles.models.role')::create([
            'name' => 'Student',
            'slug' => 'student',
            'description' => '',
            'level' => 11,
        ]);
    }
}
