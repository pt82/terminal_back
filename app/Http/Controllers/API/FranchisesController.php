<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Franchise;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FranchisesController extends Controller
{
    /**
     * Список франшиз
     *
     * @return JsonResponse
     */
    public function index()
    {
        if (Auth::user()->root_access || Auth::user()->level()>=90) {
            $franchises = Franchise::select('id as franchise_admin_id', 'name')->get();
        }
            return response()->json([
                "message" => "List Franchises",
                "data" => $franchises ?? []
            ]);
        }

}
