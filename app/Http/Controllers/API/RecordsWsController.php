<?php

namespace App\Http\Controllers\API;

use App\Events\OneRecord;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class RecordsWsController extends Controller
{
    public function setInWork(Request $request)
    {
        OneRecord::dispatch([
            'event' => 'in_work',
            'data' => [
                'user_id' => $request->user()->id,
                'record_id' => $request->record_id ?? 0
            ]
        ]);

        return response()->json([
            'ok'
        ]);
    }
    public function closeInWork(Request $request)
    {
        OneRecord::dispatch([
            'event' => 'in_work_close',
            'data' => [
                'user_id' => $request->user()->id
            ]
        ]);

        return response()->json([
            'ok'
        ]);
    }

}
