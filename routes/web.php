<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Ixudra\Curl\Facades\Curl;


Route::get('/', function () {
    \Artisan::call('route:clear');
//    \Artisan::call('config:clear');
//    \Artisan::call('cache:clear');

    echo 'test';
    exit;
});
