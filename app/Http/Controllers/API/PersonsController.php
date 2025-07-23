<?php

namespace App\Http\Controllers\API;

use App\Events\DetectedUser;
use App\Helpers\ApiServiceHelper;
use App\Http\Controllers\Controller;
use App\Mail\MailReporlAllDepartment;
use App\Mail\MailReport;
use App\Models\Camera;
use App\Models\Chain;
use App\Models\Department;
use App\Models\Franchise;
use App\Models\Item;
use App\Models\Person;
use App\Models\Photo;
use App\Models\Role;
use App\Models\User;
use App\Models\Ycitem;
use App\Models\Ycrecord;
use App\Services\YcService;
use DateTime;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Ixudra\Curl\Facades\Curl;
use phpDocumentor\Reflection\Types\False_;
use Symfony\Component\Console\Input\Input;
use Illuminate\Validation\Rule;
use App\Http\Requests\UserRequest;

class PersonsController extends Controller
{
    protected $token;
    protected $user;
//    protected $chain_id;

    public function __construct(Request $request)
    {
        $this->middleware(function ($request, $next){
            $this->user = Auth::user();
            return $next($request);
        });

        //авторизация yC
        $user = (object)['login' => 'paul@strijevski.ru', 'password' => '2fz2ex'];
        $this->token = Curl::to('https://api.yclients.com/api/v1/auth')
            ->withHeader('Content-Type:application/json')
            ->withHeader('Accept:application/vnd.yclients.v2+json')
            ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2')
            ->withData($user)
            ->asJson()
            ->post();

    }

    //массив сетей принадлежащих пользователю
    public function chain_id()
    {
        return $this->user->franchise_admin_id != NULL ?
            (Chain::where('franchise_id',$this->user->franchise_admin_id)->pluck('id')) :
            (User::find($this->user->id)->chain->pluck('id'));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @return JsonResponse
     */
    //все пользователи
    public function userAll()
    {
        $persons = User::
            leftJoin('departments','users.department_id','=','departments.department_id')
            ->leftJoin('chain_user', 'users.id','=','chain_user.user_id')
            ->select(  'users.*','departments.department_name')
            ->whereIn('chid',$this->chain_id())
            ->paginate(20);

        return response()->json([
            "success" => true,
            "message" => "List Users",
            "data" => $persons
        ]);
    }


    public function showUser($id)
    {
        $person =  DB::table('users')
            ->LeftJoin('departments', 'users.department_id','=','departments.department_id')
            ->LeftJoin('role_user', 'users.id','=','role_user.user_id')
            ->LeftJoin('roles','roles.id','=', 'role_user.role_id')
            ->select('users.*', 'departments.department_name','departments.id', 'roles.role as role','roles.id as role_id')
            ->where('users.person_id', $id)->get();
        $department=['id'=>$person[0]->department_id, 'department'=>$person[0]->department_name];
        $role=[];
        foreach ($person as $item) {
            $role[]=['id'=>$item->role_id, 'role'=>$item->role];
        }
       $ycUser=Ycitem::where('person_id',$id)->get();

        $result=[
           'person_ivideon_id'=>$person[0]->person_ivideon_id,
           'face_gallery_id'=>$person[0]->face_gallery_id,
            'person_id'=>$person[0]->person_id,
            'firstname'=>$person[0]->firstname,
            'lastname'=>$person[0]->lastname,
            'fatherland'=>$person[0]->fatherland,
            'email'=>$person[0]->email,
            'department_id'=>$person[0]->department_id,
            'department_name'=>$person[0]->department_name,
            'comment'=>$person[0]->comment,
            'terminal_name'=>$person[0]->terminal_name,
            'phone'=>$person[0]->phone,
            'avatar'=>$person[0]->avatar,
            'role'=>$role,
            'department'=>$department,
            'yclients'=>$ycUser,

        ];
        return response()->json([
            "success" => true,
            "message" => "Person",
            "data" => $result
        ]);
    }

    public function updateUser(Request $request, $id)
    {
        $person = Person::where('person_id','=',$id)->first();
        $person->department_id = $request->input('department_id');
        $person->person_ivideon_id = $request->input('person_ivideon_id');
        $person->face_gallery_id = $request->input('face_gallery_id');
        $person->firstname = trim($request->input('firstname'));
        $person->lastname = trim($request->input('lastname'));
        $person->fatherland = trim($request->input('fatherland'));
        $person->email = trim($request->input('email'));
        $person->phone = preg_replace('/[^0-9]/', '', $request->input('phone',''));
        $person->comment = $request->comment;
        $person->avatar = $request->photo;
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createUserFromTerminal(Request $request, YcService $yc)
    {
        $request->validate([
              'phone' => [
                'required',
                Rule::unique('users')->ignore(\Auth::id()),
            ]
        ]);
       $data = DB::table('chains')
            ->Join('departments','departments.chain_id','=','chains.id')
            ->select('chains.id','yc_company_id')
            ->where('departments.department_id',$request->user()->department_id)
            ->first();

            $newUser = new User();
            $newUser->person_id = Str::uuid()->toString();
            $newUser->firstname = \request('firstname');
            $newUser->phone =  preg_replace('/[^0-9]/', '', \request('phone'));
            $newUser->person_ivideon_id =  \request('face_id');
            $newUser->save();
//            $item = Item::where('name', '=', 'Клиент')->first();
            $chain = Chain::where('id', '=', $data->id)->first();
//            $item->persons()->attach($newUser->person_id, ['item_id' => $item->item_id, 'updated_at' => now(), 'created_at' => now()]);
//            $newUser->syncRoles([11]);//обавляем роль client
            $chain->personHasChains()->attach($newUser->id, ['chid' => $chain->id, 'updated_at' => now(), 'created_at' => now()]);

            //авторизация yC
//            $user=(object)['login'=>'paul@strijevski.ru', 'password'=>'2fz2ex'];
//            $token = Curl::to('https://api.yclients.com/api/v1/auth')
//                ->withHeader('Content-Type:application/json')
//                ->withHeader('Accept:application/vnd.yclients.v2+json')
//                ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2')
//                ->withData($user)
//                ->asJson()
////            ->asJsonRequest()
//                ->post();
//
//            $newClient = Curl::to('https://api.yclients.com/api/v1/clients/' . $data->yc_company_id  )
//                ->withHeader('Content-Type:application/json')
//                ->withHeader('Accept:application/vnd.yclients.v2+json')
//                ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2, User ' . $token->data->user_token)
//                ->withData(['name'=>\request('name'),
//                    'phone'=>preg_replace('/[^0-9]/', '', \request('phone'))])
//                ->asJson()
////                   ->asJsonRequest()
//                ->post();
        $categoryIds = $request->category ?? [];
        $newClient = $yc->to('clients/' . $data->yc_company_id)
            ->withData(['name'=>\request('name'),
                'phone'=>preg_replace('/[^0-9]/', '', \request('phone')),
                'categories' => $categoryIds
            ],
            )
            ->asJson()->post();
        $newUser->new_yc_id = $newClient->data->id ?? 0;

        $newUser->new_yc_id = $newClient->data->id ?? 0;


        return response()->json([
            "success" => true,
            "message" => "Create User",
            "data" => $newUser
        ]);

        }



    /**
     * Персонал франшизы
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function personAll()
    {

        if (Auth::user()->level() >= 20 && Auth::user()->level() < 90 ) {
            $users = User::
            leftJoin('departments', 'users.department_id', '=', 'departments.department_id')
                ->LeftJoin('role_user', 'users.id', '=', 'role_user.user_id')
                ->leftJoin('roles', 'role_user.role_id', '=', 'roles.id')
                ->leftJoin('chain_user', 'users.id', '=', 'chain_user.user_id')
//            ->leftJoin('items', 'item_user.item_id','=','items.item_id')
                ->select('users.*', 'departments.department_name')
//            ->where('item_user.item_id','<>','b61b735c-b19b-4c00-a63e-ac9ec18f8b4b')
                ->where('roles.level', '>=', 10)
                ->WhereIn('chid', $this->chain_id())
//                ->Where('users.franchise_admin_id', $this->user->franchise_admin_id)
//                ->Where(function ($q) {
//                    $q->WhereNotNull('users.franchise_admin_id')
//                        ->Where(function ($franchise) {
//                            $franchise->where('users.franchise_admin_id', $this->user->franchise_admin_id);
//                        });
//                })

//            ->orWhere('franchise_admin_id',$this->user->franchise_admin_id)
                ->groupBy('users.id', 'franchise_admin_id')
                ->get();
        }
        if (Auth::user()->level() >= 90  ) {
            $users = User::
            leftJoin('departments', 'users.department_id', '=', 'departments.department_id')
                ->LeftJoin('role_user', 'users.id', '=', 'role_user.user_id')
                ->leftJoin('roles', 'role_user.role_id', '=', 'roles.id')
                ->leftJoin('chain_user', 'users.id', '=', 'chain_user.user_id')
//            ->leftJoin('items', 'item_user.item_id','=','items.item_id')
                ->select('users.*', 'departments.department_name')
//            ->where('item_user.item_id','<>','b61b735c-b19b-4c00-a63e-ac9ec18f8b4b')
                ->where('roles.level', '>=', 10)
                ->OrWhereIn('chid', $this->chain_id())
                ->Where(function ($q) {
                    $q->WhereNotNull('users.franchise_admin_id')
                        ->Where(function ($franchise) {
                            $franchise->where('users.franchise_admin_id', $this->user->franchise_admin_id);
                        });
                })

//            ->orWhere('franchise_admin_id',$this->user->franchise_admin_id)
                ->groupBy('users.id', 'franchise_admin_id')
                ->get();
        }
        if (Auth::user()->level() < 20) {
            return response()->json([
                'success' => false,
                'error' => 'Нет прав'
            ], 403);
        }
            return response()->json([
                "success" => (boolean)$users,
                "message" => "List Persons",
                "data" => $users ?? []
            ]);



    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    //новый персонал
    public function createPersonal(UserRequest $request)
    {
//        info(print_r($request->role, true));
//        $request->validate([
//                    'email' => [
//                'email',
//                 Rule::unique('users')->ignore(\Auth::id()),
//
//            ],
//
//              'phone' => [
//                 'required',
//                 Rule::unique('users')->ignore(\Auth::id()),
//             ]
//        ]);
//        $validatedData = $request->validated();

//        $item = Item::where('name', '=', 'Персонал')->first();
        if (Auth::user()->level() >= 20) {
           if(isset($request->franchise_admin_id) && Auth::user()->level() < 100){
                return response()->json(['error' => 'Нет прав присвоить франшизу'], 403);
            }
            $chain = Chain::where('id', '=', array_first($this->chain_id()))->first();

            $input = $request->all();
//      $input = (array)$input['allFormData'];
            if (isset($input['password']) and \Str::of($input['password'])->trim()->isNotEmpty()) {
                $input['password'] = bcrypt(trim($input['password']));
                $input['access_granted'] = 1;
            }
            if (isset($request->avatar)) {
                $ivideonUser = Curl::to('http://openapi-alpha-eu01.ivideon.com/faces?op=CREATE&access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b')
                    ->withHeader('Content-Type: application/json')
                    ->withData([
                        'face_gallery_id' => '100-GVaGUwCF2mHejrHbKykm',
                        'person' => $request->allFormData->firstname ?? 'NoneNmae',
                        'description' => $request->allFormData->phone ?? ''
                    ])
                    ->asJson()
//             ->asJsonRequest()
                    ->post();
                $input['person_ivideon_id'] = $ivideonUser->result->id;
            }
            $input['person_id'] = Str::uuid()->toString();
            $user = User::create($input);

            if (isset($request->avatar)) {
                $base64 = base64_encode(file_get_contents($request->avatar));
                $loadIvideon = Curl::to('http://openapi-alpha-eu01.ivideon.com/faces/' . $ivideonUser->result->id . '/photos?op=ADD&access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b')
                    ->withHeader('Content-Type: application/json')
                    ->withData([
                        'image_data' => $base64,
                    ])
//                                                      ->asJsonRequest()
                    ->asJson()
                    ->post();
                $newPhoto = new Photo();
                $newPhoto->user_id = $user->id;
                $filename = 'avatar_' . Str::random(5) . $request->avatar->getClientOriginalName();
                $path = 'photos/avatar/' . $user->id . '/';
                $url = env('APP_URL') . $path . $filename;
                $newPhoto->path = $url;
                $request->avatar->move(public_path() . '/' . $path, $filename);
                $newPhoto->type = 3;
                $avatar['avatar'] = $url;
                $newPhoto->save();
                User::where('id', $user->id)->update($avatar);

//отправляем фото в ивидеон

            }
//        $item->persons()->attach($user->person_id, ['item_id' => $item->item_id, 'updated_at' => now(), 'created_at' => now()]);

            $chain->personHasChains()->attach($user->id, ['chid' => $chain->id, 'updated_at' => now(), 'created_at' => now()]);


            $role = [];
            if (isset($request->role)) {
                if (!is_array($request->role)) {
                    $request->role = json_decode($request->role);
                }
                foreach ($request->role as $roleOne) {
                    $roleOne = (array)$roleOne;
                    array_push($role, $roleOne['role_id']);
                }
            }
            if (!empty($role)) {
                $user->syncRoles($role);
            } elseif (empty($role)) {
                $user->detachAllRoles();
            }

//        if(isset($request->photos)) {


//                $filename = $user->id . '_' . $imageOne->getClientOriginalName();
//                $newPhoto = new Photo();
//                $newPhoto->path = '/photos/ivideon/' . $user->id . '/' . $filename;
////                $newPhoto->user_id = $photoIvideon->id;
////                $newPhoto->ivideon_id = $user->id;
//
//                $image_resize = \Image::make($image->getRealPath());
//                $image_resize->resize(300, null, function ($constraint) {
//                    $constraint->aspectRatio();
//                });
//                $image_resize->save(public_path('/photo/clients/' . $filename));
//                $newPhoto->save();
//            }
//        }
//        }
            return response()->json([
                'success' => true,
                'ivideon_success' => $loadIvideon->success ?? '',
                'person_id' => $user ?? null
            ]);
        }
        else {
            return response()->json(['error' => 'Нет прав'], 403);
        }

    }


    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    //новый пользователь ivideon, сохранение персоны в базу
    public function CreatePerson(Request $request, $id)
    {
                $person = new Person();
                $person->person_ivideon_id = $id;
                $person->face_gallery_id = $request->face_gallery_id;
                $person->person_id = Str::uuid()->toString();
                $person->work_posts_id = 1;
//                $person->role_id = $request->role_id;
                $person->department_id = $request->input('department_id');
                $person->terminal_name = trim($request->input('terminal_name', 'Noname'));
                $person->firstname = trim($request->input('name', 'Noname'));
                $person->lastname = trim($request->input('surname', 'Noname'));
                $person->fatherland = trim($request->input('patronymic', 'Noname'));
                $person->phone = preg_replace('/[^0-9]/', '', $request->input('phone',''));
                $person->avatar = $request->photo;
                $person->save();
//                $person->item()->attach($person->person_id,['item_id' => '5a937b13-d9a2-443a-9014-1adf8cb1d450',]);

            return response()->json([
                'success' => $person->save(),
                'person_id' => $person->person_id ?? ''
                 ]);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */

    public function listStaffYc(Request $request)
    {
      $company = Department::where('department_id', $request->user()->department_id ?? 'no-id')->first();
      if(isset($request->yc_company_id)) {
          $yc_company=$request->yc_company_id;
      }
      elseif (!isset($request->yc_company_id) && isset($company->yc_company_id))
      {
          $yc_company=$company->yc_company_id;
      }
      $staffYC= Curl::to('https://api.yclients.com/api/v1/company/'.$yc_company.'/staff')
            ->withHeader('Content-Type:application/json')
            ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2, User ' . $this->token->data->user_token)
            ->asJson()
//            ->asJsonRequest()
            ->get();
      $result=[];
    foreach ($staffYC->data as $staffOne ) {
        if($staffOne->fired!=1) {
            $result[] = [
                'yc_staff_id' => $staffOne->id,
                'yc_name' => $staffOne->name,
                'specialization' => $staffOne->specialization,
                'avatar' => $staffOne->avatar,
                'avatar_big' => $staffOne->avatar_big,
            ];
        }
    }
        return response()->json([
            'success' => true,
            'staffYC' =>$result
        ]);

    }



    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }
    /**
     * Store a newly created resource in storage.
     *
     *
     *
     */
//отчет по всем сотрудникам (начало работы), с группировкой по салонам
    public function createReportSalon()
    {
         $idPerson = DB::table('users')
            ->leftJoin('departments','users.department_id','=','departments.department_id')
            ->leftJoin('item_user','item_user.person_id','=','users.person_id')
            ->select( 'person_ivideon_id', 'firstname', 'lastname', 'fatherland', 'users.person_id','departments.department_name')
            ->where('item_user.item_id','=','5a937b13-d9a2-443a-9014-1adf8cb1d450')
            ->get();

           $idSalon=DB::table('departments')
           ->Join('cameras','cameras.department_id','=','departments.department_id')
           ->select( 'departments.department_id','departments.department_name', 'cameras.camera_ivideon_id', 'cameras.camera_name')
           ->get();

        $arrPerson=[];
        foreach ($idPerson as $item){
          $arrPerson[]=['person_ivideon_id'=>$item->person_ivideon_id];
        }
//выгружаем с ivideon текущий день
          $eventAll = Curl::to('http://openapi-alpha-eu01.ivideon.com/face_events?op=FIND&access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b')
            ->withHeader('Content-Type: application/json')
            ->withData([
                "faces"=>Arr::flatten($arrPerson),
                'start_time'=>strtotime(date("Y-m-d 00:00:00")),
                'end_time'=>strtotime(date("Y-m-d 23:59:00")),
            ])
            ->asJson()
            ->post();
//выгружаем с ivideon предыдущий день
        $previousEventAll = Curl::to('http://openapi-alpha-eu01.ivideon.com/face_events?op=FIND&access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b')
            ->withHeader('Content-Type: application/json')
            ->withData([
                "faces"=>Arr::flatten($arrPerson),
                'start_time'=>strtotime(date('Y-m-d 00:00:00', strtotime(now() .' -1 day'))),
                'end_time'=>strtotime(date('Y-m-d 23:59:00', strtotime(now() .' -1 day'))),
            ])
            ->asJson()
            ->post();

        $resultTemp = [];
        foreach ($eventAll->result->items as $oneShot) {
            foreach ($idSalon as $departmetItem) {
                if ($departmetItem->camera_ivideon_id === $oneShot->camera_id) {
                    if (!isset($resultTemp [$oneShot->face_id])) {
                        $resultTemp [$oneShot->face_id] = [
                            'start_time' => date('H:i', $oneShot->best_shot_time + (7 * 3600)),
                            'end_time' => date('H:i', $oneShot->best_shot_time + (7 * 3600)),
                        ];
                    }
                    $resultTemp [$oneShot->face_id]['start_time'] = date('H:i', $oneShot->best_shot_time + 7 * 3600);
                    $resultTemp [$oneShot->face_id]['face_id'] = $oneShot->face_id;
                    $resultTemp [$oneShot->face_id]['department_name'] = $departmetItem->department_name;
                }
            }
    }
        $resultPreviousTemp =[];
        foreach ($previousEventAll->result->items as $oneShot) {
            foreach ($idSalon as $departmetItem) {
                if ($departmetItem->camera_ivideon_id === $oneShot->camera_id) {
                    if (!isset($resultPreviousTemp [$oneShot->face_id])) {
                        $resultPreviousTemp [$oneShot->face_id] = [
                            'start_time' => date('H:i', $oneShot->best_shot_time + (7 * 3600)),
                            'end_time' => date('H:i', $oneShot->best_shot_time + (7 * 3600)),
                        ];
                    }
                    $resultPreviousTemp [$oneShot->face_id]['start_time'] = date('H:i', $oneShot->best_shot_time + 7 * 3600);
                    $resultPreviousTemp [$oneShot->face_id]['face_id'] = $oneShot->face_id;
                    $resultPreviousTemp [$oneShot->face_id]['department_name'] = $departmetItem->department_name;
                }
            }
        }
        //массив данных текущего дня
        $currentResult=[];
        foreach ($idPerson as $itemperson){
            foreach ($resultTemp  as $itemEvent) {
                if ($itemperson->person_ivideon_id===$itemEvent['face_id']) {
                    $currentResult[$itemEvent['department_name']][]=[
                        'name' => $itemperson->lastname." ".$itemperson->firstname." ".$itemperson->fatherland,
                        'start_time' => $itemEvent['start_time'] ,
                        'end_time' => $itemEvent['end_time'],
                        'face_id' => $itemEvent['face_id'],
                        'department_name' => $itemEvent['department_name']
                    ];
                }
            }
        }
        //массив данных предыдущего дня
        $previousResult=[];
           foreach ($idPerson as $itemperson){
            foreach ($resultPreviousTemp  as $itemEvent) {
                if ($itemperson->person_ivideon_id===$itemEvent['face_id']) {
                    $previousResult[$itemEvent['department_name']][]=[
                        'name' => $itemperson->lastname." ".$itemperson->firstname." ".$itemperson->fatherland,
                        'start_time' => $itemEvent['start_time'] ,
                        'end_time' => $itemEvent['end_time'],
                        'face_id' => $itemEvent['face_id'],
                        'department_name' => $itemEvent['department_name']
                    ];
                }
            }
        }
           $result= [
               'previousResult'=> $previousResult,
               'currentResult' => $currentResult
           ];

        $email = ["9237857776@mail.ru"];
        Mail::to($email)->send(new MailReporlAllDepartment($result));
       }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    //отчет по всем сотрудникам (начало работы), без группировки по салонам
    public function createReportAll(Request $request)
    {
        $idPersonAll = Person::all();
        $arrPersons=[];
        foreach ($idPersonAll as $itemPerson){
            $arrPersons[]=['canera_ivideon_id'=>$itemPerson->person_ivideon_id];
        }
        $eventAll = Curl::to('http://openapi-alpha-eu01.ivideon.com/face_events?op=FIND&access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b')
            ->withHeader('Content-Type: application/json')
            ->withData([
//                "faces"=>['100-A4OhhYSfjdACvRbZEtTb', '100-UUyq8ABbyspO81S4Rgoo', '100-RWaLBEm28ih4yj0byyxY', '100-h2B258QrPDOuRlJjI7yD','100-iXlbtUVr4Ezfu4ri3ktA','100-T0irJAZEZeLxYIC1nva6','100-7wNiGFTaoJAV3AtIUFqD', '100-hfcgeWURiDUn4vqGXvZK'],
                "faces"=>Arr::flatten($arrPersons),
                'start_time'=>strtotime(date("Y-m-d 00:00:00")),
                'end_time'=>strtotime(date("Y-m-d 23:59:00")),
            ])
            ->asJson()
            ->post();

        $resultTemp = [];
        foreach ($eventAll->result->items as $oneShot) {
            if (!isset($resultTemp [$oneShot->face_id])) {
                $resultTemp [$oneShot->face_id] = [
                    'start_time' => date('d.m.Y H:i', $oneShot->best_shot_time + 7*3600),
                    'end_time' => date('d.m.Y H:i', $oneShot->best_shot_time + 7*3600),
                ];
            }
            $resultTemp [$oneShot->face_id]['start_time'] = date('d.m.Y H:i', $oneShot->best_shot_time + 7*3600);
            $resultTemp [$oneShot->face_id]['face_id'] = $oneShot->face_id;
            $resultTemp [$oneShot->face_id]['camera_id'] = $oneShot->camera_id;
        }
        $result=[];
        foreach ($idPersonAll  as $itemPerson) {
            foreach ($resultTemp  as $itemResult) {
                if($itemPerson->person_ivideon_id==$itemResult['face_id'])
                    $result[$itemResult['face_id']]=
                        [
                            'firstname' => $itemPerson['firstname'],
                            'lastname' => $itemPerson['lastname'],
                            'fatherland' => $itemPerson['fatherland'],
                            'start_time' => $itemResult['start_time'] ,
                            'end_time' => $itemResult['end_time'],
                            'face_id' => $itemResult['face_id'],
                        ];
            }
        }
        $email = ["9237857776@mail.ru"];
        Mail::to($email)->send(new MailReport($result));

    }


    /**
     * Store a newly created resource in storage.
     *
     *
     * @return JsonResponse
     */

    public function createReport($id)
    {
        $person =  DB::table('users')->where('person_id', $id)->first();
        $id_person = $person->person_ivideon_id;
        $name_person = $person->name;
        $ev = Curl::to('http://openapi-alpha-eu01.ivideon.com/face_events?op=FIND&access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b')
            ->withHeader('Content-Type: application/json')
            ->withData([
                "faces"=>[$id_person],
                'start_time'=>strtotime("2021-03-25")
            ])
            ->asJson()
//           ->asJsonRequest()
            ->post();
        $person_ev=head($ev->result->items);
        $person_ev_mod = Arr::add(['id', $name_person],'start_time',date('d.m.Y H:i', $person_ev->track_start + 7*3600));
        $result = response()->json($person_ev_mod);

        return $result;
    }

    /**
     * Store a newly created resource in storage.
     *
     *
     * @return JsonResponse
     */
    public function show($id)
    {
        $person = User::
        LeftJoin('departments', 'users.department_id', '=', 'departments.department_id')
            ->select('users.*', 'departments.department_name', 'departments.id as department_id', 'departments.yc_company_id')
            ->where('users.person_id', $id)->first();

//        $photos = User::
//        Join('photos', 'photos.user_id', '=', 'users.id')
//            ->select('photos.id as photo_id', 'photos.path as photos')
//            ->where('users.person_id', $id)
//            ->get();
        $photos = Photo::where('user_id', $person->id)->where('type', 5)->select('id', 'path', 'num')->get()->pluck('path', 'num');

        $role = User::
        Join('role_user', 'users.id', '=', 'role_user.user_id')
            ->Join('roles', 'roles.id', '=', 'role_user.role_id')
            ->select('roles.id as role_id', 'roles.name as name', 'roles.slug')
            ->where('users.person_id', $id)
            ->get();

        $franchise = $person->franchise();
        $franchise->franchise_admin_id = $franchise->id;

        $department = [];
        $yc = [];

        if ($person->department_id != NULL) {
            $department[] = ['department_id' => $person->department_id, 'department_name' => $person->department_name, 'yc_company_id' => $person->yc_company_id,];
        }
        if ($person->yc_staff_id != NULL) {
            $yc[] = [
                'yc_staff_id' => $person->yc_staff_id,
                'yc_name' => $person->yc_name,
                'avatar' => $person->avatar
            ];
        }
        $result = [
            'person_id' => $person->person_id,
            'yc_staff_id' => $person->yc_staff_id,
            'person_ivideon_id' => $person->person_ivideon_id,
            'face_gallery_id' => $person->face_gallery_id,
            'email' => $person->email,
            'firstname' => $person->firstname,
            'lastname' => $person->lastname,
            'fatherland' => $person->fatherland,
            'comment' => $person->comment,
            'phone' => $person->phone,
            'terminal_name' => $person->terminal_name,
            'avatar' => $person->avatar,
            'role' => $role,
            'department' => $department,
            'yc' => $yc,
            'photos' => $photos,
            'franchises'=> $franchise
        ];

        return response()->json([
            "success" => true,
            "message" => "Person",
            "data" => $result
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        info(print_r($request->all(), true));
//        exit;
//        info((array)$request->allFormData);
//        $request->validate([
//             'phone' => [
//               Rule::unique('users')->ignore(\Auth::id()),
//            ]
//        ]);
        if (Auth::user()->level() >= 20) {

            if (!empty($request->franchises) && Auth::user()->level() < 100) {
                if(Auth::user()->franchise_admin_id != $request->franchises['franchise_admin_id'])
                return response()->json(['error' => 'Нет прав изменить франшизу'], 403);
            }
         $user = User::
            Leftjoin('item_user', 'item_user.person_id', '=', 'users.person_id')
                ->LeftJoin('role_user', 'users.id', '=', 'role_user.user_id')
                ->leftJoin('roles', 'role_user.role_id', '=', 'roles.id')
                ->Leftjoin('items', 'items.item_id', '=', 'item_user.item_id')
                ->select('users.*', 'items.name as items','roles.level')
                ->where('users.person_id', '=', $id)
                ->first();
//            if(empty($user->level)){
//                return 1;
//            }
//            return $user->person_ivideon_id;
            $input = $request->all();
            $face_id= $user->person_ivideon_id;
            //добавляем фото в BIS и ивидеон
//        if (!empty($request->photo)) {
            $files = $request->photo;
////                //если нет у пользователя person_ivideon_id - создаем face_id в ивидеон и загружаем фото
//                if (empty($user->person_ivideon_id)) {
//                    if($user->items=='Клиент' || $user->items==NULL){
//                        $face_gallery_id='100-5Dzg4Q2nKpvqKd9Vu52B';
//                    }
//                    if($user->items=='Персонал'){
//                        $face_gallery_id='100-GVaGUwCF2mHejrHbKykm';
//                    }
//                    $photoIvideon = Curl::to('http://openapi-alpha-eu01.ivideon.com/faces?op=CREATE&access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b')
//                        ->withHeader('Content-Type: application/json')
//                        ->withData([
//                            'person'=>$user->firstname ?? "Noname",
//                            'face_gallery_id'=>$face_gallery_id,
//                            'description'=>$user->phone ?? NULL,
//                        ])
////                        ->asJsonRequest()
//                        ->asJson()
//                        ->post();
//                    $face_id=$photoIvideon->result->id;
//                }
//               foreach ($files as $file => $image) {
//                $base64 = base64_encode(file_get_contents($image));
//                       //загружаем фото в ивидеон
//                Curl::to('http://openapi-alpha-eu01.ivideon.com/faces/' . $face_id . '/photos?op=ADD&access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b')
//                    ->withHeader('Content-Type: application/json')
//                    ->withData([
//                        'image_data' => $base64,
//                    ])
//                    ->asJson()
//                    ->post();
//                $filename = $file . '_' . $image->getClientOriginalName();
//                $newPhoto = new Photo();
//                $newPhoto->user_id = $user->id;
//                $newPhoto->path = 'https://bis.zdato.ru/photos/ivideon/' . $user->id . '/' . $filename;
//                //сжатие
////              $newPhoto->user_id = $photoIvideon->id;
////              $newPhoto->ivideon_id = $user->id;
////    $image_resize = \Image::make($image->getRealPath());
////    $image_resize->resize(300, null, function ($constraint) {
////        $constraint->aspectRatio();
////    });
//                $image->move(public_path() . '/photos/ivideon/' . $user->id . '/', $filename);
//                $result[] = [$file => $newPhoto->save()];
//            }
//                if(isset($photoIvideon)){
//                    $input['person_ivideon_id']=$photoIvideon->result->id;
//                }

//    }
            if (isset($input['email'])) {
                if (trim($input['email']) == $user->email) {
                    if (isset($input['password']) and \Str::of($input['password'])->trim()->isNotEmpty()) {
                        $input['password'] = bcrypt(trim($input['password']));
                        $input['access_granted'] = 1;
                    } elseif (empty($input['password'])) {
                        unset($input['password']);
                    }
                } elseif (trim($input['email']) != $user->email) {
                    $count = User::where('email', '=', $input['email'])->count();
                    if ($count > 0) {
                        return response()->json(['error' => 'Email занят'], 409);
                    }
                    if ($count == 0) {
                        if (isset($input['password']) and \Str::of($input['password'])->trim()->isNotEmpty()) {
                            $input['password'] = bcrypt(trim($input['password']));
                            $input['access_granted'] = 1;
                        } elseif (empty($input['password'])) {
                            unset($input['password']);
                        }
                    }

                }
            }
            if (isset($input['phone'])) {
                $input['phone'] = '7' . substr(preg_replace('/[^0-9]/', '', $input['phone']), -10);
            }
            if (!empty($request->photosObj)) {
                $face_gallery_id = '100-GVaGUwCF2mHejrHbKykm';
                if ($user->person_ivideon_id != NULL) {
                    $face_id = $user->person_ivideon_id;
                    if ($user->level == NULL) {
                        $face_gallery_id = '100-GVaGUwCF2mHejrHbKykm';
                    }
                }
                if (empty($user->person_ivideon_id) && !empty($user->level)) {

                    $photoIvideon = Curl::to('http://openapi-alpha-eu01.ivideon.com/faces?op=CREATE&access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b')
                        ->withHeader('Content-Type: application/json')
                        ->withData([
                            'person' => $user->firstname ?? "Noname",
                            'face_gallery_id' => $face_gallery_id,
                            'description' => $user->phone ?? NULL,
                        ])
//                        ->asJsonRequest()
                        ->asJson()
                        ->post();
                     $face_id = $input['person_ivideon_id'] = $photoIvideon->result->id;
                }
                if (!empty($user->person_ivideon_id)){
                    $face_id = $user->person_ivideon_id;
                }

                $photos = Photo::where('type', 5)->where('user_id', $user->id)->get()->keyBy('num') ?? [];
                foreach ($request->photosObj as $photoNum => $photoOne) {
                    if (isset($photos[$photoNum])) {
                        try {
                            $photoFile = explode('/photos/ivideon/', $photos[$photoNum]->path)[1] ?? null;
                            unlink(public_path() . '/photos/ivideon/' . $photoFile);
                        } catch (\Throwable $e) {}
                        $photos[$photoNum]->delete();
                    }

                    $base64 = base64_encode(file_get_contents($photoOne->getPathName()));
                    //загружаем фото в ивидеон
                    $loadIvideon = Curl::to('http://openapi-alpha-eu01.ivideon.com/faces/' . $face_id . '/photos?op=ADD&access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b')
                        ->withHeader('Content-Type: application/json')
                        ->withData([
                            'image_data' => $base64,
                        ])
                        ->asJson()
                        ->post();

                    $newPhoto = new Photo();
                    $newPhoto->user_id = $user->id;
                    $filename = 'ivideon_' . Str::random(5) . $photoOne->getClientOriginalName();
                    $path = 'photos/ivideon/' . $user->id . '/';
                    $url = env('APP_URL') . $path . $filename;
                    $newPhoto->path = $url;
                    $photoOne->move(public_path() . '/' . $path, $filename);
                    $newPhoto->type = 5;
                    $newPhoto->num = $photoNum;
                    $newPhoto->type_title = 'фото для Ivideon';
//                   $input['avatar'] = $url;
                    $newPhoto->save();
                }
            }
            if(isset($request->photo)){
                $newPhoto = new Photo();
                $newPhoto->user_id = $user->id;
                $filename = 'avatar_' . Str::random(5) . $request->photo->getClientOriginalName();
                $path = 'photos/avatar/' . $user->id . '/';
                $url = env('APP_URL') . $path . $filename;
                $newPhoto->path = $url;
                $request->photo->move(public_path() . '/' . $path, $filename);
                $newPhoto->type = 4;
                $newPhoto->type_title = 'аватар пользователя';
                $input['avatar'] = $url;
                $newPhoto->save();
            }
            $role = [];
            if (isset($request->role)) {
                foreach ($request->role as $roleOne) {
                    array_push($role, $roleOne['role_id']);
                }
            }
            if (!empty($role)) {
                $user->syncRoles($role);
            } elseif (empty($role)) {
                $user->detachAllRoles();
            }
            if(!empty($request->franchises)){
                $input['franchise_admin_id']=$request->franchises['franchise_admin_id'];
            }
            return response()->json([
                'success' => $user->update($input) ?? false,
                'ivideon_success' => $loadIvideon->success ?? '',
                'person' => $user ?? ''
            ]);
        }
        else {
                return response()->json(['error' => 'Нет прав'], 403);
            }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
       $user=Person::where('person_id',$id)->first();
       $user->person_ivideon_id=NULL;
       $user->face_gallery_id=NULL;
        $user->save();
        return response()->json([
            'success' => $user->delete(),
            'person_id' => $user->person_id ?? ''
        ]);
    }

    public function showUserByPhone(Request $request)
    {
        $phone = trim(\request('phone', ''));
        if (!$phone)
            abort(404);
        $result = User
            ::where('phone', 'like', '%' . $phone . '%')
            ->first();
        if(!$result ){
            return response()->json([
                "success" => false,
                "message" => "user",
                "data" => $result
            ]);
        }
//        \App\Jobs\CreateClientSalon::dispatch(
//            [
//                'phone'=>$phone,
//                'department_id'=>$request->user()->department_id
//            ]
//        );
        $face_id=[];
        if($result && empty($result->person_ivideon_id)) {
            $face_id['showUserByPhone']=\request('face_id');
//            info($face_id['showUserByPhone']);
            $result->update([
                'person_ivideon_id' => \request('face_id')
            ]);
            return response()->json([
                "success" => true,
                "message" => "User",
                "data" => $result
            ]);
        }
            return response()->json([
                "success" => true,
                "message" => "user",
                "data" => $result
            ]);
    }
    public function showUserSearch()
    {
        $search=\request('search', '');
        $result = User
            ::where('firstname', 'like', '%' . $search . '%')
            ->orWhere('lastname', 'like', '%' . $search . '%')
            ->orWhere('fatherland', 'like', '%' . $search . '%')
            ->orWhere('phone', 'like', '%' . $search . '%')
            ->orWhere('email', 'like', '%' . $search . '%')
            ->paginate(20);
        return response()->json([
            "success" => true,
            "message" => "Person",
            "data" => $result
        ]);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function chainUsersFromService(Request $request)
    {
        if(Auth::user()->level() >= 20) {
            $pool = 'abcdefghijklmnopqrstuvwxyz';
            $api = ApiServiceHelper::api([
                'token' => $request->token ?? '',
                'login' => $request->login ?? '',
                'password' => $request->password ?? '',
            ]);
            if (empty($api->chainStaff()['success'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Неверный токен',
                ], 406);
            } elseif (!empty($api->chainStaff()['success'])) {
                $result = [];
                $chain =  Chain::find($request->id);
                foreach ($api->chainStaff()['data'] as $staffOne){
                    $user = User::where('yc_staff_id',$staffOne['yc_staff_id'])->first();
                    if(!empty($user) && !empty($user->chains()->where('chid',$chain->id)->get())) {continue;}
                       $result[]=[
                        'chain_id'=>$chain->id,
                        'yc_staff_id'=> $staffOne['yc_staff_id'] ?? '',
                        'firstname'=> $staffOne['firstname'] ?? '',
                        'lastname'=> $staffOne['lastname'] ?? '',
                        'fatherland' => $staffOne['fatherland'] ?? '',
                        'email' => $staffOne['email'] ?? '',
                        'phone' => $staffOne['phone'] ?? '',
//                        'invite_code' => Str::lower(Str::random(6)),
                        'invite_code' => substr(str_shuffle(str_repeat($pool, 8)), 0, 6)
                    ];
                }
                return response()->json([
                    'message' => "List Users from Service",
                    'success'=>(boolean)$result,
                    'data' => $result ?? []
                ], 200);
            }
        }
        else{
            return response()->json(['error' => 'Нет прав'], 403);
        }
    }

    public function chainUsersFromServiceStore(Request $request)
    {
        if(Auth::user()->level() >= 20) {
            $result=[];
            if(empty($request->users)){
                return response()->json([
                    'success'=>false,
                    'error' => 'Вы не выбрали сотрудников'
                ], 406);
            }
            if(isset($request->users)){
                $role = config('roles.models.role')::where('slug', 'student')->first();
                foreach ($request->users as $userOne){
                    $data=[];
                    $data['person_id']=Str::uuid()->toString();
                    $data['yc_staff_id']=$userOne['yc_staff_id'];
                    $data['firstname']=$userOne['firstname'];
                    $data['lastname']=$userOne['lastname'];
                    $data['fatherland']=$userOne['fatherland'];
                    $data['email']=$userOne['email'];
                    $data['phone']=$userOne['phone'];
                    $data['invite_code']=$userOne['invite_code'];
                    $result[] = $user = User::create($data);
                    $user->chains()->attach($user->id, ['chid' => $userOne['chain_id'], 'updated_at' => now(), 'created_at' => now()]);
                    $user->attachRole($role);
                }
            }
            return response()->json([
                'message' => "Save Users from Service",
                'success'=>(boolean)$result,
                'data' => $result ?? []
            ], 200);
        }
    }


    /**
     * Список студентов
     *
     */
    public function studentList()
    {
        if (Auth::user()->level() >= 20) {
            $users = User::
            leftJoin('departments', 'users.department_id', '=', 'departments.department_id')
                ->LeftJoin('role_user', 'users.id', '=', 'role_user.user_id')
                ->leftJoin('roles', 'role_user.role_id', '=', 'roles.id')
                ->leftJoin('chain_user', 'users.id', '=', 'chain_user.user_id')
                ->select('users.*', 'departments.department_name')
                ->where('roles.slug', '=', 'student')
                ->where(function ($q) {
                    $q->whereIn('chid', $this->chain_id())
                        ->orWhere(function ($franchise) {
                            $franchise->where('users.franchise_admin_id', $this->user->franchise_admin_id);
                        });
                })
                ->groupBy('users.id', 'franchise_admin_id')
                ->get();

            return response()->json([
                "success" => (boolean)$users,
                "message" => "List Students",
                "data" => $users ?? []
            ]);
        } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Нет прав'
                ], 403);
            }


    }



}
