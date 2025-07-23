<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Ranium\SeedOnce\Traits\SeedOnce;

class CreateFranchisePermSeeder extends Seeder
{
    use SeedOnce;

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $permission = config('roles.models.permission')::create([
            'name' => 'Franchises list',
            'slug' => 'list.franchises',
            'description' => '', // optional
        ]);
        $role = config('roles.models.role')::where('slug', 'admin_bis')->first();
        $role->attachPermission($permission);
    }
}
