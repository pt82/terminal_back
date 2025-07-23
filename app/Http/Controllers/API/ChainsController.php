<?php

namespace App\Http\Controllers\API;

use App\Helpers\ApiServiceHelper;
use App\Http\Controllers\Controller;
use App\Models\Chain;
use App\Services\YcService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Ixudra\Curl\Facades\Curl;

class ChainsController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index()
    {
       if(Auth::user()->level()>=90 || Auth::user()->root_access){
           if(Auth::user()->root_access){
               return response()->json([
                   "message" => "List Chains",
                   'data'=>Chain::all() ?? []
               ]);
           }
           return response()->json([
               "message" => "List Chains",
               'data'=>Chain::where('franchise_id',Auth::user()->franchise_admin_id)->get() ?? []
           ]);
       }
        if(Auth::user()->level() >= 20  && Auth::user()->level() < 90){
            return response()->json([
                "message" => "List Chains",
                'data'=>Chain::whereIn('id',Auth::user()->chain->pluck('id'))->get() ?? []
            ]);
        }
       elseif(Auth::user()->level() < 20){ return response()->json(['error' => 'Нет прав'], 403);}
    }

    /**
     * Проверяем правильный ли введен токен для интегравционной системы
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkAccess(Request $request)
    {
        if ($request->user()->level() >= 20 ) {
            $api = ApiServiceHelper::api([
                'token' => $request->token ?? '',
                'login' => $request->login ?? '',
                'password' => $request->password ?? '',
            ]);
            if(empty($api->getCompaniesInfo()['success'])) {
                return response()->json([
                    'success'=> false,
                    'error' => 'Неверный токен',
                ], 406);
            }
            elseif (!empty($api->getCompaniesInfo()['success'])) {
                return $api->getCompaniesInfo();
            }
        }
        else{ return response()->json(['success'=> false,'error' => 'Нет прав'], 403);}
    }
    /**
     * Добввляем сеть
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        info(print_r($request->all(), true));
        if ($request->user()->level() >= 90 ) {
            $data=$request->all();
            if(isset($data['password'])){
                   $data['password']=bcrypt(trim($data['password']));
            }
            $data['chain_id']=Str::uuid()->toString();
            $data['franchise_id']=$request->user()->franchise_admin_id ?? NULL;
            if(!empty($request->franchise_id)){
                $data['franchise_id'] = $request->franchise_id;
            }
                return response()->json([
                    'success'=>(boolean)($result=Chain::create($data)),
                    'data'=>$result
                ]);
            }
        else{ return response()->json(['success'=> false,'error' => 'Нет прав'], 403);}
    }


    /**
     * Показать сеть.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request, $id)
    {
        if ($request->user()->level() >= 20 ) {
            $chain=Chain::where('id',$id)->where('franchise_id', Auth::user()->franchise()->id)->first();
            if(!$chain){
                throw new ModelNotFoundException('Не найдена компания');
            }
            return response()->json([
                'success'=> (boolean) $chain,
                'data' =>  $chain ?? [],
            ]);
        }
        else{ return response()->json(['success'=> false,'error' => 'Нет прав'], 403);}
    }

    /**
     * Редактируем сеть.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        if ($request->user()->level() >= 20 ) {
            $data = $request->all();
            $chain = Chain::where('id',$id)->where('franchise_id', Auth::user()->franchise()->id)->first();
            if(!$chain){
                throw new ModelNotFoundException('Не найдена компания');
            }
            return response()->json([
                "message" => "Edit Chain",
                'success' => (boolean)$chain->update($data),
                'data' => $chain ?? [],
            ]);
        }
        else{ return response()->json(['success'=> false,'error' => 'Нет прав'], 403);}
    }


    /**
     * Удалить сеть.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        if ($request->user()->level() >= 90 ) {
            $chain = Chain::where('id',$id)->where('franchise_id', Auth::user()->franchise()->id)->first();
            if(!$chain){
                throw new ModelNotFoundException('Не найдена компания');
            }
            return response()->json([
                "message" => "Delete Chain",
                'success' => (boolean)$chain->delete()
                ]);
        }
        else{ return response()->json(['success'=> false,'error' => 'Нет прав'], 403);}
    }


}
