<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Socialite;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite as SocialiteFacade;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SocialController extends Controller
{
    protected $authService;

    public function __construct()
    {
        $this->authService = app()->make(AuthService::class);
    }

    public function redirectToProvider($provider)
    {
        return SocialiteFacade::driver($provider)->stateless()->redirect()->getTargetUrl();
    }

    public function callbackFromProvider($provider)
    {
        $user = SocialiteFacade::driver($provider)->stateless()->user();
        $socialite = DB::table('socialites')
            ->where('provider', $provider)
            ->where('uid', $user->id)
            ->first();
        if (!$socialite) {
            $auth = $this->register($user);
        } else {
            $auth = $this->login($socialite->user_id);
        }

        Cache::put('socialite_' . $provider . '_' . $user->id, '1', 10);

        return view('socialite', [
            'name' => $user->name,
            'id' => $user->id,
            'email' => $user->getEmail(),
            'data' => addslashes(json_encode($auth))
        ]);
    }

    protected function register($user)
    {
        return '';
    }

    protected function login($userId)
    {
        return $this->authService->authData(User::find($userId));
    }

    public function attach(Request $request)
    {
        if (empty($request->provider) or empty($request->uid))
            abort(403, 'Provider or uid not specified');
        if (!Cache::get('socialite_' . $request->provider . '_' . $request->uid))
            abort(403, 'Try again');

        Socialite::where('user_id', '!=', $request->user()->id)
            ->where('provider', $request->provider)
            ->where('uid', $request->uid)
            ->delete();
        Socialite::firstOrCreate([
            'user_id' => $request->user()->id,
            'provider' => $request->provider,
            'uid' => $request->uid,
        ]);
        return ['success' => true];
    }
}
