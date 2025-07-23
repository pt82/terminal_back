<p align="center">
    <a href="https://github.com/ranium/laravel-seedonce/actions">
        <img src="https://github.com/ranium/laravel-seedonce/workflows/tests/badge.svg" alt="Build Status">
    </a>
    <a href="https://packagist.org/packages/ranium/laravel-seedonce">
        <img src="https://img.shields.io/packagist/dt/ranium/laravel-seedonce" alt="Total Downloads">
    </a>
    <a href="https://packagist.org/packages/ranium/laravel-seedonce">
        <img src="https://img.shields.io/packagist/v/ranium/laravel-seedonce" alt="Latest Stable Version">
    </a>
    <a href="https://packagist.org/packages/ranium/laravel-seedonce">
        <img src="https://img.shields.io/packagist/l/ranium/laravel-seedonce" alt="License">
    </a>
</p>

# Laravel SeedOnce

This package works similar to `php artisan migrate`. When using this package, each seeder is seeded only once even if you run `php artisan db:seed` multiple times (on each deployment).

## Why was this package developed?

The purpose of this package is to make it easier for the developers to run seeders when working in teams and to run seeders during automated deployments. One doesn't need to remember which seeders have already been executed and which are pending. All this is handled by this package.

## Assumption

This package assumes that you use one seeder (ex.`DatabaseSeeder`) class as the main/parent for running all other seeders. This main seeder class should never seed any data directly on its own. It should only be used to run other seeder classes.

## Installation

Use composer to install the package.

```
composer require ranium/laravel-seedonce
```

Optionally, publish the package configuration file. Default config should work well in most of the cases.

```
php artisan vendor:publish --tag=laravel-seedonce-config
```

After the package is installed by composer, run the migrations to create a table which will hold the seeder information.

```
php artisan migrate
```

Note: This will create a table named `seeders` in your database. If you want to change this table's name, then you have to publish the package's config (above step) and modify the value of `table` in `config/seedonce.php`.

## Configuration

Once you have published the configuration file, you can edit it to suit your needs. Configuration options are as follows:

- `table`: This is the name of the table that will hold the details of the seeders that have been executed. The default value is *seeders* which should work in most of the cases.
- `database_seeder`: This is the name of the class that seeds all other seeders. In most of the cases this will be *DatabaseSeeder* which is the default value. Make sure to change this if you use a different class as the entry point to seed all other seeders.

## Usage

Use the `Ranium\SeedOnce\Traits\SeedOnce` trait in all your seeder classes including the main seeder (ex. `DatabaseSeeder`) class.

```
<?php
File: database/seeds/DatabaseSeeder.php

use Illuminate\Database\Seeder;
use Ranium\SeedOnce\Traits\SeedOnce;

class DatabaseSeeder extends Seeder
{
    use SeedOnce;

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(UsersTableSeeder::class);
        $this->call(RolesTableSeeder::class);
    }
}
?>

<?php
File: database/seeds/UsersTableSeeder.php

use Illuminate\Database\Seeder;
use Ranium\SeedOnce\Traits\SeedOnce;

class UsersTableSeeder extends Seeder
{
    use SeedOnce;

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // User table seeder logic
    }
}
?>
```

That's it. Now no matter how many times you run `php artisan db:seed`, the `UsersTableSeeder` will only be executed once.

### Projects with existing seeders

The usage instructions above assumes that you have installed this package before running any seeders. If you have already executed some or all seeders before installing this package, then you will need to mark those seeders as executed.

Run the following command to mark all existing seeder classes as seeded.

```
php artisan seedonce:mark-seeded
```

If you only want to mark specific seeder class as seeded, then run this command

```
php artisan seedonce:mark-seeded --class=UsersTableSeeder
```

## Seeders Status

Often you would want to know that status of your seeder classes i.e. which seeders have been executed and which are pending. Run the following command to get the status

```
php artisan seedonce:status
```

This will output a table with status of each seeder class

Seeded? | Seeder
--------|-------
Yes | UsersTableSeeder
No | RolesTableSeeder

## Testing

The package unit tests can be executed with the following command (from inside package's root directory)
```
/path/to/phpunit
```

## Credits

- [Abbas Ali](https://github.com/abbasali)


## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
