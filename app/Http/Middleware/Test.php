<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;

class Test
{

    public function handle($request, Closure $next)
    {

        \Auth::login(User::find(4));
        return $next($request);
    }
}
