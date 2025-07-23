<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Models\Chain;
use App\Models\Department;
use App\Models\Item;
use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use App\Models\Ycdb;
use App\Models\Ycitem;
use App\Models\Ycrecord;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Ixudra\Curl\Facades\Curl;
use Illuminate\Support\Str;
use function MongoDB\BSON\toJSON;

class LoadDBController extends Controller
{

    public function LoadFromYcNewClients()
    {
//
        $c=0;
     //авторизация yC
        $user=(object)['login'=>'paul@strijevski.ru', 'password'=>'2fz2ex'];
        $token= Curl::to('https://api.yclients.com/api/v1/auth')
            ->withHeader('Content-Type:application/json')
            ->withHeader('Accept:application/vnd.yclients.v2+json')
            ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2')
            ->withData($user)
            ->asJson()
            ->post();
   //список компаний
      $companiesYc=Curl::to('https://api.yclients.com/api/v1/companies?my=1')
            ->withHeader('Content-Type:application/json')
            ->withHeader('Accept:application/vnd.yclients.v2+json')
            ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2, User '. $token->data->user_token)
            ->asJson()
            ->get();


        $result=[];
        $idClientYc = [];
        $dategegin=date('Y-m-d\T00:00:00');
        $dateend=date('Y-m-d\T23:59:00');
        $item = Item::where('name', '=', 'Клиент')->first();
        $chain = Chain::where('chain_id', '=', 'e8e5bdae-332d-42f7-b80f-bd1e4371aca3')->first();

          //выбираем все записи за текущий день по всем салонам
          foreach ($companiesYc->data as $companiesId) {
             $clientsYc = Curl::to('https://api.yclients.com/api/v1/records/' . $companiesId->id)
                  ->withHeader('Content-Type:application/json')
                  ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2, User ' . $token->data->user_token)
                  ->withData(['changed_after' => $dategegin, 'changed_before' => $dateend])
//                  ->asJsonRequest()
                  ->asJson()
                  ->get();
              foreach ($clientsYc->data as $client) {
                  //проверяем есть ли свойсво client или client->id у записей  (может и не быть)
                  if ($client->client == null || (!isset($client->client->id))) continue;
                  $idClientYc[] = [
                      'record_id'=>$client->id,
                      'company_id'=>$client->company_id,
                      'id'=>$client->client->id,
                      'phone' =>$client->client->phone
                  ];

              }
          }

            //из найденных id клиентов ищем которых нет в базе
            foreach ($idClientYc as $clientYc) {
              $countPhone = Person::where('phone',  preg_replace('/[^0-9]/', '', $clientYc['phone']))->get()->count();
              if ($countPhone == 0) {
              //    выбираем из yC всех клиентов всех салонов, но уже с данными
                 foreach ($companiesYc->data as $companiesId) {
                     $client = Curl::to('https://api.yclients.com/api/v1/client/' . $companiesId->id . '/' . $clientYc['id'])
                         ->withHeader('Content-Type:application/json')
                         ->withHeader('Accept:application/vnd.yclients.v2+json')
                         ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2, User ' . $token->data->user_token)
                         ->withData((object)['page_size' => 200])
                         ->asJson()
                         ->get();
                     switch ($companiesId->id) {
                         case 215098:
                             //ватутина
                             $idSalon = '1b8e1451-ec5a-41cf-bd98-38913361e2cd';
                             break;
                         case 399823:
                             //морской
                             $idSalon = '559871ac-b9d1-4dd8-b58c-69e71e90c2cc';
                             break;
                         case 243563:
                             //красный
                             $idSalon = '37857ade-4fe3-407e-a97f-75081cc7a37f';
                             break;
                         case 282644:
                             //Дуси Ковальчук
                             $idSalon = '0388493a-d104-44ad-847c-85ed48080856';
                             break;
                     }

                     //если есть данные на клиента в салоне - сохраняем в БД
                     if ($client->data != null) {
                         $result[] =
                             [
                                 $client->data,
                             ];


                         $person = new Person();
                         $person->person_id = Str::uuid()->toString();
                         if (isset($client->data->custom_fields->ivideon)) {
                             $person->person_ivideon_id = $client->data->custom_fields->ivideon;
                         }
                         $person->firstname = $client->data->name;
                         $person->sex = $client->data->sex;
                         $person->phone =  preg_replace('/[^0-9]/', '', $clientYc['phone']);
                         $person->birth_date = $client->data->birth_date;
                         $person->email = $client->data->email;
                         $person->save();

                         $ycItem = new Ycitem();
                         $ycItem->ycitem_id = Str::uuid()->toString();
                         $ycItem->yc_id = $client->data->id;
                         $ycItem->department_id = $idSalon;
                         $ycItem->chain_id = 'e8e5bdae-332d-42f7-b80f-bd1e4371aca3';
                         $ycItem->name = $client->data->name;
                         $ycItem->phone =  preg_replace('/[^0-9]/', '', $clientYc['phone']);
                         $ycItem->email = $client->data->email;
                         $ycItem->categories = ($client->data->categories);
                         $ycItem->sex_id = $client->data->sex_id;
                         $ycItem->sex = $client->data->sex;
                         $ycItem->birth_date = $client->data->birth_date;
                         $ycItem->discount = $client->data->discount;
                         $ycItem->card = $client->data->card;
                         $ycItem->comment = $client->data->comment;
                         $ycItem->sms_check = $client->data->sms_check;
                         $ycItem->sms_bot = $client->data->sms_bot;
                         $ycItem->spent = $client->data->spent;
                         $ycItem->paid = $client->data->paid;
                         $ycItem->balance = $client->data->balance;
                         $ycItem->visits = $client->data->visits;
                         $ycItem->importance_id = $client->data->importance_id;
                         $ycItem->last_change_date = $client->data->last_change_date;
                         $ycItem->importance = $client->data->importance;
                         $ycItem->custom_fields = ($client->data->custom_fields);

                         $item->persons()->attach($person->person_id, ['item_id' => $item->item_id, 'updated_at' => now(), 'created_at' => now()]);
                         $chain->personHasChains()->attach($person->person_id, ['chain_id' => $chain->chain_id, 'updated_at' => now(), 'created_at' => now()]);
                         $person->ycitems()->save($ycItem);

                     }
                 }
              }
            }
        foreach ($idClientYc as $clientYc) {
            $user_id = DB::table('ycitems')
                ->join('users', 'users.person_id', '=', 'ycitems.person_id')
                ->select('users.id')
                ->where('ycitems.yc_id', '=', $clientYc['id'])->get();
            //проверяем есть такая запись в базе, если нет то создаем ее
            $cointRecord = Ycrecord::where('record_id', $clientYc['record_id'])->get()->count();
            if ($cointRecord == 0) {
                foreach ($companiesYc->data as $companiesId) {
                    $record = Curl::to('https://api.yclients.com/api/v1/record/' . $companiesId->id . '/' . $clientYc['record_id'])
                        ->withHeader('Content-Type:application/json')
                        ->withHeader('Accept:application/vnd.yclients.v2+json')
                        ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2, User ' . $token->data->user_token)
                        ->asJson()
//            ->asJsonRequest()
                        ->get();
                    switch ($companiesId->id) {
                        case 215098:
                            //ватутина
                            $idSalon = 8;
                            break;
                        case 399823:
                            //морской
                            $idSalon = 11;
                            break;
                        case 243563:
                            //красный
                            $idSalon = 10;
                            break;
                        case 282644:
                            //Дуси Ковальчук
                            $idSalon = 9;
                            break;
                    }
                    $recordToDb=new Ycrecord();
                    if(isset($user_id[0]->id)) {
                        $recordToDb->user_id = $user_id[0]->id;
                    }
                    $recordToDb->department_id=$idSalon;
                    $recordToDb->chain_id=1;
                    if(isset($record->data)) {
                        $recordToDb->record_id = $record->data->id;
                        $recordToDb->company_id = $record->data->company_id;
                        $recordToDb->staff_id = $record->data->staff_id;
                        $recordToDb->services = $record->data->services;
                        $recordToDb->goods_transactions = $record->data->goods_transactions;
                        $recordToDb->staff = $record->data->staff;
                        $recordToDb->client = $record->data->client;
                        $recordToDb->clients_count = $record->data->clients_count;
                        $recordToDb->date = $record->data->date;
                        $recordToDb->datetime = date("Y-m-d H:i:s", strtotime($record->data->datetime));
                        $recordToDb->create_date = date("Y-m-d H:i:s", strtotime($record->data->create_date));
                        $recordToDb->comment = $record->data->comment;
                        $recordToDb->online = $record->data->online;
                        $recordToDb->visit_attendance = $record->data->visit_attendance;
                        $recordToDb->attendance = $record->data->attendance;
                        $recordToDb->confirmed = $record->data->confirmed;
                        $recordToDb->seance_length = $record->data->seance_length;
                        $recordToDb->length = $record->data->length;
                        $recordToDb->sms_before = $record->data->sms_before;
                        $recordToDb->sms_now = $record->data->sms_now;
                        $recordToDb->sms_now_text = $record->data->sms_now_text;
                        $recordToDb->email_now = $record->data->email_now;
                        $recordToDb->notified = $record->data->notified;
                        $recordToDb->master_request = $record->data->master_request;
                        $recordToDb->api_id = $record->data->api_id;
                        $recordToDb->from_url = $record->data->from_url;
                        $recordToDb->review_requested = $record->data->review_requested;
                        $recordToDb->visit_id = $record->data->visit_id;
                        $recordToDb->created_user_id = $record->data->created_user_id;
                        $recordToDb->deleted = $record->data->deleted;
                        $recordToDb->paid_full = $record->data->paid_full;
                        $recordToDb->prepaid = $record->data->prepaid;
                        $recordToDb->prepaid_confirmed = $record->data->prepaid_confirmed;
                        $recordToDb->last_change_date = date("Y-m-d H:i:s", strtotime($record->data->last_change_date));
                        $recordToDb->custom_color = $record->data->custom_color;
                        $recordToDb->custom_font_color = $record->data->custom_font_color;
                        $recordToDb->record_labels = $record->data->record_labels;
                        $recordToDb->activity_id = $record->data->activity_id;
                        $recordToDb->custom_fields = $record->data->custom_fields;
                        $recordToDb->documents = $record->data->documents;

                        $c++;
                        $recordToDb->save();
                    }
                }
            }
        }

        return $c;
    }

    public function LoadFromYcRecords()
    {

        //авторизация yC
        $user=(object)['login'=>'paul@strijevski.ru', 'password'=>'2fz2ex'];
        $token= Curl::to('https://api.yclients.com/api/v1/auth')
            ->withHeader('Content-Type:application/json')
            ->withHeader('Accept:application/vnd.yclients.v2+json')
            ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2')
            ->withData($user)
            ->asJson()
            ->post();
        //список компаний

        $companiesYc=Curl::to('https://api.yclients.com/api/v1/companies?my=1')
            ->withHeader('Content-Type:application/json')
            ->withHeader('Accept:application/vnd.yclients.v2+json')
            ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2, User '. $token->data->user_token)
            ->asJson()
            ->get();



  $recordYc = Curl::to('https://api.yclients.com/api/v1/records/215098')
                ->withHeader('Content-Type:application/json')
                ->withHeader('Accept:application/vnd.yclients.v2+json')
                ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2, User ' . $token->data->user_token)
//                ->withData((object)['pagesize' => 25])
                ->asJson()
//            ->asJsonRequest()
                ->get();



$totalPage=ceil($recordYc->meta->total_count/100);
        $recordId=[];
        $c=0;
        $clientsId=[];
//        выбираем из yC все id записи одного салона, формируем массив из id
       for($i=1;$i<=$totalPage;$i++) {
           $recordAll = Curl::to('https://api.yclients.com/api/v1/records/215098/?page='.$i)
                    ->withHeader('Content-Type:application/json')
                    ->withHeader('Accept:application/vnd.yclients.v2+json')
                    ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2, User ' . $token->data->user_token)
//            ->withData((object)['page' => 20])
                    ->asJson()
//            ->asJsonRequest()
                    ->get();
           foreach ($recordAll->data as $recordIdOne) {
               array_push($recordId, $recordIdOne->id);
           }
          }
       $result=[];
        foreach ($recordId as $recordOne) {
          $record = Curl::to('https://api.yclients.com/api/v1/record/215098/'.$recordOne)
                ->withHeader('Content-Type:application/json')
                ->withHeader('Accept:application/vnd.yclients.v2+json')
                ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2, User ' . $token->data->user_token)
                ->asJson()
//            ->asJsonRequest()
                ->get();
//            $result[]=$record->data;
            if(isset($record->data->client->id)) {
                $user_id = DB::table('ycitems')
                    ->join('users', 'users.person_id', '=', 'ycitems.person_id')
                    ->select('users.id')
                    ->where('ycitems.yc_id', '=', $record->data->client->id)->get();
            }

            $recordToDb=new Ycrecord();
            if(isset($user_id[0]->id)) {
                $recordToDb->user_id = $user_id[0]->id;
            }
            $recordToDb->department_id=8;
            $recordToDb->chain_id=1;
            if(isset($record->data)) {
                $recordToDb->record_id = $record->data->id;
                $recordToDb->company_id = $record->data->company_id;
                $recordToDb->staff_id = $record->data->staff_id;
                $recordToDb->services = $record->data->services;
                $recordToDb->goods_transactions = $record->data->goods_transactions;
                $recordToDb->staff = $record->data->staff;
                $recordToDb->client = $record->data->client;
                $recordToDb->clients_count = $record->data->clients_count;
                $recordToDb->date = $record->data->date;
                $recordToDb->datetime = date("Y-m-d H:i:s", strtotime($record->data->datetime));
                $recordToDb->create_date = date("Y-m-d H:i:s", strtotime($record->data->create_date));
                $recordToDb->comment = $record->data->comment;
                $recordToDb->online = $record->data->online;
                $recordToDb->visit_attendance = $record->data->visit_attendance;
                $recordToDb->attendance = $record->data->attendance;
                $recordToDb->confirmed = $record->data->confirmed;
                $recordToDb->seance_length = $record->data->seance_length;
                $recordToDb->length = $record->data->length;
                $recordToDb->sms_before = $record->data->sms_before;
                $recordToDb->sms_now = $record->data->sms_now;
                $recordToDb->sms_now_text = $record->data->sms_now_text;
                $recordToDb->email_now = $record->data->email_now;
                $recordToDb->notified = $record->data->notified;
                $recordToDb->master_request = $record->data->master_request;
                $recordToDb->api_id = $record->data->api_id;
                $recordToDb->from_url = $record->data->from_url;
                $recordToDb->review_requested = $record->data->review_requested;
                $recordToDb->visit_id = $record->data->visit_id;
                $recordToDb->created_user_id = $record->data->created_user_id;
                $recordToDb->deleted = $record->data->deleted;
                $recordToDb->paid_full = $record->data->paid_full;
                $recordToDb->prepaid = $record->data->prepaid;
                $recordToDb->prepaid_confirmed = $record->data->prepaid_confirmed;
                $recordToDb->last_change_date = date("Y-m-d H:i:s", strtotime($record->data->last_change_date));
                $recordToDb->custom_color = $record->data->custom_color;
                $recordToDb->custom_font_color = $record->data->custom_font_color;
                $recordToDb->record_labels = $record->data->record_labels;
                $recordToDb->activity_id = $record->data->activity_id;
                $recordToDb->custom_fields = $record->data->custom_fields;
                $recordToDb->documents = $record->data->documents;

                $c++;
                $recordToDb->save();
            }

        }
        return $c;
    }


     public function BreakIvideon(Request $request)
    {
       return $persons = DB::table('item_user')->get()->count();
        $res=[];
        $ar=Ycitem::all();
        foreach ($ar as $item){
            $countDbPerson=Ycitem::where('phone',$item->phone)->get()->count();
            if ($countDbPerson>1){
                $res[$item->person_id]=[
                    'person_id' =>$item->person_id,
                    'phone'=>$item->phone
                ];
            }
        }
        return  $res;
  return  $countDbPerson=Ycitem::where('phone','70000000000')->get()->count();
    $camera1='100-HvefJmYJ6Q7MsOm80QPdh0:0';
    $camera2='100-JMnCllnkj7t2B40mdTWydj:0';

   return $eventAll = Curl::to('http://openapi-alpha-eu01.ivideon.com/face_events?op=FIND&access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b')
            ->withHeader('Content-Type: application/json')
            ->withData([
                'face_galleries'=>['100-GVaGUwCF2mHejrHbKykm'],
                "cameras" => [$camera2, $camera1],
//                "faces"=>['100-h2B258QrPDOuRlJjI7yD'],
                'start_time'=>strtotime("2021-03-21 00:00:00"),
                'end_time'=>strtotime("2021-03-21 23:59:00"),
            ])
//            ->asJson()
           ->asJsonRequest()
            ->post();
     $result=[];
     $res='';
     $count=0;
     foreach ($eventAll->result->items as $itemBreak){
         if($itemBreak->camera_id===$camera2)
         $res=date('d.m.Y H:i', $itemBreak->best_shot_time + 7*3600);

         if($itemBreak->camera_id!==$camera2){
             $result[$itemBreak->face_id]=[
                 'time_begin_br' =>$res,
                 'time_end_br' =>date('d.m.Y H:i', $itemBreak->best_shot_time + 7*3600)
                 ];

         }
     }
        return $result;
    }


    //yClients
    public function LoadFromYclients(Request $request)
    {


//   //авторизация yC
        $user=(object)['login'=>'paul@strijevski.ru', 'password'=>'2fz2ex'];
        $token= Curl::to('https://api.yclients.com/api/v1/auth')
            ->withHeader('Content-Type:application/json')
            ->withHeader('Accept:application/vnd.yclients.v2+json')
            ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2')
            ->withData($user)
             ->asJson()
            ->post();
//
//       //список компаний
//        $companiesYc=Curl::to('https://api.yclients.com/api/v1/companies?my=1')
//            ->withHeader('Content-Type:application/json')
//            ->withHeader('Accept:application/vnd.yclients.v2+json')
//            ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2, User '. $token->data->user_token)
//            ->asJson()
//            ->asJsonRequest()
//            ->get();
//    return $companiesYc;
//     определяем количество страниц $totalPage
//        $data=(object)['page_size'=>1];
//        $totalPageYc=Curl::to('https://api.yclients.com/api/v1/company/215098/clients/search')
//            ->withHeader('Content-Type:application/json')
//            ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2, User '. $token->data->user_token)
//            ->withData($data)
//            ->asJson()
////          ->asJsonRequest()
//            ->post();
//       $totalPage=ceil($totalPageYc->meta->total_count/25);
//
//        $clientsId=[];
////        выбираем из yC все id клиентов одного салона, формируем массив из id
//       for($i=1;$i<=$totalPage;$i++) {
//           $clientsYc = Curl::to('https://api.yclients.com/api/v1/company/215098/clients/search')
//               ->withHeader('Content-Type:application/json')
//               ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2, User ' . $token->data->user_token)
//               ->withData((object)['page_size'=>25,'page' => $i])
//               ->asJson()
////            ->asJsonRequest()
//               ->post();
//
//           foreach ($clientsYc->data as $itemId) {
//               array_push($clientsId, $itemId->id);
//           }
//       }
//
//
////    выбираем из yC всех клиентов одного салона, но уже с данными
//
//        foreach ($clientsId as $itemClient) {
//            $client = Curl::to('https://api.yclients.com/api/v1/client/215098/' . $itemClient)
//                ->withHeader('Content-Type:application/json')
//                ->withHeader('Accept:application/vnd.yclients.v2+json')
//                ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2, User ' . $token->data->user_token)
//                ->withData((object)['page' => $i])
////                ->asJsonRequest()
//                ->asJson()
//                ->get();
//
//
//            $ycClients[] =
//                [
//                    'yc_id' => $client->data->id,
//                    'name' => $client->data->name,
//                    'phone' => '7' . substr(trim($client->data->phone), -10),
//                    'email' => $client->data->email,
//                    'birth_date' => ($client->data->birth_date),
//                    'categories' => (object)($client->data->categories),
//                    'sex_id' => $client->data->sex_id,
//                    'sex' => $client->data->sex,
//                    'discount' => $client->data->discount,
//                    'card' => $client->data->card,
//                    'importance_id' => $client->data->importance_id,
//                    'importance' => $client->data->importance,
//                    'comment' => $client->data->comment,
//                    'sms_check' => $client->data->sms_check,
//                    'sms_bot' => $client->data->sms_bot,
//                    'paid' => $client->data->paid,
//                    'spent' => $client->data->spent,
//                    'balance' => $client->data->balance,
//                    'visits' => $client->data->visits,
//                    'last_change_date' => $client->data->last_change_date,
//                    'custom_fields' => ($client->data->custom_fields)
//                ];
//
//        }
//
//
////            загрузим данные yc в базу
//        $count=0;
//        foreach($ycClients as $itemsYcPerson){
//
//            $ycItem=new Ycdb();
//            $ycItem->yc_id=$itemsYcPerson['yc_id'];
//            $ycItem->department_id='1b8e1451-ec5a-41cf-bd98-38913361e2cd';
//            $ycItem->name=$itemsYcPerson['name'];
//
//            $ycItem->birth_date=$itemsYcPerson['birth_date'];
//            $ycItem->phone=$itemsYcPerson['phone'];
//            $ycItem->email=$itemsYcPerson['email'];
//            $ycItem->categories=($itemsYcPerson['categories']);
//            $ycItem->sex_id=$itemsYcPerson['sex_id'];
//            $ycItem->sex=$itemsYcPerson['sex'];
//            $ycItem->discount=$itemsYcPerson['discount'];
//            $ycItem->card=$itemsYcPerson['card'];
//            $ycItem->importance_id=$itemsYcPerson['importance_id'];
//            $ycItem->importance=$itemsYcPerson['importance'];
//            $ycItem->comment=$itemsYcPerson['comment'];
//            $ycItem->sms_check=$itemsYcPerson['sms_check'];
//            $ycItem->sms_bot=$itemsYcPerson['sms_bot'];
//            $ycItem->spent=$itemsYcPerson['spent'];
//            $ycItem->paid=$itemsYcPerson['paid'];
//            $ycItem->balance=$itemsYcPerson['balance'];
//            $ycItem->visits=$itemsYcPerson['visits'];
//            $ycItem->last_change_date=$itemsYcPerson['last_change_date'];
//            $ycItem->custom_fields=$itemsYcPerson['custom_fields'];
//            $ycItem->save();
//            $count++;
//        }
//
//return $count;

        $duplicateUser=[];
        $duplicateYcItems=[];
        $ycClients=Ycdb::all();
        $dbPerson=Person::all();
        $ycItemsRecord=Ycitem::all();
        $count=0;
        foreach($ycClients as $itemsYcPerson)
            foreach ($dbPerson as $icItem )
            {
                if($itemsYcPerson['phone']==$icItem['phone'] )
                {
                    $ycItem = new Ycitem();
                    $ycItem->ycitem_id = Str::uuid()->toString();
                    $ycItem->person_id=$icItem->person_id;
                    $ycItem->yc_id = $itemsYcPerson['yc_id'];
                    $ycItem->department_id = $itemsYcPerson['department_id'];
                    $ycItem->chain_id = 'e8e5bdae-332d-42f7-b80f-bd1e4371aca3';
                    $ycItem->name = $itemsYcPerson['name'];
                    $ycItem->phone = preg_replace('/[^0-9]/', '', $itemsYcPerson['phone']);
                    $ycItem->email = $itemsYcPerson['email'];
                    $ycItem->categories = json_encode($itemsYcPerson['categories']);
                    $ycItem->sex_id = $itemsYcPerson['sex_id'];
                    $ycItem->sex = $itemsYcPerson['sex'];
                    $ycItem->birth_date = $itemsYcPerson['birth_date'];
                    $ycItem->discount = $itemsYcPerson['discount'];
                    $ycItem->card = $itemsYcPerson['card'];
                    $ycItem->comment = $itemsYcPerson['comment'];
                    $ycItem->sms_check = $itemsYcPerson['sms_check'];
                    $ycItem->sms_bot = $itemsYcPerson['sms_bot'];
                    $ycItem->spent = $itemsYcPerson['spent'];
                    $ycItem->paid = $itemsYcPerson['paid'];
                    $ycItem->balance = $itemsYcPerson['balance'];
                    $ycItem->visits = $itemsYcPerson['visits'];
                    $ycItem->importance_id = $itemsYcPerson['importance_id'];
                    $ycItem->last_change_date = $itemsYcPerson['last_change_date'];
                    $ycItem->importance = $itemsYcPerson['importance'];
                    $ycItem->custom_fields = json_encode($itemsYcPerson->custom_fields);
                    $ycItem->save();
                    $count++;
                   }
            }

return $count;
        foreach($ycClients as $itemsYcPerson)
          {
            $personExists = false;
              foreach ($dbPerson as $dbPersonItem)
             {
                if($itemsYcPerson['phone']==$dbPersonItem['phone'])
               {  $duplicateUser[] = [
                       'name' => $itemsYcPerson->name,
                       'birth_date' => $itemsYcPerson->birth_date,
                       'phone' => $itemsYcPerson->phone,
                       'email' => $itemsYcPerson->email,
                       'categories' => $itemsYcPerson->categories,
                       'sex_id' => $itemsYcPerson->sex_id,
                       'sex' => $itemsYcPerson->sex,
                       'discount' => $itemsYcPerson->discount,
                       'card' => $itemsYcPerson->card,
                       'importance_id' => $itemsYcPerson->importance_id,
                       'importance' => $itemsYcPerson->importance,
                       'comment' => $itemsYcPerson->comment,
                       'sms_check' => $itemsYcPerson->sms_check,
                       'sms_bot' => $itemsYcPerson->sms_bot,
                       'spent' => $itemsYcPerson->spent,
                       'paid' => $itemsYcPerson->paid,
                       'balance' => $itemsYcPerson->balance,
                       'visits' => $itemsYcPerson->visits,
                       'last_change_date' => $itemsYcPerson->last_change_date,
                       'custom_fields' => ($itemsYcPerson->custom_fields)
                   ];

                   $personExists = true;
                    break;
               }

            $countDbPerson=Person::where('phone',$itemsYcPerson->phone)->get()->count();
            if($countDbPerson>0){ $personExists = true;continue;}
                 $countYcItem=Ycitem::where('phone','=',$itemsYcPerson->phone and 'department_id','<>',$itemsYcPerson['department_id'])->get()->count();


                 if (!$personExists ) {
                   $item = Item::where('name', '=', 'Клиент')->first();
                   $chain = Chain::where('chain_id', '=', 'e8e5bdae-332d-42f7-b80f-bd1e4371aca3')->first();
                   $person = new Person();
                   $person->person_id = Str::uuid()->toString();
                   if ($itemsYcPerson['custom_fields']) {
                       $person->person_ivideon_id = $itemsYcPerson['custom_fields']['ivideon'];
                   }
                   $person->firstname = $itemsYcPerson['name'];
                   $person->sex = $itemsYcPerson['sex'];
                   $person->phone = $itemsYcPerson['phone'];
                   $person->birth_date = $itemsYcPerson['birth_date'];
                   $person->email = $itemsYcPerson['email'];
                   $person->save();

                   $ycItem = new Ycitem();
                   $ycItem->ycitem_id = Str::uuid()->toString();
                   $ycItem->yc_id = $itemsYcPerson['yc_id'];
                   $ycItem->department_id = $itemsYcPerson['department_id'];
                   $ycItem->chain_id = 'e8e5bdae-332d-42f7-b80f-bd1e4371aca3';
                   $ycItem->name = $itemsYcPerson['name'];
                   $ycItem->phone = preg_replace('/[^0-9]/', '', $itemsYcPerson['phone']);
                   $ycItem->email = $itemsYcPerson['email'];
                   $ycItem->categories = json_encode($itemsYcPerson['categories']);
                   $ycItem->sex_id = $itemsYcPerson['sex_id'];
                   $ycItem->sex = $itemsYcPerson['sex'];
                   $ycItem->birth_date = $itemsYcPerson['birth_date'];
                   $ycItem->discount = $itemsYcPerson['discount'];
                   $ycItem->card = $itemsYcPerson['card'];
                   $ycItem->comment = $itemsYcPerson['comment'];
                   $ycItem->sms_check = $itemsYcPerson['sms_check'];
                   $ycItem->sms_bot = $itemsYcPerson['sms_bot'];
                   $ycItem->spent = $itemsYcPerson['spent'];
                   $ycItem->paid = $itemsYcPerson['paid'];
                   $ycItem->balance = $itemsYcPerson['balance'];
                   $ycItem->visits = $itemsYcPerson['visits'];
                   $ycItem->importance_id = $itemsYcPerson['importance_id'];
                   $ycItem->last_change_date = $itemsYcPerson['last_change_date'];
                   $ycItem->importance = $itemsYcPerson['importance'];
                   $ycItem->custom_fields = json_encode($itemsYcPerson->custom_fields);

                   $item->persons()->attach($person->person_id, ['item_id' => $item->item_id, 'updated_at' => now(), 'created_at' => now()]);
                   $chain->personHasChains()->attach($person->person_id, ['chain_id' => $chain->chain_id, 'updated_at' => now(), 'created_at' => now()]);
                   $person->ycitems()->save($ycItem);
               }
           }
          }



                $result[]=[
                    'duplicateUser'=>$duplicateUser,
                ];



               return $result;

    }
    /**<span class="v-text">5c8pfgzh7tp6fw8b22d2</span>
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function Load()
    {
      $arrPerson = Curl::to('http://openapi-alpha-eu01.ivideon.com/faces?op=FIND&access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b')
             ->withHeader('Content-Type: application/json')
            ->withHeader('Authorization: Basic access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b' )
            ->withData([
//                'face_galleries'=>["100-GVaGUwCF2mHejrHbKykm"],
            ])
            ->asJson()
//           ->asJsonRequest()
            ->post();


        foreach ($arrPerson->result->items as $itemperson){
            $person=new Person();
            $person->person_ivideon_id = $itemperson->id;
            $person->person_id = Str::uuid()->toString();
            $person->work_posts_id = 1;
            $person->role_id = 1;
            $person->avatar=$itemperson->photos[0]->thumbnails->original->url;
            $person->name = $itemperson->description;
            $person->save();
        }

        return response()->json([
            'persons'=>Person::latest()->get()
        ],200);

    }

    //загрузка одного id_ivideon с Ivideon, которого нет в базе
    public function LoadIvideonPerson($id)
    {
        $ivideonPerson = Curl::to('http://openapi-alpha-eu01.ivideon.com/faces/'.$id.'?op=GET&access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b')
            ->withHeader('Content-Type: application/json')
            ->withHeader('Authorization: Basic access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b' )
            ->withData()
            ->post();
    $person= json_decode($ivideonPerson, true);
     $photos=[];

      for ($itemPhoto=0; $itemPhoto<count($person['result']['photos']); $itemPhoto++)
      {
          array_push($photos,$person['result']['photos'][$itemPhoto]['thumbnails']['original']['url']);
      }
                $result=[
               'ivideon_id' => $person['result']['id'],
               'face_gallery_id' =>$person['result']['face_gallery_id'],
               'person' => $person['result']['person'],
               'name' => $person['result']['description'],
               'photos' => $photos
           ];
       return $result;
    }

// Загрузка всех новых id_ivideon с Ivideon, которых нет в базе
    public function LoadIdIvideon()
    {
        $arrDbPerson=Person::all();
        $arrIvideonPerson = Curl::to('http://openapi-alpha-eu01.ivideon.com/faces?op=FIND&access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b')
            ->withHeader('Content-Type: application/json')
            ->withHeader('Authorization: Basic access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b' )
            ->withData([
                'face_galleries'=>["100-GVaGUwCF2mHejrHbKykm"],
            ])
            ->asJson()
            ->post();
        $newPersons = [];
        foreach($arrIvideonPerson->result->items as $itemsIvideonPerson) {
            $personExists = false;
            foreach($arrDbPerson as $itemsDbPerson) {
                if($itemsDbPerson->person_ivideon_id === $itemsIvideonPerson->id) {
                    $personExists = true;
                    break;
                }
            }
            if (!$personExists) {
                $photos=[];
                for($itemPhoto=0; $itemPhoto<count($itemsIvideonPerson->photos); $itemPhoto++)
                {
                    array_push($photos, $itemsIvideonPerson->photos[$itemPhoto]->thumbnails->original->url);
                }
                $newPersons[] = [
                    'id_ivideon'=>$itemsIvideonPerson->id,
                    'face_gallery_id' =>$itemsIvideonPerson->face_gallery_id,
                    'persons' =>$itemsIvideonPerson->person,
                    'name'=>$itemsIvideonPerson->description,
                    'photos'=>$photos
                ];
            }
        }
        return json_encode($newPersons);
    }



    public function loadCamera()
    {
        $cameraAll = Curl::to('http://openapi-alpha-eu01.ivideon.com/cameras?op=FIND&access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b')
            ->withHeader('Content-Type: application/json')
            ->withHeader('Authorization: Basic access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b' )
            ->withData([
                'limit'=>100,
            ])
            ->asJson()
//           ->asJsonRequest()
            ->post();

        foreach ($cameraAll->result->items as $itemCamera) {
            $camera = new Camera();
            $camera->camera_id = Str::uuid()->toString();
            $camera->camera_ivideon_id = $itemCamera->id;
            $camera->camera_name = $itemCamera->name;
            $camera->camera_adress = $itemCamera->name;
            $camera->save();
        }
        return 'ok';
    }




    public function index()
    {

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
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
