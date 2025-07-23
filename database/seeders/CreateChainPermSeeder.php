<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Ranium\SeedOnce\Traits\SeedOnce;

class CreateChainPermSeeder extends Seeder
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
            'name' => 'Chains list',
            'slug' => 'list.chains',
            'description' => '', // optional
        ]);
        $role = config('roles.models.role')::whereIn('slug', ['admin_bis','account_bis'])->get();
        foreach ($role as $roleOne){
            $roleOne->attachPermission($permission);
        }

    }
}
