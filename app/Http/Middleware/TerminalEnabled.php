<?php

namespace App\Http\Middleware;

use App\Models\Chain;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TerminalEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if(Auth::check()) {
            $chain = Chain::query()->find(Auth::user()->chain_id);
            if (Auth::user()->level() === 10 && !$chain->isTerminalEnabled())
            {
                Auth::user()->currentAccessToken()->delete();
            }
        }
        return $next($request);
    }
}
