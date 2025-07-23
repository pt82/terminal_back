<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;

class RootUsersOnly
{

    public function handle($request, Closure $next)
    {
dd($request->user());
        if (!$request->user());
            abort(403);
        return $next($request);
    }
}
