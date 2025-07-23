<?php

namespace App\Http\Controllers\API;

use App\Events\DetectedUser;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Ycdb;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Terminal;

class IvideonWebhookController extends Controller
{
    public function hook(Request $request)
    {
        $data=$request->all();
        $user = User::where('person_ivideon_id', $data['event']['data']['face_id'])->first();
        $terminal = DB::table('terminals')
            ->Join('cameras','cameras.id','=','terminals.camera_id')
            ->where('cameras.camera_ivideon_id', '=', $data['event']['device_id'])
            ->first();
        if ($terminal) {
            DetectedUser::dispatch([
                'event' => $user ? 'detected' : 'not_detected',
                'data' => [
                    'user_id' => $terminal->user_id,
                    'face_id' => $data['event']['data']['face_id'] ?? null,
                    'user' => $user ?? null,
                ]
            ]);
        }
    }
}
