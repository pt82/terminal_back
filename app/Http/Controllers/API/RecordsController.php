<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Chain;
use App\Models\Department;
use App\Models\History;
use App\Models\Item;
use App\Models\Person;
use App\Models\Photo;
use App\Models\Report;
use App\Models\Typeform;
use App\Models\User;
use App\Models\Ycitem;
use App\Models\Ycrecord;
use App\Models\Yctransaction;
use App\Services\TerminalRecordService;
use App\Services\YcService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Ixudra\Curl\Facades\Curl;
use React\Dns\Model\Record;
use function Faker\Provider\pt_BR\check_digit;

class RecordsController extends Controller
{
    protected $user_id=[];
    protected $page;
    protected $date_begin;
    protected $date_end;
    protected $perPage = 20;
    protected $userId;
    protected $token;
    protected $chains;


    public function __construct(Request $request)
    {
        //авторизация yC
//        $user=(object)['login'=>'paul@strijevski.ru', 'password'=>'2fz2ex'];
        $user=(object)['login'=>'79501500958', 'password'=>'TIM1986Dolgov'];
        $this->token= Curl::to('https://api.yclients.com/api/v1/auth')
            ->withHeader('Content-Type:application/json')
            ->withHeader('Accept:application/vnd.yclients.v2+json')
            ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2')
            ->withData($user)
            ->asJson()
            ->post();

        //сети доступные пользователю
        $this->chains = DB::table('chain_user')
            ->join('chains', 'chains.id', '=', 'chain_user.chid')
            ->where('user_id', \Auth::id())
            ->pluck('chains.id');

        $this->userId = \Auth::id();

        $this->page = $request->input('page', \Cache::get('page_' .$this->userId, function () {
            return 1;
        }));
        \Cache::forever('page_' . $this->userId, $this->page);

        $this->date_begin = $request->input('date_begin', \Cache::get('date_begin_' .$this->userId, function () {
            return date('2015-01-01 00:00:00');
        }));
        \Cache::forever('date_begin_'. $this->userId, $this->date_begin);

        $this->date_end = $request->input('date_end', \Cache::get('date_end_' .$this->userId, function () {
            return date('Y-m-d 23:59:00');
        }));
        \Cache::forever('date_end_'. $this->userId, $this->date_end);


   }


    public function personOneRecord(Request $request)
    {
//           if(!($request->user()->hasRole(['operator', 'Admin']))){
//            abort(404);
//        }
        $listRecord = DB::table('ycrecords')
              ->selectRaw('ycrecords.user_id, count(user_id) as total')
              ->groupBy('user_id')
              ->having('total', '=', 1)
              ->get();

          $idUser = [];
          foreach ($listRecord as $recordOne) {
              array_push($idUser, $recordOne->user_id);
          }
      $result =   DB::table('ycrecords')
              ->leftJoin('users', 'ycrecords.user_id', '=', 'users.id')
              ->leftJoin('departments', 'ycrecords.department_id', '=', 'departments.id')
              ->leftJoin('v_user_record', 'v_user_record.id', '=', 'ycrecords.user_id')
              ->select('ycrecords.id as record_id', 'v_user_record.firstname', 'v_user_record.phone', 'v_user_record.activity', 'v_user_record.age', 'v_user_record.period_haircut', 'v_user_record.software', 'v_user_record.rating','users.comment','users.time_ring','users.time_remind','ycrecords.attendance', 'ycrecords.date', 'ycrecords.staff->name as master', 'departments.department_name as department')
              ->whereIn('v_user_record.id', $idUser)
              ->whereBetween('ycrecords.date', [$this->date_begin, $this->date_end])
              ->whereNull('users.success_data')
              ->orderBy('ycrecords.date', 'desc')
              ->paginate($this->perPage, ['*'], 'page', intval($this->page));


          return response()->json([
              "success" => true,
              "message" => "Client with one visit",
              "data" => $result
          ], 200);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function personOneRecordUpdate(Request $request, $id)
    {
        $input = $request->all();

        $ycRecord=Ycrecord::find($id);
        $client=User::find($ycRecord->user_id);
        if(isset($input['age'])) {
            $input['near_brday'] = date('Y-m-d', strtotime("-" . intval($input['age']) . " year"));
        }
       if(!isset($input['time_ring'])){
           $input['success_data']=1;
       }
       if(isset($input['comment'])) {
           $input['comment'] = $client->comment . ' ' . $input['comment'];
       }
       if(isset($input['period_haircut'])){

        $input['time_remind']=date('Y-m-d', strtotime("+" . intval($request->period_haircut['day']) . " day",$ycRecord->data));}

        return response()->json([
            'success' =>$client->update($input),
            'user_id' => $client->person_id ?? ''
        ],200);
    }


    public function recordsAll()
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function userRecord(Request $request)
    {
        $user=User::select('id','firstname')->where('phone',$request->phone)->first();
        $record=DB::table('ycrecords')
           ->leftJoin('departments', 'ycrecords.department_id','=','departments.id')
            ->select('ycrecords.id','ycrecords.date','ycrecords.staff->name as master', 'departments.department_name as department')
            ->where('ycrecords.user_id', $user->id)
            ->where('ycrecords.attendance','=',0)
            ->get();
       return $result=[
            'firstname'=>$user->firstname,
            'record'=>$record
        ];

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
//Услуги YC
    public function servicesYc(Request $request)
    {

        $companyId=Department::where('department_id',$request->user()->department_id)->first();
//         $servicesYc = Curl::to('https://api.yclients.com/api/v1/company/'.$companyId->yc_company_id.'/services/') //выводим все услуги
        $byStaffId = ($request->staff_id ?? '') ? ('?staff_id=' . $request->staff_id) : '';
        $servicesYc = Curl::to('https://api.yclients.com/api/v1/book_services/'.$companyId->yc_company_id . $byStaffId) //только услуги для онлайн записи
            ->withHeader('Content-Type:application/json')
            ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2, User ' . $this->token->data->user_token)
//                                     ->asJsonRequest()
          ->asJson()
          ->get();
        $services=[];
//        foreach ($servicesYc->data as $servicesYcOne){//выводим все услуги
        foreach ($servicesYc->services as $servicesYcOne){ //только услуги для онлайн записи
            $services[]=[
                'id'=>$servicesYcOne->id,
                'title'=>$servicesYcOne->title,
                'price_min'=>$servicesYcOne->price_min,
                'price_max'=>$servicesYcOne->price_max,
//                'staff'=>$servicesYcOne->staff[0] //выводим все услуги
            ];
        }

        return response()->json([
            'success' =>true,
            'services' => $services
        ],200);
    }


    /**
     * Вывод ближайшего свободного времени мастеров на терминале.
     *
     * @param Request $request
     * @return JsonResponse
     */

    public function servicesYcSelect (Request $request)
    {
     $companyId=Department::where('department_id',$request->user()->department_id)->first();
     $staffYC = Curl::to('https://api.yclients.com/api/v1/book_staff/' . $companyId->yc_company_id )
            ->withHeader('Content-Type:application/json')
            ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2, User ' . $this->token->data->user_token)
            ->withData(['service_ids'=>$request->service_ids])
            ->asJson()
            ->get();
        $count=0;
        $result=[];
        foreach ($staffYC as $staffOne) {
            $count++;
            if($count>10) break;
           $freetime = Curl::to('https://api.yclients.com/api/v1/book_staff_seances/' . $companyId->yc_company_id . '/'.$staffOne->id)
                ->withHeader('Content-Type:application/json')
                ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2, User ' . $this->token->data->user_token)
                ->withData(['service_ids'=>$request->service_ids])
                ->asJson()
                ->get();
           if($freetime) {
               foreach ($freetime->seances as $freetimeOne) {
                   $result[] = [
                       'staff_id' => $staffOne->id,
                       'name' => $staffOne->name,
                       'specialization' => $staffOne->specialization,
                       'avatar' => $staffOne->avatar,
                       'avatar_big' => $staffOne->avatar_big,
                       'services_id' => $request->service_id,
                       'seance_length' => $freetimeOne->sum_length,
                       'date' => date('d.m.Y', strtotime($freetimeOne->datetime)),
                       'time' => $freetimeOne->time,
                       'datetime' => $freetimeOne->datetime,
                   ];
                   break;
               }
           }
//           elseif (!$freetime->seances){
//               return response()->json([
//                   'success' =>false,
//                   'error' =>  'not seances'
//               ],401);
//           }

        }
        return response()->json([
            'success' =>true,
            'servicesDate' =>  $result
        ],200);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */

    public function exactServicesYcSelect(Request $request)
    {
        $companyId=Department::where('department_id',$request->user()->department_id)->first();
       return $staffYC = Curl::to('https://api.yclients.com/api/v1/hooks_settings/' . $companyId->yc_company_id  )
            ->withHeader('Content-Type:application/json')
            ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2, User ' . $this->token->data->user_token)
                                    ->asJsonRequest()
//            ->asJson()

            ->get();

        $date=[Carbon::now('Asia/Novosibirsk')->format('Y-m-d'),Carbon::now('Asia/Novosibirsk')->add(1, 'day')->format('Y-m-d'),Carbon::now('Asia/Novosibirsk')->add(2, 'day')->format('Y-m-d') ];
//        info($request);exit;


        //мастер из yc
        $staffYC = Curl::to('https://api.yclients.com/api/v1/company/' . $companyId->yc_company_id . '/staff/' )
            ->withHeader('Content-Type:application/json')
            ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2, User ' . $this->token->data->user_token)
//                                    ->asJsonRequest()
            ->asJson()
            ->get();
        $res=[];

       $servicesYcId = Curl::to('https://api.yclients.com/api/v1/company/'.$companyId->yc_company_id.'/services/'.$request->service_id)
            ->withHeader('Content-Type:application/json')
            ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2, User ' . $this->token->data->user_token)
            ->asJson()
//                           ->asJsonRequest()
            ->get();
         $result=[];
         $count=0;
        $countinterval=0;

        $time='';
        foreach ($date as $dateOne){
        foreach ($servicesYcId->data->staff[0] as  $staffIdOne) {

//            $servicesDateOne = Curl::to('https://api.yclients.com/api/v1/timetable/seances/' . $companyId->yc_company_id . '/' . $staffIdOne->id . '/' . \Carbon\Carbon::now('Asia/Novosibirsk')->format('Y-m-d')) //текущий день
            $servicesDateOne = Curl::to('https://api.yclients.com/api/v1/timetable/seances/' . $companyId->yc_company_id . '/' . $staffIdOne->id . '/' . $dateOne)
                ->withHeader('Content-Type:application/json')
                ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2, User ' . $this->token->data->user_token)
                ->asJson()
//                                         ->asJsonRequest()
                ->get();


            $interval = $staffIdOne->seance_length / 300;
            if ($servicesDateOne) {
                foreach ($servicesDateOne as $timeFree) {
                    if ($timeFree->is_free == false) {
                        $countinterval = 0;
                    }
                    if ($timeFree->is_free == true && date($timeFree->time) >= \Carbon\Carbon::now('Asia/Novosibirsk')->format('H:i')) {
                        $countinterval++;
                        if ($countinterval == $interval) {
                            $time = date('H:i', strtotime($timeFree->time . '-' . intval($staffIdOne->seance_length - 300) . ' seconds'));
                            break 1;
                        }
                    }
                }
            }
            //мастер из yc
            $staffYC = Curl::to('https://api.yclients.com/api/v1/company/' . $companyId->yc_company_id . '/staff/' . $staffIdOne->id)
                ->withHeader('Content-Type:application/json')
                ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2, User ' . $this->token->data->user_token)
                ->asJson()
                ->get();

            if ($servicesDateOne && $count < 5) {
                $count++;
                if ($time != "") {
                    $result[] = [
                        'staff_id' => $staffYC->data->id,
                        'name' => $staffYC->data->name,
                        "specialization" => $staffYC->data->specialization,
                        'avatar' => $staffYC->data->avatar,
                        'avatar_big' => $staffYC->data->avatar_big,
                        'services_id' => $request->service_id,
                        'seance_length' => $staffIdOne->seance_length,
//                        'date' => \Carbon\Carbon::now('Asia/Novosibirsk')->format('d.m.Y'),
                        'date' => $dateOne,
                        'time' => $time,
                        'datetime' => date('Y-m-d\TH:i:00+0' . \Carbon\Carbon::now('Asia/Novosibirsk')->tzAbbrName . ':00', strtotime(\Carbon\Carbon::now('Asia/Novosibirsk')->format('Y-m-d') . $time)),
//                        'datetime' => date('Y-m-d\TH:i:00+0' . \Carbon\Carbon::now('Asia/Novosibirsk')->tzAbbrName . ':00', strtotime($dateOne . $time)),
//                    'jur'=>$servicesDateOne
                    ];
                }
            }
        }
       }

        return response()->json([
            'success' =>true,
            'servicesDate' =>  $result
        ],200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @param TerminalRecordService $terminalRecordService
     * @return JsonResponse
     */

    public function addRecordYc(Request $request, TerminalRecordService $terminalRecordService, YcService $yc)
    {
        $companyId = Department::where('department_id', $request->user()->department_id)->first();
        $serviceIds = [];
        foreach ($request->services as $serviceId => $service) {
            $serviceIds[] = [
                'id' => $serviceId
            ];
        }
        //запись на время
        $newRecord=$yc->to('records/'.$companyId->yc_company_id)
//        $newRecord = Curl::to('https://api.yclients.com/api/v1/records/'.$companyId->yc_company_id.'/')
//            ->withHeader('Content-Type:application/json')
//            ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2, User ' . $this->token->data->user_token)
            ->withData([
                'staff_id'=>$request->staff_id,
                'seance_length'=>$request->seance_length,
                'services'=>$serviceIds,
                'datetime'=>$request->datetime,
                'client'=>['phone'=>$request->client['phone'],'name'=>$request->client['name']],
            ])
            ->asJson()
//            ->asJsonRequest()
            ->post();
//        info((json_encode($newRecord)));
         if((object)$newRecord->data->id) {
             $userId=User::where('phone',$request->client['phone'])->first();
             $data=(array)$newRecord->data;
             $data['user_id']=$userId->id;
             $data['department_id']=$companyId->id;
             $data['chain_id']=$companyId->chain_id;
             $data['record_id']=$newRecord->data->id;
             unset($data['id']);
             $data['company_id']=$newRecord->data->company_id;
             $data['staff_id']=$newRecord->data->staff_id;
             $data['visit_id']=$newRecord->data->visit_id;
             if (isset($newRecord->data->date)) {
                 $data['date'] = date("Y-m-d H:i:s", strtotime($newRecord->data->date));
             }
             if (isset($newRecord->data->datetime)) {
                 $data['datetime'] = date("Y-m-d H:i:s", strtotime($newRecord->data->datetime));
             }
             if (isset($newRecord->data->create_date)) {
                 $data['create_date'] = date("Y-m-d H:i:s", strtotime($newRecord->data->create_date));
             }
             if (isset($newRecord->data->last_change_date)) {
                 $data['last_change_date'] = date("Y-m-d H:i:s", strtotime($newRecord->data->last_change_date));
             }
             Ycrecord::create($data);
         }
//             $user = User::where('phone', $request->client['phone'])->first();
//             if (!$user){
//                 $item = Item::where('name', '=', 'Клиент')->first();
//                 $chain = Chain::where('chain_id', '=', 'e8e5bdae-332d-42f7-b80f-bd1e4371aca3')->first();
//                 $newUser=new User();
//                 $newUser->person_id = Str::uuid()->toString();
//                 $newUser->phone=$request->client['phone'];
//                 $newUser->firstname=$request->client['name'];
//                 $newUser->save();
//                 $item->persons()->attach($user->person_id, ['item_id' => $item->item_id, 'updated_at' => now(), 'created_at' => now()]);
//                 $chain->personHasChains()->attach($user->person_id, ['chain_id' => $chain->chain_id, 'updated_at' => now(), 'created_at' => now()]);
//
//             }
//             elseif($user){
//                 $ycItem = new Ycitem();
//                 $ycItem->ycitem_id = Str::uuid()->toString();
//                 $ycItem->yc_id = $newRecord->data->id;
//                 $ycItem->department_id = $companyId->department_id;
//                 $ycItem->chain_id = 'e8e5bdae-332d-42f7-b80f-bd1e4371aca3';
//                 $ycItem->name = $newRecord->data->name;
//                 $ycItem->phone =  preg_replace('/[^0-9]/', '', $request->client['phone']);
//                 $ycItem->email = $newRecord->data->email;
//                 $ycItem->categories = ($newRecord->data->categories);
//                 $ycItem->sex_id = $newRecord->data->sex_id;
//                 $ycItem->sex = $newRecord->data->sex;
//                 $ycItem->birth_date = $newRecord->data->birth_date;
//                 $ycItem->discount = $newRecord->data->discount;
//                 $ycItem->card = $newRecord->data->card;
//                 $ycItem->comment = $newRecord->data->comment;
//                 $ycItem->sms_check = $newRecord->data->sms_check;
//                 $ycItem->sms_bot = $newRecord->data->sms_bot;
//                 $ycItem->spent = $newRecord->data->spent;
//                 $ycItem->paid =$newRecord->data->paid;
//                 $ycItem->balance = $newRecord->data->balance;
//                 $ycItem->visits = $newRecord->data->visits;
//                 $ycItem->importance_id = $newRecord->data->importance_id;
//                 $ycItem->last_change_date = $newRecord->data->last_change_date;
//                 $ycItem->importance = $newRecord->data->importance;
//                 $ycItem->custom_fields = ($newRecord->data->custom_fields);
//                 $user->ycitems()->save($ycItem);
//             }
//}
//         if ($newRecord->data->id ?? 0)
//            $terminalRecordService->waitForTransaction($companyId->yc_company_id, $newRecord->data->id);

        return response()->json([
            'success' => (bool)($newRecord->data->id ?? 0),
            'data'=>$newRecord->data
        ],200);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */

    public function delRecordYc(Request $request)
    {
        $companyId=Department::where('department_id',$request->user()->department_id)->first();
        $delRecord = Curl::to('https://api.yclients.com/api/v1/record/'.$companyId->yc_company_id.'/303251070')
            ->withHeader('Content-Type:application/json')
            ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2, User ' . $this->token->data->user_token)
//            ->asJson()
                           ->asJsonRequest()
            ->delete();
        return $delRecord;
    }

    /**
     * Отобразить записи для оплаты
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function unPaidRecords(Request $request, YcService $yc)
    {
        $company = Department::where('department_id', $request->user()->department_id ?? 'no-id')->first();
         return response()->json([
             'data' => Ycrecord
                 ::where('user_id', $request->user_id ?? 0)
                 ->where('department_id',$company->id)
                 ->where('date','>=',date('Y-m-d 00:00:00'))
                 ->where('paid_full', 0)->get()->map(function ($item) {
                     $item->date = Carbon::createFromFormat('Y-m-d H:i:s', $item->date)->format('d.m.Y H:i');
                     return $item;
                 })
         ]);
    }

    /**
     * Обновить список записей для оплаты
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function refreshRecords(Request $request, YcService $yc)
    {
        $company = Department::where('department_id', $request->user()->department_id ?? 'no-id')->first();
       $records=  Ycrecord
            ::where('user_id', User::where('phone',$request->phone ?? 0)->first()->id ?? 0)
            ->where('department_id',$company->id)
            ->where('date','>=',date('Y-m-d 00:00:00'))
            ->where('paid_full', 0)->get()->map(function ($item) {
                $item->date = Carbon::createFromFormat('Y-m-d H:i:s', $item->date)->format('d.m.Y H:i');
                return $item;
            });

        if(count($records)==0) {
            $clients = $yc->to('clients/' . $company->yc_company_id)
                    ->withData(['phone' => $request->phone ?? ''])
                    ->asJson()->get()->data ?? [];
            foreach ((array)$clients as $client) {
                $records[] = array_values(
                    collect(
                        $yc->to('records/' . $company->yc_company_id)
                            ->withData([
                                'start_date' => Carbon::now()->format('Y-m-d'),
                                'client_id' => $client->id
                            ])
                            ->asJson()->get()->data
                    )->filter(function ($item) {
                        $item->date = Carbon::createFromFormat('Y-m-d H:i:s', $item->date)->format('d.m.Y H:i');
                        return !$item->paid_full;
                    })->toArray()
                );
            }
            $records= array_merge(...$records);
        }
        return response()->json([
            'data' => $records
        ]);
    }




    /**
     * Кнопка обновить на терминале
     * Нет записи в BIS проверяем наличие записи в yc, если есть в юк, но нет в bis записываем в bis и выводим из bis
     * @param Request $request
     * @return JsonResponse
     */
    public function importRecords(Request $request, YcService $yc)
    {
      $company = Department::where('department_id', $request->user()->department_id ?? 'no-id')->first();
      $client=Ycitem::where('phone',$request->phone)->where('department_id', $company->department_id)->first();
        if($client) {
            $records = collect($yc->to('records/' . $company->yc_company_id)
                ->withData([
                    'start_date' => Carbon::now()->format('Y-m-d'),
                    'client_id' => $client->yc_id
                ])
                ->asJson()->get()->data)->filter(function ($item) {
                $item->date = Carbon::createFromFormat('Y-m-d H:i:s', $item->date)->format('d.m.Y H:i');
                return !$item->paid_full;
            });
            if (count($records)>0) {
                foreach ($records as $recordOne) {
                  $countRecord = Ycrecord::where('record_id', $recordOne->id)->get();
                   if ($countRecord->count() == 0) {
                        $recordOne->user_id = User::where('person_id', $client->person_id)->first()->id;
                        $recordOne->department_id = $company->id;
                        $recordOne->chain_id = $company->chain_id;
                        $recordOne->record_id = $recordOne->id;
                        unset($recordOne->id);
                        if (isset($recordOne->date)) {
                            $recordOne->date = date("Y-m-d H:i:s", strtotime($recordOne->date));
                        }
                        if (isset($recordOne->datetime)) {
                            $recordOne->datetime = date("Y-m-d H:i:s", strtotime($recordOne->datetime));
                        }
                        if (isset($recordOne->create_date)) {
                            $recordOne->create_date = date("Y-m-d H:i:s", strtotime($recordOne->create_date));
                        }
                        if (isset($recordOne->last_change_date)) {
                            $recordOne->last_change_date = date("Y-m-d H:i:s", strtotime($recordOne->last_change_date));
                        }
                        Ycrecord::create((array)$recordOne);
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => Ycrecord
                ::where('user_id', User::where('person_id', $client->person_id)->first()->id ?? 0)
                ->where('department_id',$company->id)
                ->where('date','>=',date('Y-m-d 00:00:00'))
                ->where('paid_full', 0)->get()->map(function ($item) {
                    $item->date = Carbon::createFromFormat('Y-m-d H:i:s', $item->date)->format('d.m.Y H:i');
                    return $item;
                })
        ]);
    }

    /**
     * Контроль качества записей
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function qualityControl(Request $request)
    {
        if ($request->user()->hasRole(['master'])) {
            return response()->json(['error' => 'Нет прав'], 403);
        }
        $phone = trim(\request('phone', '')) ?? '';
        $chainId=DB::table('chain_user')
            ->join('chains','chains.id','=','chain_user.chid')
            ->where('user_id',$request->user()->id)
            ->pluck('chains.id');
        $records=Ycrecord::
            skip($request->skip ?? 0)
            ->Join('photos','photos.ycrecord_id','=','ycrecords.id')
            ->leftJoin('users','users.id','=','ycrecords.user_id' )
           ->select('ycrecords.id','users.phone','photos.ycrecord_id','ycrecords.user_id','ycrecords.department_id','ycrecords.staff->name as staff_name','staff->avatar as staff_avatar','ycrecords.date','typeform_status','ycrecords.rating','ycrecords.user_id')
            ->whereIn('ycrecords.chain_id',$chainId)
            ->where('users.phone','like', '%' . $phone . '%')
            ->whereNotNull('ycrecords.user_id')
            ->groupBy('ycrecords.id')
            ->orderBy('ycrecords.date','desc')
            ->take(5)
            ->get();
       $result=[];
       foreach ($records as $recordOne){
           $result[]=[
               'ycrecord_id'=>$recordOne->id,
               'department'=>Department::where('id',$recordOne->department_id)->select('department_name')->first()->department_name,
               'staff_name'=>$recordOne->staff_name,
               'staff_avatar'=>$recordOne->staff_avatar,
               'client_name'=>User::where('id',$recordOne->user_id)->first()->firstname,
               'date'=>date('d.m.Y H:i',strtotime($recordOne->date)),
               'rating'=>$recordOne->rating,
               'typeform_status'=>$recordOne->typeform_status,
               'photo'=>Photo::where('ycrecord_id',$recordOne->id)->where('type',1)->pluck('path'),
               ];
       }

        return response()->json([
        'success' => true,
        'data' =>$result
    ]);

    }
    /**
     * Аналитика по загруженным фотографиям
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function qualityControlAnalytics(Request $request)
    {
       if(Auth::user()->hasPerms('list.analytics.common')) {
             $chainId = DB::table('chain_user')
               ->join('chains', 'chains.id', '=', 'chain_user.chid')
               ->where('user_id', Auth::user()->id)
               ->pluck('chains.id');

             $result = Report::where('type', '4')->whereIn('chain_id',$chainId)->latest()->first();

             return response()->json([
               'success' => (boolean)$result,
               'data' => $result->data ?? []
           ]);
      }
      else{return response()->json(['error' => 'Нет прав'], 403);}
    }



    /**
     * Показать результат оценки записи из TypeForm
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function showTypeFormWithRecord(Request $request, $id)
    {
        $result=[];
        $typeforms=Typeform::where('ycrecord_id',$id)->latest()->first();
        if($typeforms){
         $definition=$typeforms['definition']['fields'];
         $answers=$typeforms['answers'];
           $result1['calculated']=$typeforms->calculated;
              foreach ($definition as $definitionOne){
                  foreach ($answers as $answerOne ){
                      if($definitionOne['id']==$answerOne['field']['id']){
                            $result[]=[
                                'labels'=>$definitionOne['title'],
                                'answer'=>$answerOne['choices']['labels']
                            ];
                          }
                  }
              }
          }
        return response()->json([
            'data'=>[
            'calculated'=>$typeforms->calculated,
            'result'=>$result ?? []
        ]]);

        }
    /**
     * Обновить запись поле typeform_status в BIS, пока вебхук не пришел статус меняем 1
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateTypeformStatus(Request $request, $id)
    {
        $result=false;
        $record=Ycrecord::find($id);
        if($record->typeform_status!=2) {
            $result = $record->update(['typeform_status' => $request->typeform_status]);
        }
          return response()->json([
            'success'=> $result,
            'data'=>Ycrecord::find($id) ?? []
            ]);
    }

    /**
     * Показать все поля записи
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show($id)
    {
        $result=Ycrecord::find($id);
        return response()->json([
            'success'=>(boolean)$result,
            'data'=>Ycrecord::find($id) ?? []
        ]);
    }



}
