<?php

namespace App\Http\Controllers\API;

use App\Helpers\ApiServiceHelper;
use App\Http\Controllers\Controller;
use App\Models\Chain;
use App\Models\Department;
use App\Models\Terminal;
use App\Services\YcService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Ixudra\Curl\Facades\Curl;
use jessedp\Timezones\Facades\Timezones;
use Psy\Util\Json;
use function PHPUnit\Framework\isNull;

class DepartmentsController extends Controller
{
    /**
     * Точки (салоны) пользоватля
     * @return JsonResponse
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
         if(Auth::user()->level() >= 90) {
             return response()->json([
                 "message" => "List Departments",
                 'data' => Department::where('franchise_id', Auth::user()->franchise()->id)->get()
             ]);
         }
             if(Auth::user()->level() >= 20 && Auth::user()->level() < 90) {
                 return response()->json([
                     "message" => "List Departments",
                     'data'=>Department::where('chain_id',Auth::user()->chain->first()->id)->get()
                 ]);
             }


          if(Auth::user()->level() < 20){return response()->json(['success'=>false,'error' => 'Нет прав'], 403);}

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        if(Auth::user()->level() >= 20) {
            $data = $request->all();
            $data['franchise_id']=$request->user()->franchise()->id;
            $data['department_id']=Str::uuid()->toString();
            if(empty($data['chain_id'])){
                $data['chain_id'] = $request->user()->chains[0]->id;
            }
            $department = Department::create($data);
            return response()->json([
                "message" => "Create Department",
                'success' => (boolean)$department,
                'data' => $department
            ]);
        }
        else{
            return response()->json(['error' => 'Нет прав'], 403);
        }

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show($id)
    {
        if(Auth::user()->level() >= 20 || Auth::user()->hasRole('terminal')) {
            if ($id) {
                $department = Department
                    ::where('department_id', $id)
                    ->where('franchise_id', Auth::user()->franchise()->id)
                    ->first();
                $department->terminal = Terminal::where('department_id', $department->id)->first();
                if (!$department->terminal) {
                    $department->terminal = Terminal::create([
                        'department_id' => $department->id,
                        'name' => $department->department_name,
                        'adress' => $department->department_address,
                        'yc_account_card' => 0,
                        'yc_account_cash' => 0,
                    ]);
                }
                return response()->json([
                    'success' => (boolean)$department,
                    'data' => $department ?? []
                ]);
            }
        }
        else{return response()->json(['error' => 'Нет прав'], 403);}
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        if(Auth::user()->level() >= 20) {
            $req = $request->all();
            Terminal::where('department_id', $req['department']['id'] ?? 0)->update([
                'yc_account_card' => $req['ycAccountCard'] ?? 0,
                'yc_account_cash' => $req['ycAccountCash'] ?? 0,
                'camera_id' => (int)($req['cameraId'] ?? 0) ? ($req['cameraId'] ?? 0) : NULL,
                'cash_port' => $req['department']['terminal']['cash_port'] ?? '',
            ]);

          $department = Department::where('id', $req['department']['id'] ?? 0)->update([
                'timezone_title' => $req['timezoneTitle'] ?? NULL,
                'timezone_offset' => $req['timezoneOffset'] ?? NULL,
                'city' => $req['city'] ?? NULL,
                'coordinates' => $req['coordinates'] ?? NULL,
                'department_address' => $req['address'] ?? '',
                'department_name' => $req['showAs'] ?? '',
            ]);
            return response()->json([
                'success' => (boolean)$department,
                'data' => $department ?? []
            ]);
        }
        else{
            return response()->json(['error' => 'Нет прав'], 403);
        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $department = Department::
                    where('department_id', $id);
        $department->delete();
    }

    public function ycAccounts(Request $request, YcService $yc)
    {
       // $company = Department::where('department_id', $request->user()->department_id ?? 'no-id')->first();
        return response()->json(
            (array)$yc->to('accounts/' . $request->yc_company_id)->asJson()->get()
        );
    }

    /**
     * Список временных зон
     * @return \Illuminate\Http\JsonResponse
     */
    public function listTimezone()
    {
        $result=[];
        $timezones=Timezones::toArray();
        foreach ($timezones as $timezoneOne=>$value){
            if($timezoneOne=='Asia'||$timezoneOne=='Europe'){
                $result[]=$value;
            }
        }
        return response()->json([
            'message' => "List Timezones",
            'data' => array_merge(...$result)
        ], 200);
    }

    /**
     * Список список городов из Airtable
     * @return \Illuminate\Http\JsonResponse
     */
    public function listCities()
    {
       $result=[];
       $cities=Curl::to('https://api.airtable.com/v0/appSTpB9sE7Qhfslu/region_db')
            ->withHeader('Authorization:Bearer keyVWFtF8wCTJ6gjs')
            ->asJson()
            ->get();
        if($cities){
          foreach ($cities->records as $cityOne){
            $result[]=[
                'id'=>$cityOne->id,
                'region'=>$cityOne->fields->region,
                'capital'=>$cityOne->fields->capital
            ];
          }
        }
        return response()->json([
            'message' => "List Cities from Airtable",
            'data' => $result ?? []
        ], 200);
    }

    /**
     * Список список салонов из Yc
     * @return \Illuminate\Http\JsonResponse
     */
    public function departmentsFromYc(YcService $yc)
    {
       $departments= $yc->to('companies/')
            ->withData([
                'my' => 1,
               ])
            ->asJson()
           ->get();
       $departments->data;
       $result=[];
       if($departments->success==true) {
           foreach ($departments->data as $departmentOne) {
               $result[] = [
                   'id' => $departmentOne->id,
                   'title' => $departmentOne->title,
                   'public_title' => $departmentOne->public_title,
                   'short_descr' => $departmentOne->short_descr,
                   'city' => $departmentOne->city,
                   'timezone' => $departmentOne->timezone,
                   'address' => $departmentOne->address,
                   'coordinate_lat' => $departmentOne->coordinate_lat,
                   'coordinate_lon' => $departmentOne->coordinate_lon,
                   'app_ios' => $departmentOne->app_ios,
                   'app_android' => $departmentOne->app_android
               ];
           }
       }
        return response()->json([
            'message' => "List departments from YClients",
            'success'=>$departments->success,
            'data' => $result ?? []
        ], 200);
    }

    /**
     * проверка токена для интеграционной системы, вывод точек из интеграционной системы
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function chainDepartmentsFromService(Request $request)
    {
        info(print_r($request->all(), true));
        if(Auth::user()->level() >= 20) {
             $api = ApiServiceHelper::api([
                    'token' => $request->token ?? '',
                    'login' => $request->login ?? '',
                    'password' => $request->password ?? '',
                ]);
//        return $api->chainDepartments();
                if (empty($api->chainDepartments()['success'])) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Неверный токен',
                    ], 406);
                } elseif (!empty($api->chainDepartments()['success'])) {
//                    return $api->chainDepartments();

                    $result = [];
                    $chain =  Chain::find($request->chain_id);
                    foreach ($api->chainDepartments()['data'] as $departmenOne){
                        $department = Department::where('yc_company_id',$departmenOne['yc_company_id'])->first();
                        if (!empty($department)) continue;
                        $result[]=[
                          'franchise_id'=>$chain->franchise_id,
                          'chain_id'=>$chain->id,
                          'chain_name'=> $chain->name,
                          'department_name'=> $departmenOne['department_name'],
                          'yc_company_id' => $departmenOne['yc_company_id'],
                          'department_address' => $departmenOne['department_address']
                        ];
                    }
                    return response()->json([
                        'message' => "List departments from Service",
                        'success'=>(boolean)$result,
                        'data' => $result ?? []
                    ], 200);
                }
        }
        else{
            return response()->json(['error' => 'Нет прав'], 403);
        }
    }

    /**
     * массовое добавление точек из интеграционной системы
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function chainDepartmentsFromServiceStore(Request $request)
    {
        info(print_r($request->all(), true));
        if(Auth::user()->level() >= 20) {
            $result=[];
            if(empty($request->departments)){
                return response()->json([
                    'success'=>false,
                    'error' => 'Вы не выбрали точки'
                ], 406);
            }
            if(isset($request->departments)){
                foreach ($request->departments as $departmentOne){
                    $data=[];
                    $data['department_id']=Str::uuid()->toString();
                    $data['franchise_id']=$departmentOne['franchise_id'];
                    $data['chain_id']=$departmentOne['chain_id'];
                    $data['department_name']=$departmentOne['department_name'];
                    $data['yc_company_id']=$departmentOne['yc_company_id'];
                    $data['department_address']=$departmentOne['department_address'];
                    $result[] = Department::create($data);
                }
            }
            return response()->json([
                'message' => "Save departments from Service",
                'success'=>(boolean)$result,
                'data' => $result ?? []
            ], 200);
        }
        else {
            return response()->json(['error' => 'Нет прав'], 403);
        }
    }
}
