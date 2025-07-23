<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('test', function () {
    info('2323');
    return 1;
});

Broadcast::channel('one_record', function ($user) {
    return ['user_id' => (int) $user->id];
});

Broadcast::channel('detected_user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id ? ['user_id' => (int) $user->id] : false;
});
