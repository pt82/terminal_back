<?php


namespace App\Services;


use App\Models\Chain;
use App\Models\Item;
use App\Models\Person;
use App\Models\Ycitem;
use App\Models\Ycrecord;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Ixudra\Curl\Facades\Curl;

class NewClientYcService
{
    public function __invoke()
    {
        if (App::environment(['test','production'])) {
            exit;
        }
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
//        $item = Item::where('name', '=', 'Клиент')->first();
        $chain = Chain::where('chain_id', '=', 'e8e5bdae-332d-42f7-b80f-bd1e4371aca3')->first();

        //выбираем все записи за текущий день по всем салонам
        foreach ($companiesYc->data as $companiesId) {
            $clientsYc = Curl::to('https://api.yclients.com/api/v1/records/' . $companiesId->id)
                ->withHeader('Content-Type:application/json')
                ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2, User ' . $token->data->user_token)
                ->withData(['changed_after' => $dategegin, 'changed_before' => $dateend])
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

//                        $item->persons()->attach($person->person_id, ['item_id' => $item->item_id, 'updated_at' => now(), 'created_at' => now()]);
                        $chain->personHasChains()->attach($person->id, ['chid' => $chain->id, 'updated_at' => now(), 'created_at' => now()]);
                        $person->ycitems()->save($ycItem);

                    }
                }
            }
        }


        // новая запись и обновление измененной запси за текущий день
        foreach ($idClientYc as $clientYc) {
            $user_id = DB::table('ycitems')
                ->join('users', 'users.person_id', '=', 'ycitems.person_id')
                ->select('users.id')
                ->where('ycitems.yc_id', '=', $clientYc['id'])->get();
            //проверяем есть такая запись в базе, если нет то создаем ее
            $cointRecord = Ycrecord::where('record_id', $clientYc['record_id'])->get()->count();
            foreach ($companiesYc->data as $companiesId) {
                $record = Curl::to('https://api.yclients.com/api/v1/record/' . $companiesId->id . '/' . $clientYc['record_id'])
                    ->withHeader('Content-Type:application/json')
                    ->withHeader('Accept:application/vnd.yclients.v2+json')
                    ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2, User ' . $token->data->user_token)
                    ->asJson()
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
                if ($cointRecord == 0) {
                    $recordToDb = new Ycrecord();
                }
                if ($cointRecord > 0) {
                    $recordToDb = Ycrecord::where('record_id',$clientYc['record_id'])->first();
                }
                if (isset($user_id[0]->id)) {
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

                    $recordToDb->save();
                }
            }
        }
    }
}
