<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Ranium\SeedOnce\Traits\SeedOnce;

class CreateAnalyticsCommonListPermSeeder extends Seeder
{
    use SeedOnce;

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $permission = config('roles.models.permission')::firstOrcreate([
            'name' => 'Common analytics list',
            'slug' => 'list.analytics.common',
            'description' => '', // optional
        ]);
        $roles = config('roles.models.role')::whereIn('slug', ['admin_company_group', 'account_bis', 'admin_bis'])
            ->get();
        foreach ($roles as $role) {
            $role->attachPermission($permission);
        }
    }
}
