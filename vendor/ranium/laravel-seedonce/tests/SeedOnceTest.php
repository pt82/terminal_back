<?php
namespace Ranium\SeedOnce\Test;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedOnceTest extends TestCase
{
    /** @test */
    public function it_migrates_seeders_table()
    {
        $this->assertTrue(Schema::hasTable('seeders'));
    }

    /** @test */
    public function it_stores_seeders()
    {
        $this->artisan('db:seed')->run();

        $this->assertDatabaseHas('seeders', [
            'seeder' => 'UsersTableSeeder'
        ]);

        $this->assertDatabaseMissing('seeders', [
            'seeder' => config('seedonce.database_seeder')
        ]);
    }

    /** @test */
    public function it_runs_seeders_only_once()
    {
        $this->artisan('db:seed')->run();

        $this->artisan('db:seed')->run();

        // Find the count of rows in users table. It should be 1
        $this->assertEquals(DB::table('users')->count(), 1);
        $this->assertEquals(DB::table('roles')->count(), 2);
    }
}
