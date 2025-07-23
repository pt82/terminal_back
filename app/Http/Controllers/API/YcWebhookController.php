<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Chain;
use App\Models\Department;
use App\Models\Item;
use App\Models\Person;
use App\Models\User;
use App\Models\Ycdb;
use App\Models\Ycitem;
use App\Models\Ycrecord;
use App\Models\Yctransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Ixudra\Curl\Facades\Curl;

class YcWebhookController extends Controller
{
    public function hook(Request $request)
    {
//        Log::channel('yc_webhook')->info($request);
        $test = new Ycdb();
        $test->custom_fields = $request->all();
        $test->comment = $request->resource;
        $test->save();
        $data = $request->all();

        if ($data["resource"] == "record" || $data["resource"] == "finances_operation") {
            //авторизация yC
            $user = (object)['login' => 'paul@strijevski.ru', 'password' => '2fz2ex'];
            $token = Curl::to('https://api.yclients.com/api/v1/auth')
                ->withHeader('Content-Type:application/json')
                ->withHeader('Accept:application/vnd.yclients.v2+json')
                ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2')
                ->withData($user)
                ->asJson()
                ->post();

            $companyId = Department::where('yc_company_id', $data['company_id'])->first();
            $chain_id = DB::table('chains')
                ->Join('departments', 'departments.chain_id', '=', 'chains.id')
                ->select('chains.chain_id')
                ->where('departments.id', $companyId->id)
                ->first();
            $data['data']['department_id'] = $companyId->id;
            $data['data']['chain_id'] = $companyId->chain_id;
            if($data['data']['client']) {
                $client = Curl::to('https://api.yclients.com/api/v1/client/' . $data['company_id'] . '/' . $data['data']['client']['id'])
                    ->withHeader('Content-Type:application/json')
                    ->withHeader('Accept:application/vnd.yclients.v2+json')
                    ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2, User ' . $token->data->user_token)
                    ->withData((object)['page_size' => 200])
                    ->asJson()
//                   ->asJsonRequest()
                    ->get();
                $countPhone = User::where('phone', preg_replace('/[^0-9]/', '', $data['data']['client']['phone']))->first();
                if ($countPhone) {
                    $userId = $countPhone->id;
                   }
                if (!$countPhone) {
                    if ($client->data != null) {
                        $user = new User();
                        $user->person_id = Str::uuid()->toString();
                        if (isset($client->data->custom_fields->ivideon)) {
                            $user->person_ivideon_id = $client->data->custom_fields->ivideon;
                        }
                        $user->firstname = $client->data->name;
                        $user->yc_name = $client->data->name;
                        $user->yc_name = $client->data->name;
                        $user->sex = $client->data->sex;
                        $user->phone = preg_replace('/[^0-9]/', '', $client->data->phone);
                        if ($client->data->birth_date) {
                            $user->birth_date = date("Y-m-d", strtotime($client->data->birth_date));

                        }
                        if ($client->data->email) {
                            $user->email = $client->data->email;
                        }
                        $user->comment = $client->data->comment;
//                    $user->comment = 'WebhookYcAddUser';
                        $user->save();
                        $userId = $user->id;
                        $ycItem = new Ycitem();
                        $ycItem->ycitem_id = Str::uuid()->toString();
                        $ycItem->yc_id = $client->data->id;
                        $ycItem->department_id = $companyId->department_id;
                        $ycItem->chain_id = $chain_id->chain_id;
                        $ycItem->name = $client->data->name;
                        $ycItem->phone = preg_replace('/[^0-9]/', '', $client->data->phone);
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

//                        $item = Item::where('name', '=', 'Клиент')->first();
                        $chain = Chain::where('chain_id', '=', $chain_id->chain_id)->first();
//                        $item->persons()->attach($user->person_id, ['item_id' => $item->item_id, 'updated_at' => now(), 'created_at' => now()]);
                        $chain->personHasChains()->attach($user->id, ['chid' => $chain->id, 'updated_at' => now(), 'created_at' => now()]);
                        $user->ycitems()->save($ycItem);
                    }
                }
                $data['data']['user_id'] = $userId ?? NULL;
            }

            if ($data["resource"] == "record") {
//                $record = Curl::to('https://api.yclients.com/api/v1/record/' . $companyId->yc_company_id . '/' . $data['resource_id'])
//                    ->withHeader('Content-Type:application/json')
//                    ->withHeader('Accept:application/vnd.yclients.v2+json')
//                    ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2, User ' . $token->data->user_token)
//                    ->get();
//
//                $dataRec = json_decode($record, true);
//                $dataRec['data']['record_id'] = $dataRec['data']['id'];


                $data['data']['record_id'] = $data['data']['id'];
                unset ($data['data']['id']);
                if (isset($data['data']['date'])) {
                    $data['data']['date'] = date("Y-m-d H:i:s", strtotime($data['data']['date']));
                }
                if (isset($data['data']['datetime'])) {
                    $data['data']['datetime'] = date("Y-m-d H:i:s", strtotime($data['data']['datetime']));
                }
                if (isset($data['data']['create_date'])) {
                    $data['data']['create_date'] = date("Y-m-d H:i:s", strtotime($data['data']['create_date']));
                }
                if (isset($data['data']['last_change_date'])) {
                    $data['data']['last_change_date'] = date("Y-m-d H:i:s", strtotime($data['data']['last_change_date']));
                }
                if ($data['status'] == 'create') {
                    if(!empty(Ycrecord::where('record_id',$data['data']['record_id'])->first())) {exit;}
                    Ycrecord::create($data['data']);
                    exit;
                }
                if ($data['status'] == 'update') {
//                $dataRec['data']['comment']='webhoookYcUpdate';
                    Ycrecord::where('record_id', $data['resource_id'])->update($data['data']);
                    exit;
                }
                if ($data['status'] == 'delete') {
                    Ycrecord::where('record_id', $data['resource_id'])->delete();
                    exit;

                }
            }
            if ($data["resource"] == "finances_operation") {

//                $ycTransaction = Curl::to('https://api.yclients.com/api/v1/timetable/transactions/' . $companyId->yc_company_id . '?visit_id=' . $data['data']['visit_id'])
//                    ->withHeader('Content-Type:application/json')
//                    ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2, User ' . $token->data->user_token)
////            ->withData($data)
////            ->asJsonRequest()
////            ->asJson()
//                    ->get();
//
//                $transaction = json_decode($ycTransaction, true);
               $recordId = Ycrecord::where('record_id', $data['data']['record_id'])->first();
               if(!$recordId) {
                    unset($data['data']['record_id']);
                }
               if($recordId){
                   $data['data']['record_id']=$recordId->id;
               }
                $data['data']['date'] = date("Y-m-d H:i:s", strtotime($data['data']['date']));
                $data['data']['last_change_date'] = date("Y-m-d H:i:s", strtotime($data['data']['last_change_date']));
                $data['data']['transaction_id'] = $data['data']['id'];
                unset($data['data']['id']);
                if ($data['status'] == 'create') {
                    if(!empty(Yctransaction::where('transaction_id',$data['data']['transaction_id'])->first())){exit;}
                    Yctransaction::create($data['data']);
                    exit;
                }
                if ($data['status'] == 'update') {
//                $dataRec['data']['comment']='webhoookYcUpdate';
                    Yctransaction::where('transaction_id', $data['resource_id'])->update($data['data']);
                    exit;
                }
                if ($data['status'] == 'delete') {
                    Yctransaction::where('transaction_id', $data['resource_id'])->delete();
                    exit;
                }
            }

            exit;
        }
    }
}

