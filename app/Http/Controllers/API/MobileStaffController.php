<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Photo;
use App\Models\Report;
use App\Models\User;
use App\Models\Ycrecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Ixudra\Curl\Facades\Curl;
use phpDocumentor\Reflection\Types\True_;
use PhpParser\Node\Expr\Array_;
use React\Dns\Model\Record;

class MobileStaffController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function allRecordsStaff(Request $request)
    {
        $beginDate=date('Y-m-d 00:00:00');
        $endDate=date('Y-m-d 23:59:00');
        if($request->date){
            $beginDate=date('Y-m-d 00:00:00',strtotime($request->date));
            $endDate=date('Y-m-d 23:59:00',strtotime($request->date));
        }
        $result=[];
            $records=Ycrecord::where('staff->id',$request->user()->yc_staff_id)
            ->join('users','users.id','=','ycrecords.user_id')
            ->where('date','>=',$beginDate)
            ->where('date','<=',$endDate)
            ->select('ycrecords.date', 'ycrecords.id as record_id','ycrecords.user_id','users.firstname as client_name','ycrecords.services')
            ->orderBy('date','asc')
            ->get();
             foreach ( $records as  $recordOne){
                 $result[]=[
                   'date'=>date('d.m.Y',strtotime($recordOne->date)),
                   'time'=>date('H:i',strtotime($recordOne->date)),
                   'record_id'=>$recordOne->record_id,
                   'user_id'=>$recordOne->user_id,
                   'client_name'=>$recordOne->client_name,
                   'services_id'=>$recordOne->services[0]['id'] ?? '',
                   'services_title'=>$recordOne->services[0]['title'] ?? ''
                   ];
//                 if($recordOne->services){
//                     foreach ($recordOne->services as $service){
////                         array_push($result, ['title'=>$service['title']]);
//                         $result['fsd']=[
//                              $service['title']
//                         ];
////                         $result=Array('title'=>$service['title']);
//                     }
//
//                 }

             }

        return response()->json([
            "success" => true,
            "message" => "List services staff",
            "data" => $result
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function allRecordsClient(Request $request, $id)
    {
     $records=Ycrecord::
       join('users','users.id','=','ycrecords.user_id')
        ->Leftjoin('photos','photos.ycrecord_id','=','ycrecords.id')
       ->select('users.id as user_id','ycrecords.date as date','ycrecords.id as record_id','users.firstname','ycrecords.date','ycrecords.staff->name as master','ycrecords.services','photos.ycrecord_id','ycrecords.record_done','ycrecords.comment')
       ->where('ycrecords.user_id',$id)
       ->groupBy('ycrecords.id')
       ->orderBy('ycrecords.date', 'desc')
       ->get();
      $result=[];
        foreach ( $records as  $recordOne) {
            if(($recordOne->date)<date('Y-m-d 00:00:00')){
               $records[0]->record_done=1;
            }
            $result[] = [
                'date' => date('d.m.Y', strtotime($recordOne->date)),
                'time' => date('H:i', strtotime($recordOne->date)),
                'user_id' => $recordOne->user_id,
                'record_id' => $recordOne->record_id,
                'staff' => $recordOne->master,
                'firstname'=>$records[0]->firstname,
                'comment'=>$records[0]->comment,
                'record_done'=>(boolean)$records[0]->record_done,
                'photos'=>Photo::
                    join('ycrecords','ycrecords.id','=','photos.ycrecord_id')
                   ->where('photos.ycrecord_id',$recordOne->record_id)
                   ->pluck('photos.path')
            ];
        }

        return response()->json([
            "success" => true,
            "message" => "Record client",
            "firstname"=>$records[0]->firstname,
            "data" =>$result
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function RecordClient(Request $request,$id)
    {
         $record=DB::table('ycrecords')
            ->leftJoin('photos','photos.ycrecord_id','=','ycrecords.id')
            ->leftJoin('users','users.id','=','ycrecords.user_id')
            ->select('users.id as user_id', 'users.firstname','users.comment','users.ivideon_done','ycrecords.id as record_id','ycrecords.date','photos.id as photo_id','photos.path as path','ycrecords.record_done')
            ->where('ycrecords.id',$id)
            ->get();
        $photo=[];

        foreach ($record as $photoOne) {
            if ($photoOne->photo_id==null){
                $photo=[];
                break;
            }
            $photo[] = [
                'id' => $photoOne->photo_id,
                'path' => $photoOne->path,
            ];
        }
        if(($record[0]->date)<date('Y-m-d 00:00:00')){
            $record[0]->record_done=1;
        }
        $result=[
            'record_id'=>$record[0]->record_id,
            'date'=>date('d.m.Y',strtotime($record[0]->date)),
            'time'=>date('H:i',strtotime($record[0]->date)),
            'user_id'=>$record[0]->user_id,
            'firstname'=>$record[0]->firstname,
            'comment'=>$record[0]->comment,
            'ivideon_done'=>(boolean)$record[0]->ivideon_done,
            'record_done'=>(boolean)$record[0]->record_done,
            'photos'=> $photo
        ];
        return response()->json([
            "success" => true,
            "message" => "Record of client",
            "data" => $result
        ]);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function storeRecordClient(Request $request,$id)
    {
        info(print_r($request->all(), true));
        $result = [];
        $data=[];
       $user = User::where('users.id', '=', $request->user_id)->first();
        $face_id=$user->person_ivideon_id ?? null;
        if(Photo::where('user_id',$user->id)->where('type',5)->count()==0 && !empty($user->person_ivideon_id)){
          Curl::to('http://openapi-alpha-eu01.ivideon.com/faces/'.$user->person_ivideon_id.'?op=DELETE&access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b')
             ->withData(['id' => $user->person_ivideon_id])
                ->asJsonRequest()
                ->post();
            $user->person_ivideon_id=NULL;
        }
        if (empty($user->person_ivideon_id)) {
            if ($user->level() == 0 ) {
                $face_gallery_id = '100-5Dzg4Q2nKpvqKd9Vu52B';
            }
            if ($user->level() >= 10) {
                $face_gallery_id = '100-GVaGUwCF2mHejrHbKykm';
            }

            $photoIvideon = Curl::to('http://openapi-alpha-eu01.ivideon.com/faces?op=CREATE&access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b')
                ->withHeader('Content-Type: application/json')
                ->withData([
                    'person' => $user->firstname ?? "Noname",
                    'face_gallery_id' => $face_gallery_id,
                    'description' => $user->phone ?? NULL,
                ])
                ->asJson()
                ->post();

            $data['person_ivideon_id']=$face_id=$photoIvideon->result->id;
            $user->update(['person_ivideon_id'=>$data['person_ivideon_id'],'terminal_name'=>$user->firstname]);
        }
       if(!empty($request->files)){

           $count=0;
           foreach ($request->files as $file => $image) {
               if($count==4){break;}
               $count++;
               $filename = $file . '_' .Str::random(10). $image->getClientOriginalName();
               $newPhoto = new Photo();
               if ($file != 'ivideonFirst' || $file != 'ivideonSecond' || $file != 'ivideonThird') {
//                   info(print_r($image->getMimeType(), true));
                   $path='photos/records/' . $id . '/';
                   $url = env('APP_URL') . $path . $filename;
                   $newPhoto->ycrecord_id = $id;
                   $newPhoto->type=1;
                   $newPhoto->type_title='фото стрижки с моб приложения';
                   $newPhoto->user_id = $user->id;
                   $newPhoto->path = $url;
                   $image->move(public_path(). '/'.$path, $filename);
                   $result[$file] =  $newPhoto->save();
                   if(Photo::where('user_id',$user->id)->where('type',5)->count()<=3 && $count<=3) {
                       $newPhotoIvideon = new Photo();
                       $pathIvideon = 'photos/ivideon/' . $user->id . '/';
                       $urlIvideon = env('APP_URL') . $pathIvideon . $filename;
                       $newPhotoIvideon->path = $urlIvideon;
                       if (!file_exists(public_path() .'/'.$pathIvideon)) {
                           mkdir(public_path() .'/'.$pathIvideon, 0755, true);
                       }
                       File::copy(public_path(). '/'.$path.$filename, public_path() .'/'. $pathIvideon . $filename);
                       $newPhotoIvideon->type = 5;
                       $newPhotoIvideon->num = $count;
                       $newPhotoIvideon->type_title = 'фото для Ivideon';
                       $newPhotoIvideon->user_id = $request->user_id;
                       $newPhotoIvideon->save();
                       $base64 = base64_encode(file_get_contents(public_path(). '/'.$path.$filename));
                       Curl::to('http://openapi-alpha-eu01.ivideon.com/faces/' . $face_id . '/photos?op=ADD&access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b')
                        ->withHeader('Content-Type: application/json')
                        ->withData([
                            'image_data' => $base64,
                        ])
                        ->asJson()
                        ->post();
                   }

               }
//            if ($file == 'ivideonFirst' || $file == 'ivideonSecond' || $file == 'ivideonThird') {
//                $url = 'https://bis.zdato.ru/photos/ivideon/' . $request->user_id . '/' . $filename;
//                $path='/photos/ivideon/' . $request->user_id . '/';
//                $base64 = base64_encode(file_get_contents($image));
//                //загружаем фото в ивидеон
//                Curl::to('http://openapi-alpha-eu01.ivideon.com/faces/' . $face_id . '/photos?op=ADD&access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b')
//                    ->withHeader('Content-Type: application/json')
//                    ->withData([
//                        'image_data' => $base64,
//                    ])
//                    ->asJson()
//                    ->post();
//                $newPhoto->type=2;
//            }
           }

//            foreach ($filesIvideon as $fileIvideon => $imageIvideon) {
//                    if($count2==3){break;}
//                    $count2++;
//
//                  if ($fileIvideon != 'ivideonFirst' || $fileIvideon != 'ivideonSecond' || $fileIvideon != 'ivideonThird') {
//                      if(Photo::where('user_id',$user->id)->where('type',5)->count()<=3){
//                        $filename = $fileIvideon . '_' .Str::random(5). $imageIvideon->getClientOriginalName();
//                        $newPhotoIvideon = new Photo();
//                        $pathIvideon = 'photos/ivideon/' . $user->id . '/';
//                        $urlIvideon = env('APP_URL') . $pathIvideon . $filename;
//                        $newPhotoIvideon->path = $urlIvideon;
//                        File::copy($imageIvideon->getPathName(), public_path().$pathIvideon.$filename.'123');
//                        $newPhotoIvideon->type = 5;
//                        $newPhotoIvideon->num = $count2;
//                        $newPhotoIvideon->type_title = 'фото для Ivideon';
//                        $newPhotoIvideon->user_id = $request->user_id;
//                        $newPhotoIvideon->save();
////                    $base64 = base64_encode(file_get_contents($imageIvideon));
//////                    //загружаем фото в ивидеон
//////                    Curl::to('http://openapi-alpha-eu01.ivideon.com/faces/' . $face_id . '/photos?op=ADD&access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b')
//////                        ->withHeader('Content-Type: application/json')
//////                        ->withData([
//////                            'image_data' => $base64,
//////                        ])
//////                        ->asJson()
//////                        ->post();
////
//                    }
//                  }
//            }
       }
        $record = Ycrecord::where('id',$id)->first();
        $data['ivideon_done']=true;
//        $data['comment']=$user->comment ? $user->comment.' '.$request->comment : $request->comment;
        $user->update($data);
            return response()->json([
            "record_update"=>(boolean)$record->update(['comment'=>$record ->comment ? $record ->comment.' '.$request->comment : $request->comment ?? NULL,'record_done'=>1]),
            "message" => "save photos",
            "data" => $result

        ]);

    }


    /**
     * Выводим все запсии клиента с активной для отображения в ленте
     *
     * @param Request $request
     * @return JsonResponse
     */

    public function listRecordClient(Request $request,$id){
        $beforeResult=[];
        $actionResult=[];
        $afterResult=[];

    $chain_id=Department::where('department_id',$request->user()->department_id)->first()->chain_id;
    $records=Ycrecord::
            leftJoin('departments','departments.id','=','ycrecords.department_id')
            ->select('ycrecords.id','ycrecords.user_id','ycrecords.department_id','ycrecords.staff->name as staff_name','staff->avatar as staff_avatar','ycrecords.date','ycrecords.rating','ycrecords.comment','departments.department_name as department_name','ycrecords.goods_transactions')
            ->where('ycrecords.user_id',$request->user_id)
            ->where('ycrecords.chain_id',$chain_id)
            ->orderBy('ycrecords.date','desc')
            ->get();
    $recordActive=Ycrecord::
       where('ycrecords.id',$id)
       ->leftJoin('users','users.id','=','ycrecords.user_id')
         ->select('users.firstname', 'ycrecords.date')
        ->first();
        foreach ($records as $recordOne){
            if(strtotime($recordOne->date)<strtotime($recordActive->date)){
                $afterResult[]=[
                    'record_id' => $recordOne->id,
                    'date'=>date('d.m.Y', strtotime($recordOne->date)),
                    'staff' => $recordOne->staff_name,
                    'avatar' => $recordOne->staff_avatar,
                    'department_name' => $recordOne->department_name,
                    'photos'=>Photo::where('ycrecord_id',$recordOne->id)->select('path')->get(),
                    'goods'=>$recordOne->goods_transactions,
                    'comment'=>$recordOne->comment
                ];
            }
            if($recordOne->id==$id) {
                $actionResult = [
                    'record_id' => $recordOne->id,
                    'date'=>date('d.m.Y', strtotime($recordOne->date)),
                    'staff' => $recordOne->staff_name,
                    'avatar' => $recordOne->staff_avatar,
                    'department_name' => $recordOne->department_name,
                    'photos'=>Photo::where('ycrecord_id',$recordOne->id)->select('path')->get(),
                    'goods'=>$recordOne->goods_transactions,
                    'comment'=>$recordOne->comment
                ];

            }
            if(strtotime($recordOne->date)>strtotime($recordActive->date)) {
                $beforeResult[] = [
                    'record_id' => $recordOne->id,
                    'date'=>date('d.m.Y', strtotime($recordOne->date)),
                    'staff' => $recordOne->staff_name,
                    'avatar' => $recordOne->staff_avatar,
                    'department_name' => $recordOne->department_name,
                    'photos'=>Photo::where('ycrecord_id',$recordOne->id)->select('path')->get(),
                    'goods'=>$recordOne->goods_transactions,
                    'comment'=>$recordOne->comment
                ];
            }
           }
           return response()->json([
            "success" => true,
            "message" => "history records",
            "client_name"=>$recordActive->firstname,
            "data" => [
                'before'=>array_reverse(array_slice(array_reverse($beforeResult), 0, 2, true)),
                'active'=>$actionResult,
                'after'=>(array_slice($afterResult, 0, 2, true)),
            ]
        ]);

    }

    /**
     * Выводим все запсии клиента с активной для отображения в лченте
     *
     * @param Request $request
     * @return JsonResponse
     */

    public function nextRecordClient(Request $request, $id){
        $param='';
        $result=[];
        $chain_id=Department::where('department_id',$request->user()->department_id)->first()->chain_id;
        $recordActive=Ycrecord::
        where('ycrecords.id',$id)
            ->leftJoin('users','users.id','=','ycrecords.user_id')
            ->select('ycrecords.date')
            ->first();
        if($request->before==true){
            $param = '>';
            $sort='asc';
        }
        if($request->after==true){
            $param = '<';
            $sort='desc';
        }
        $records = Ycrecord::skip($request->skip)
           -> leftJoin('departments','departments.id','=','ycrecords.department_id')
                ->select('ycrecords.id','ycrecords.user_id','ycrecords.department_id','ycrecords.staff->name as staff_name','staff->avatar as staff_avatar','ycrecords.date','ycrecords.rating','ycrecords.comment','departments.department_name as department_name', 'ycrecords.goods_transactions')
                ->where('ycrecords.user_id',$request->user_id)
                ->where('ycrecords.chain_id',$chain_id)
                ->where('ycrecords.date',$param,date('Y-m-d H:i:s',strtotime($recordActive->date)))
                ->orderBy('ycrecords.date',$sort)
                ->take(2)->get();
           foreach ($records as $recordOne){

            $result[] = [
                'record_id' => $recordOne->id,
                'date'=>date('d.m.Y', strtotime($recordOne->date)),
                'staff' => $recordOne->staff_name,
                'avatar' => $recordOne->staff_avatar,
                'department_name' => $recordOne->department_name,
                'photos'=>Photo::where('ycrecord_id',$recordOne->id)->select('path')->get(),
                'goods'=>$recordOne->goods_transactions,
                'comment'=>$recordOne->comment
            ];

        }

        return response()->json([
            "success" => true,
            "message" => "next records",
            'data'=>$result
            ]);

    }

    /**
     * Загружаем аватар мастера из мобильного приложения
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function loadavatar(Request $request){
            if(isset($request->avatar)){
                $user=User::where('id',$request->user()->id)->first();
                $newPhoto = new Photo();
                $newPhoto->user_id = $request->user()->id;
                $filename = 'avatar_' .Str::random(5). $request->avatar->getClientOriginalName();
                $path='photos/avatar/' . $request->user()->id . '/';
                $url = env('APP_URL') .$path. $filename;
                $newPhoto->path = $url;
                $request->avatar->move(public_path().'/'.$path, $filename);
                $newPhoto->type=3;
                return response()->json([
                    'load_photo'=>[
                        "success" =>$newPhoto->save()
                    ],
                    'update_user'=>[
                        "success" =>$user->update(['avatar'=>$url])
                    ],
                ]);
            }
    }

    /**
     * аналитика мастера
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function analytics(Request $request){

//        $result = Report::where('user_id', $request->user()->id)->where('type','5')->latest()->first();
//        return response()->json([
//            'message' => "Analytics of Master",
//            'success' => (boolean)$result,
//            'data'=> $result->data ?? []
//        ]);

        //выбираем суточный отчет по мастерам
        $result = collect(Report::where('type','4')->where('chain_id',Auth::user()->chain[0]->id)->latest()->first()->data);

        //новые клиенты
        $arr_clientRecord =(array_values($result->sortByDesc('clientRecordWithPhoto')->toArray()));
        $count_clientRecord=0;
        foreach ( $arr_clientRecord as $key=>$posOne){
            $count_clientRecord++;
            if($posOne['id']==Auth::id()){
                if($count_clientRecord==1){
                    $countNewClientWithPhoto=['value'=>$posOne['clientAndRecords_percent'],'rank'=>$count_clientRecord];
                }
                else $countNewClientWithPhoto=['value'=>$posOne['QA_new_client_percent']. '% ('.$posOne['clientRecordWithPhoto'].' из '.$posOne['clientRecord'].')',
                    'rank'=>$count_clientRecord.'(+'.(($arr_clientRecord[$count_clientRecord-2]['clientRecordWithPhoto']-$posOne['clientRecordWithPhoto'])+1).' фото до Ранг '.($count_clientRecord-1).')'];

            }
        }

        //клиенты загружены за месяц
        $arr_clientWithPhotofirstOfMonth =(array_values($result->sortByDesc('clientStaffWithPhotofirstOfMonth')->toArray()));
        $count_PhotofirstOfMonth=0;
        foreach ($arr_clientWithPhotofirstOfMonth as $key=>$posOne){
            $count_PhotofirstOfMonth++;
            if($posOne['id']==Auth::id()){
                if($count_PhotofirstOfMonth==1){
                    $countRecordWithPhotofirstOfMonth=['value'=>$posOne['clientAndRecordsfirstOfMonth_percent'],'rank'=>$count_PhotofirstOfMonth];
                }
                else  $countRecordWithPhotofirstOfMonth=['value'=>$posOne['clientAndRecordsfirstOfMonth_percent']. '% ('.$posOne['clientStaffWithPhotofirstOfMonth'].' из '.$posOne['clientStaffRecordsfirstOfMonth'].')',
                    'rank'=> $count_PhotofirstOfMonth.'(+'.(($arr_clientWithPhotofirstOfMonth[$count_PhotofirstOfMonth-2]['clientStaffWithPhotofirstOfMonth']-$posOne['clientStaffWithPhotofirstOfMonth'])+1).' фото до Ранг '.($count_PhotofirstOfMonth-1).')'];

            }
        }

        //клиенты загружены всего
        $arr_countRecord =(array_values($result->sortByDesc('countRecordWithPhoto')->toArray()));
        $count_countRecord=0;
        foreach ($arr_countRecord as $key=>$posOne){
            $count_countRecord++;
            if($posOne['id']==Auth::id()){
                if($count_countRecord==1){
                    $countRecordWithPhoto=['value'=>$posOne['clientAndRecords_percent'],'rank'=>$count_countRecord];
                }
                else  $countRecordWithPhoto=['value'=>$posOne['clientAndRecords_percent']. '% ('.$posOne['countRecordWithPhoto'].' из '.$posOne['countRecord'].')',
                    'rank'=>$count_countRecord.'(+'.(($arr_countRecord[$count_countRecord-2]['countRecordWithPhoto']-$posOne['countRecordWithPhoto'])+1).' фото до Ранг '.($count_countRecord-1).')'];

            }
        }

        //средний чек
        $arr_avg_buy =(array_values($result->sortByDesc('avg_buy')->toArray()));
        $count_avg_buy=0;
        foreach ($arr_avg_buy as $key=>$posOne){
            $count_avg_buy++;
            if($posOne['id']==Auth::id()){
                if($count_avg_buy==1){
                    $avg_buy=['value'=>$posOne['avg_buy'],'rank'=>$count_avg_buy];
                }
                else  $avg_buy=['value'=>$posOne['avg_buy'],'rank'=>$count_avg_buy.'(+'.(($arr_avg_buy[$count_avg_buy-2]['avg_buy']-$posOne['avg_buy'])+1).' руб. до '.($count_avg_buy-1).'-го места)'];

            }
        }

        return response()->json([
            'success' => (boolean)$result,
            'data' =>[
                'newClientMonth'=> $countNewClientWithPhoto ?? [],
                'clientMonth'=> $countRecordWithPhotofirstOfMonth ?? [],
                'clientTotal'=>$countRecordWithPhoto ?? [],
                'avg'=>$avg_buy ?? [],
            ]
        ]);

    }


}
