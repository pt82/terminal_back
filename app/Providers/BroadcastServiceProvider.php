<?php

namespace App\Providers;

use App\Http\Middleware\Test;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
//        Broadcast::routes(['prefix' => 'api', 'middleware' => Test::class]);
        Broadcast::routes(['prefix' => 'api', 'middleware' => 'auth:sanctum']);

        require base_path('routes/channels.php');
    }
}
