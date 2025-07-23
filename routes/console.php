<?php

use App\Helpers\ApiServiceHelper;
use App\Models\Terminal;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Ixudra\Curl\Facades\Curl;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

//    $spots = $poster->to('access.getSpots')->asJson()->get();

Artisan::command('tst', function (ApiServiceHelper $api, Faker\Generator $faker) {
    \DB::enableQueryLog();

    dd(\DB::getQueryLog());

//    $lessons = \App\Models\Lesson
//        ::leftJoin('lesson_user', 'lessons.id', '=', 'lesson_user.lesson_id')
//        ->where('lessons.course_id', 2)
//        ->where('lesson_user.user_id', 12085)
//        ->get();
    $lessons = \App\Models\Lesson::where('course_id', 2)->with(['lessonUser' => function ($query) {
        $query->where('user_id', 12085);
    }])->get();
    dd($lessons->toArray());

    $api = ApiServiceHelper::api(['token' => '419723:91293871219dc1b5241847079734a476']);
//    $api = ApiServiceHelper::api(['chain' => 1]);
//    $api = ApiServiceHelper::api(['chain' => 'e8e5bdae-332d-42f7-b80f-bd1e4371aca3']);
//    $api = ApiServiceHelper::api(['user' => 12085]);
//    $api = ApiServiceHelper::api(['user' => \App\Models\User::find(12085)]);
//    $api = \Auth::user()->apiService();
//    $api = ApiServiceHelper::api([
//        'token' => $request->token ?? '',
//        'login' => $request->login ?? '',
//        'password' => $request->password ?? '',
//    ]);

//    print_r($api->to('access.getEmployees')->asJson()->get());

    $product = [
        'product_name'           => 'test-product',
        'menu_category_id'       => 0,
        'workshop'               => 1,
        'weight_flag'            => 0,
        'color'                  => 'red',
        'different_spots_prices' => 0,
        'modifications'          => 0,
        'barcode'                => '4820098749621',
        'cost'                   => 2000,
        'price'                  => 3000,
        'visible'                => 1,
        'fiscal_code'            => 1234567890,
    ];
//    print_r($api->to('menu.createProduct')->withData($product)->asJson()->post());
//    print_r($api->to('menu.removeProduct')->withData(['product_id' => 248])->asJson()->post());
    print_r($api->to('menu.getProducts')->withQueryParams(['type' => 'batchtickets'])->asJson()->get());
//    print_r($api->to('access.getEmployees')->asJson()->get());

    exit;
//    print_r($api->service);
//    print_r($faker->sentence(5));
    \DB::statement("SET foreign_key_checks=0");
    \App\Models\Lesson::truncate();
    \DB::statement("SET foreign_key_checks=1");
});
