<?php


namespace App\Services;


use App\Mail\MailReporlAllDepartment;
use App\Mail\MailReport;
use App\Mail\ReportRecords;
use App\Models\Report;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Ixudra\Curl\Facades\Curl;
use App\Models\Person;


class ReportService
{
    public function __invoke()
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
//выгружаем с ivideon предфдущий день день
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
            'previousResult'=> $previousResult ?? null,
            'currentResult' => $currentResult ?? null
        ];
        //сохраняем в BIS
        Report::create([
            'type'=>'Отчет по салонам. Ivideon',
             'data'=>$result]);
        //сохраняем в airtable
        $data=["fields"=>["Name"=>date("Y-m-d").'_Report_Ivideon', "Notes"=>json_encode($result,JSON_UNESCAPED_UNICODE)]];
        Curl::to('https://api.airtable.com/v0/appOOds7b02Z6yjg1/video_report')
//            ->withHeader('Content-Type:application/json')
            ->withHeader('Authorization:Bearer keyVWFtF8wCTJ6gjs')
            ->withData($data)
            ->post();
//отправляем на почту
//        $email = ["9237857776@mail.ru", "paul@strijevski.ru"];
//        Mail::to($email)->send(new MailReporlAllDepartment($result));
    }

}
