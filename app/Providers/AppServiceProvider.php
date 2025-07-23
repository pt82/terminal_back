<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Log::setTimezone(new \DateTimeZone('Asia/Novosibirsk'));
        if(in_array(env('APP_ENV'), ['test', 'production']))
            $this->app['request']->server->set('HTTPS', true);
    }
}
