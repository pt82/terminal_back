<?php

namespace App\Http\Controllers\API;

use App\Exceptions\Handler;
use App\Http\Controllers\Controller;
use App\Mail\ReportRecords;
use App\Models\Chain;
use App\Models\Department;
use App\Models\Group;
use App\Models\Report;
use App\Models\Ycdb;
use App\Models\Ycrecord;
use App\Models\YcService;
use App\Models\Yctransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Ixudra\Curl\Facades\Curl;


class ReportController extends Controller
{
//отчет по клиентам и записям
    public function profitReport()
    {
        $chain = Chain::all();
        $beginDay = Carbon::now()->setTimezone('Africa/Accra')->format('Y-m-d 00:00:00');
        $endDay = Carbon::now()->setTimezone('Africa/Accra')->format('Y-m-d 23:59:00');

        foreach ($chain as $chainOne) {
            $result = [];
            $departments = Department::where('chain_id', $chainOne->id)->get();
            $resultAirtable = '';
            //отчет по сети
            //общая сумма по салону
            $sumChain = Yctransaction::where('date', '>=', $beginDay)
                ->where('date', '<=', $endDay)
                ->where('chain_id', $chainOne->id)
                ->where('amount','>',0)
                ->sum('amount');
            //расходы
            $expenseChain = Yctransaction::where('date', '>=', $beginDay)
                ->where('date', '<=', $endDay)
                ->where('chain_id', $chainOne->id)
                ->where('amount','<',0)
                ->sum('amount');
            //услуги
            $countServiceChain = Yctransaction::where('date', '>=', $beginDay)
                ->where('date', '<=', $endDay)
                ->where('chain_id', $chainOne->id)
                ->whereIn('expense->title', ["Оказание услуг"])
                ->count();
            $sumServiceChain = Yctransaction::where('date', '>=', $beginDay)
                ->where('date', '<=', $endDay)
                ->where('chain_id', $chainOne->id)
                ->whereIn('expense->title', ["Оказание услуг"])
                ->sum('amount');
            //Товары
            $countProductChain = Yctransaction::where('date', '>=', $beginDay)
                ->where('date', '<=', $endDay)
                ->where('chain_id', $chainOne->id)
                ->whereIn('expense->title', ["Продажа товаров"])
                ->count();
            $sumProductChain = Yctransaction::where('date', '>=', $beginDay)
                ->where('date', '<=', $endDay)
                ->where('chain_id', $chainOne->id)
                ->whereIn('expense->title', ["Продажа товаров"])
                ->where('amount','>',0)
                ->sum('amount');

            //расчетный счет
            $PCChain = Yctransaction::where('date', '>=', $beginDay)
                ->where('date', '<=', $endDay)
                ->where('chain_id', $chainOne->id)
                ->whereIn('account->title', ["Расчетный счет"])
                ->where('amount','>',0)
                ->sum('amount');
            //наличные
            $nalChain = Yctransaction::where('date', '>=', $beginDay)
                ->where('date', '<=', $endDay)
                ->where('chain_id', $chainOne->id)
                ->whereIn('account->title', ["Основная касса", "Безналичный расчет"])
                ->where('amount','>',0)
                ->sum('amount');

            //всего записей за день
            $countRecTotalChain = Ycrecord::where('date', '>=', $beginDay)
                ->where('date', '<=', $endDay)
                ->where('chain_id', $chainOne->id)
                ->count();
            //записи с оплатой
            $countRecWithPayChain = DB::table('ycrecords')
                ->leftJoin('yctransactions', 'yctransactions.record_id', '=', 'ycrecords.id')
                ->where('ycrecords.attendance', '=', 1)
                ->where('ycrecords.date', '>=', $beginDay)
                ->where('ycrecords.date', '<=', $endDay)
                ->where('ycrecords.chain_id', $chainOne->id)
                ->whereNotNull('yctransactions.record_id')
                ->count();
            //без оплаты
            $countRecWithNotPayChain = DB::table('ycrecords')
                ->leftJoin('yctransactions', 'yctransactions.record_id', '=', 'ycrecords.id')
                ->where('ycrecords.attendance', '=', 1)
                ->where('ycrecords.date', '>=', $beginDay)
                ->where('ycrecords.date', '<=', $endDay)
                ->where('ycrecords.chain_id', $chainOne->id)
                ->whereNull('yctransactions.record_id')
                ->count();
            //не пришли
            $countRecCancelChain = Ycrecord::where('date', '>=', $beginDay)
                ->where('date', '<=', $endDay)
                ->where('chain_id', $chainOne->id)
                ->where('attendance', '=', -1)
                ->count();
            //новые записи
            $countRecNewChain = Ycrecord::
            where('created_at', '>=', $beginDay)
                ->where('created_at', '<=', $endDay)
                ->where('chain_id', $chainOne->id)
                ->count();

            //новые клиенты
            $countNewUserChain = Ycrecord::
            where('date', '>=', $beginDay)
                ->where('date', '<=', $endDay)
                ->where('chain_id', $chainOne->id)
                ->whereIn('user_id', DB::table('ycrecords')
                    ->select(DB::raw('count(user_id)'), 'user_id')
                    ->select('user_id')
                    ->groupBy('user_id')
                    ->havingRaw('count(user_id)=1')
//                    ->where('chain_id', 1)
                    ->pluck('user_id'))
                ->get()
                ->count();
            //повторные (2-3 посещения)
            $countReplayUser_2_3Chain = Ycrecord::
            where('date', '>=', $beginDay)
                ->where('date', '<=', $endDay)
                ->where('chain_id', $chainOne->id)
                ->whereIn('user_id', DB::table('ycrecords')
                    ->select(DB::raw('count(user_id)'), 'user_id')
                    ->select('user_id')
                    ->groupBy('user_id')
                    ->havingRaw('count(user_id)>=2 and count(user_id)<=3')
//                    ->where('chain_id', 1)
                    ->pluck('user_id'))
                ->get()
                ->count();
            //лояльные (4-8 посещения)
            $countReplayUser_4_8Chain = Ycrecord::
            where('date', '>=', $beginDay)
                ->where('date', '<=', $endDay)
                ->where('chain_id', $chainOne->id)
                ->whereIn('user_id', DB::table('ycrecords')
                    ->select(DB::raw('count(user_id)'), 'user_id')
                    ->select('user_id')
                    ->groupBy('user_id')
                    ->havingRaw('count(user_id)>=4 and count(user_id)<=8')
//                    ->where('chain_id', 1)
                    ->pluck('user_id'))
                ->get()
                ->count();
            //постоянные (9 и более)
            $countReplayUser_9Chain = Ycrecord::
            where('date', '>=', $beginDay)
                ->where('date', '<=', $endDay)
                ->where('chain_id', $chainOne->id)
                ->whereIn('user_id', DB::table('ycrecords')
                    ->select(DB::raw('count(user_id)'), 'user_id')
                    ->select('user_id')
                    ->groupBy('user_id')
                    ->havingRaw('count(user_id)>=9')
//                    ->where('chain_id', 1)
                    ->pluck('user_id'))
                ->get()
                ->count();

            array_push($result, [
                'Дата:' => date("d.m.Y"),
                'Сеть' => $chainOne->name,
                'Общий' => $sumChain,
                'Расходы' =>  abs($expenseChain),
                'Услуги' => '(' . $countServiceChain . ')' . $sumServiceChain,
                'Косметика' => '(' . $countProductChain . ')' . $sumProductChain,
                'Безнал' => $PCChain,
                'Наличными' => $nalChain,
                'Записей на сегодня' => $countRecTotalChain,
                'Cостоялось с оплатой' => $countRecWithPayChain,
                'Без оплаты(сертификат)' => $countRecWithNotPayChain,
                'Не пришли' => $countRecCancelChain,
                'Новые записи' => $countRecNewChain,
                'Новые клиенты' => $countNewUserChain,
                'Повторные (2-3 посещения)' => $countReplayUser_2_3Chain,
                'Лояльные (4-8 посещения)' => $countReplayUser_4_8Chain,
                'Постоянные (9 и больше)' => $countReplayUser_9Chain
            ]);

            $resultAirtable = '*' . date("d.m.Y") . '*
        *Сеть Стрижевский*
        *' . $sumChain . '*
        Услуги( ' . $countServiceChain . '): ' . $sumServiceChain . '
        Косметика (' . $countProductChain . '): ' . $sumProductChain . '
        ________________________
        Безнал: ' . $PCChain . '
        Наличные: ' . $nalChain . '
        ________________________
        Записей на сегодня: ' . $countRecTotalChain . '
        Состоялось с оплатой: ' . $countRecWithPayChain . '
        Без оплаты (сертификат): ' . $countRecWithNotPayChain . '
        Не пришли: ' . $countRecCancelChain . '

        *Новые записи - ' . $countRecNewChain . '*
        *Новые клиенты - ' . $countNewUserChain . '*
        Повторные (2-3 посещения): ' . $countReplayUser_2_3Chain . '
        Лояльные (4-8 посещения): ' . $countReplayUser_4_8Chain . '
        Постоянные (9 и больше): ' . $countReplayUser_9Chain;

            //по салонам $chain_id=1
            foreach ($departments as $departmentOne) {
                //общая сумма по салону
                $sumDepartment = Yctransaction::where('date', '>=', $beginDay)
                    ->where('date', '<=', $endDay)
                    ->where('department_id', $departmentOne->id)
                    ->where('amount','>',0)
                    ->sum('amount');
                //услуги
                $countService = Yctransaction::where('date', '>=', $beginDay)
                    ->where('date', '<=', $endDay)
                    ->where('department_id', $departmentOne->id)
                    ->whereIn('expense->title', ["Оказание услуг"])
                    ->count();
                $sumService = Yctransaction::where('date', '>=', $beginDay)
                    ->where('date', '<=', $endDay)
                    ->where('department_id', $departmentOne->id)
                    ->whereIn('expense->title', ["Оказание услуг"])
                    ->where('amount','>',0)
                    ->sum('amount');
                $expensService = Yctransaction::where('date', '>=', $beginDay)
                    ->where('date', '<=', $endDay)
                    ->where('department_id', $departmentOne->id)
                    ->whereIn('expense->title', ["Оказание услуг"])
                    ->where('amount','<',0)
                    ->sum('amount');
                //косметика
                $countProduct = Yctransaction::where('date', '>=', $beginDay)
                    ->where('date', '<=', $endDay)
                    ->where('department_id', $departmentOne->id)
                    ->whereIn('expense->title', ["Продажа товаров"])
                    ->count();
                $sumProduct = Yctransaction::where('date', '>=', $beginDay)
                    ->where('date', '<=', $endDay)
                    ->where('department_id', $departmentOne->id)
                    ->whereIn('expense->title', ["Продажа товаров"])
                    ->where('amount','>',0)
                    ->sum('amount');

                //расчетный счет
                $PC = Yctransaction::where('date', '>=', $beginDay)
                    ->where('date', '<=', $endDay)
                    ->where('department_id', $departmentOne->id)
                    ->whereIn('account->title', ["Расчетный счет"])
                    ->where('amount','>',0)
                    ->sum('amount');
                //наличные
                $nal = Yctransaction::where('date', '>=', $beginDay)
                    ->where('date', '<=', $endDay)
                    ->where('department_id', $departmentOne->id)
                    ->whereIn('account->title', ["Основная касса", "Безналичный расчет"])
                    ->where('amount','>',0)
                    ->sum('amount');

                //всего записей за день
                $countRecTotal = Ycrecord::where('date', '>=', $beginDay)
                    ->where('date', '<=', $endDay)
                    ->where('department_id', $departmentOne->id)
                    ->count();
                //записи с оплатой
                $countRecWithPay = DB::table('ycrecords')
                    ->leftJoin('yctransactions', 'yctransactions.record_id', '=', 'ycrecords.id')
                    ->where('ycrecords.attendance', '=', 1)
                    ->where('ycrecords.date', '>=', $beginDay)
                    ->where('ycrecords.date', '<=', $endDay)
                    ->where('ycrecords.department_id', $departmentOne->id)
                    ->whereNotNull('yctransactions.record_id')
                    ->count();
                //без оплаты
                $countRecWithNotPay = DB::table('ycrecords')
                    ->leftJoin('yctransactions', 'yctransactions.record_id', '=', 'ycrecords.id')
                    ->where('ycrecords.attendance', '=', 1)
                    ->where('ycrecords.date', '>=', $beginDay)
                    ->where('ycrecords.date', '<=', $endDay)
                    ->where('ycrecords.department_id', $departmentOne->id)
                    ->whereNull('yctransactions.record_id')
                    ->count();
                //не пришли
                $countRecCancel = Ycrecord::where('date', '>=', $beginDay)
                    ->where('date', '<=', $endDay)
                    ->where('department_id', $departmentOne->id)
                    ->where('attendance', '=', -1)
                    ->count();
                //новые записи
                $countRecNew = Ycrecord::
                where('created_at', '>=', $beginDay)
                    ->where('created_at', '<=', $endDay)
                    ->where('department_id', $departmentOne->id)
                    ->count();

                //новые клиенты
                $countNewUser = Ycrecord::
                where('date', '>=', $beginDay)
                    ->where('date', '<=', $endDay)
                    ->where('department_id', $departmentOne->id)
                    ->whereIn('user_id', DB::table('ycrecords')
                        ->select(DB::raw('count(user_id)'), 'user_id')
                        ->select('user_id')
                        ->groupBy('user_id')
                        ->havingRaw('count(user_id)=1')
//                        ->where('chain_id', 1)
                        ->pluck('user_id'))
                    ->get()
                    ->count();
                //повторные (2-3 посещения)
                $countReplayUser_2_3 = Ycrecord::
                where('date', '>=', $beginDay)
                    ->where('date', '<=', $endDay)
                    ->where('department_id', $departmentOne->id)
                    ->whereIn('user_id', DB::table('ycrecords')
                        ->select(DB::raw('count(user_id)'), 'user_id')
                        ->select('user_id')
                        ->groupBy('user_id')
                        ->havingRaw('count(user_id)>=2 and count(user_id)<=3')
//                        ->where('chain_id', 1)
                        ->pluck('user_id'))
                    ->get()
                    ->count();
                //лояльные (4-8 посещения)
                $countReplayUser_4_8 = Ycrecord::
                where('date', '>=', $beginDay)
                    ->where('date', '<=', $endDay)
                    ->where('department_id', $departmentOne->id)
                    ->whereIn('user_id', DB::table('ycrecords')
                        ->select(DB::raw('count(user_id)'), 'user_id')
                        ->select('user_id')
                        ->groupBy('user_id')
                        ->havingRaw('count(user_id)>=4 and count(user_id)<=8')
//                        ->where('chain_id', 1)
                        ->pluck('user_id'))
                    ->get()
                    ->count();
                //постоянные (9 и более)
                $countReplayUser_9 = Ycrecord::
                where('date', '>=', $beginDay)
                    ->where('date', '<=', $endDay)
                    ->where('department_id', $departmentOne->id)
                    ->whereIn('user_id', DB::table('ycrecords')
                        ->select(DB::raw('count(user_id)'), 'user_id')
                        ->select('user_id')
                        ->groupBy('user_id')
                        ->havingRaw('count(user_id)>=9')
//                        ->where('chain_id', 1)
                        ->pluck('user_id'))
                    ->get()
                    ->count();
                $resultAirtable =
                    $resultAirtable .
                    '*' . $departmentOne->department_name . '*' . '
        *' . $sumDepartment . '*
        Услуги( ' . $countService . '): ' . $sumService . '
        Косметика (' . $countProduct . '): ' . $sumProduct . '
        ________________________
        Безнал: ' . $PC . '
        Наличные: ' . $nal . '
        ________________________
        Записей на сегодня: ' . $countRecTotal . '
        Состоялось с оплатой: ' . $countRecWithPay . '
        Без оплаты (сертификат): ' . $countRecWithNotPay . '
        Не пришли: ' . $countRecCancelChain . '

        *Новые записи - ' . $countRecNew . '*
        *Новые клиенты - ' . $countNewUser . '*
        Повторные (2-3 посещения): ' . $countReplayUser_2_3 . '
        Лояльные (4-8 посещения): ' . $countReplayUser_4_8 . '
        Постоянные (9 и больше): ' . $countReplayUser_9;


                array_push($result, [
                    'Салон' => $departmentOne->department_name,
                    'Общий' => $sumDepartment,
                    'Расходы' =>  abs($expensService),
                    'Услуги' => '(' . $countService . ')' . $sumService,
                    'Косметика' => '(' . $countProduct . ')' . $sumProduct,
                    'Безнал' => $PC,
                    'Наличными' => $nal,
                    'Записей на сегодня' => $countRecTotal,
                    'Cостоялось с оплатой' => $countRecWithPay,
                    'Без оплаты(сертификат)' => $countRecWithNotPay,
                    'Не пришли' => $countRecCancel,
                    'Новые записи' => $countRecNew,
                    'Новые клиенты' => $countNewUser,
                    'Повторные (2-3 посещения)' => $countReplayUser_2_3,
                    'Лояльные (4-8 посещения)' => $countReplayUser_4_8,
                    'Постоянные (9 и больше)' => $countReplayUser_9
                ]);
            }
            $email = ["9237857776@mail.ru"];
            Mail::to($email)->send(new ReportRecords($result));
            $report = new Report();
            $report->chain_id = $chainOne->id;
            $report->type = 'Отчет по клиентам и записям';
            $report->data = $result;
            $report->save();
//            $data = ["fields" => ["Name" => date("Y-m-d") . '_records_' . $chainOne->name, "Notes" => json_encode($result, JSON_UNESCAPED_UNICODE)]];
//            Curl::to('https://api.airtable.com/v0/appOOds7b02Z6yjg1/report')
//                ->withHeader('Authorization:Bearer keyVWFtF8wCTJ6gjs')
//                ->withData($data)
//                ->post();

        }

        //отдельный отчет по LL

        $department_id=Department::where('chain_id',2)->get();
        $recordsTotalLL=Ycrecord::where('date', '>=', $beginDay)
            ->where('date', '<=', $endDay)
            ->where('chain_id', '=', 2)
            ->get();

        //считаем сколько выполнено записей по LL
        $recordsDoneLL=Ycrecord::where('date', '>=', $beginDay)
            ->where('date', '<=', $endDay)
            ->where('chain_id', '=', 2)
            ->where('attendance', '=', 1)
            ->get();

        $services=DB::table('ycservices')
            ->join('servicecategories','servicecategories.id','=','ycservices.servicecategory_id')
            ->select('service_id','servicecategories.title','ycservices.title as service_title')
            ->get();
        $resultLL=[];

        $countLpgTotal=0;
        $countLaserTotal=0;
        $countKosmetTotal=0;
        $countBandajTotal=0;
        $recordLLTotall=0;
        foreach ($department_id as $departmentOne) {

            $countLaser=0;
            $countLpg=0;
            $countKosmet=0;
            $countBandaj=0;
            $recordLLTotal=0;
            $countServiceLLinRecord=0;
            $countDoneLaser=0;
            $countDoneLpg=0;
            $countDoneBandaj=0;
            $countDoneKosmet=0;
            $countServiceLL=0;
            $receiptTransaction=0;
            $expenseTransaction=0;
            $countProductLL=0;
            $countserviceLaser=0;
            $countserviceLpg=0;
            $countserviceKosmetic=0;
            $countserviceBandaj=0;
            $sumserviceLaser=0;
            $sumserviceLpg=0;
            $sumserviceKosmetic=0;
            $sumserviceBandaj=0;
            $listServicesLaser=[];
            $listServicesLpg=[];
            $listServicesKosmetic=[];
            $listServicesBandaj=[];
            //поступление денег
            $receiptTransaction=Yctransaction::where('date', '>=', $beginDay)
                ->where('date', '<=', $endDay)
                ->where('department_id', '=', $departmentOne->id)
                ->where('amount', '>', 0)
                ->sum('amount');
            //расходы
            $expenseTransaction=Yctransaction::where('date', '>=', $beginDay)
                ->where('date', '<=', $endDay)
                ->where('department_id', '=', $departmentOne->id)
                ->where('amount', '<', 0)
                ->sum('amount');
            //количество транзакция по услугам
            $countServiceLL=    $countProductChain = Yctransaction::where('date', '>=', $beginDay)
                ->where('date', '<=', $endDay)
                ->where('department_id', $departmentOne->id)
                ->whereIn('expense->title', ["Оказание услуг"])
                ->count();
            //сумма транзакций по услугам
            $sumServiceLL=    $countProductChain = Yctransaction::where('date', '>=', $beginDay)
                ->where('date', '<=', $endDay)
                ->where('department_id', $departmentOne->id)
                ->whereIn('expense->title', ["Оказание услуг"])
                ->where('amount', '>', 0)
                ->sum('amount');
            //сумма транзакций по товарам
            $countProductLL=    $countProductChain = Yctransaction::where('date', '>=', $beginDay)
                ->where('date', '<=', $endDay)
                ->where('department_id', $departmentOne->id)
                ->whereIn('expense->title', ["Продажа товаров","Продажа абонементов"])
                ->count();
            //наличные
            $nalLL = Yctransaction::where('date', '>=', $beginDay)
                ->where('date', '<=', $endDay)
                ->where('department_id', $departmentOne->id)
                ->whereIn('account->title', ["Основная касса", "Наличный расчёт" ])
                ->where('amount', '>', 0)
                ->sum('amount');
            //безнал
            $notNalLL = Yctransaction::where('date', '>=', $beginDay)
                ->where('date', '<=', $endDay)
                ->where('department_id', $departmentOne->id)
                ->whereIn('account->title', ["Расчетный счет", "Безналичный расчет"])
                ->where('amount', '>', 0)
                ->sum('amount');

            foreach ($recordsTotalLL as $recordOne) {
                if ($recordOne['services']) {
                    foreach ($recordOne->services as $service){
                        //кол-во услуг по салонам
                        if($recordOne['department_id'] == $departmentOne->id){
                            $countServiceLLinRecord++;
                        }
                    }
                    $recordLLTotal++;
                    foreach ($services as $serviceOne) {
                        //группировка по категорям
                        if (mb_strtolower($recordOne->services[0]['title']) == mb_strtolower($serviceOne->service_title) && $recordOne['department_id'] == $departmentOne->id) {
                            if ($serviceOne->title == 'Лазерная эпиляция') {
                                $countLaser++;
//                             $countLaserTotal++;
//                             количество услуг по салонам группировка по категорям
                                foreach ($recordOne->services as $serviceLaser){
                                    $countserviceLaser++;
                                    $sumserviceLaser+=$serviceLaser['cost'];
                                    $listServicesLaser[]=[
                                        'Услуга'=> $serviceLaser['title'],
                                        'Цена'=>$serviceLaser['cost']
                                    ];
                                }
//                             $laserServices=$recordOne->services;
//                             $laserServicesPrice=$recordOne->services[0]['cost'];
                                if($recordOne->attendance==1){
                                    $countDoneLaser++;
                                }
                            }
                            if ($serviceOne->title == 'LPG') {
                                $countLpg++;
                                $countLpgTotal++;
                                foreach ($recordOne->services as $serviceLpg){
                                    $countserviceLpg++;
                                    $sumserviceLpg+=$serviceLpg['cost'];
                                    $listServicesLpg[]=[
                                        'Услуга'=> $serviceLpg['title'],
                                        'Цена'=>$serviceLpg['cost']
                                    ];
                                }
                                if($recordOne->attendance==1){
                                    $countDoneLpg++;
                                }
                            }
                            if ($serviceOne->title == 'Косметология') {
                                $countKosmet++;
                                $countKosmetTotal++;
                                foreach ($recordOne->services as $serviceKosmet){
                                    $countserviceKosmetic++;
                                    $sumserviceKosmetic+=$serviceKosmet['cost'];
                                    $listServicesKosmetic[]=[
                                        'Услуга'=> $serviceKosmet['title'],
                                        'Цена'=>$serviceKosmet['cost']
                                    ];
                                }
                                if($recordOne->attendance==1){
                                    $countDoneKosmet++;
                                }

                            }
                            if ($serviceOne->title == 'Бандажное обертывание') {
                                $countBandaj++;
                                $countBandajTotal++;
                                foreach ($recordOne->services as $serviceBandaj){
                                    $countserviceBandaj++;
                                    $sumserviceBandaj+=$serviceBandaj['cost'];
                                    $listServicesBandaj[]=[
                                        'Услуга'=> $serviceBandaj['title'],
                                        'Цена'=>$serviceBandaj['cost']
                                    ];
                                }
                                if($recordOne->attendance==1){
                                    $countDoneBandaj++;
                                }

                            }
                        }
                    }
                }
            }

            $resultLL[] = [
                'Дата'=>date("d.m.Y"),
                'Салон'=>$departmentOne->department_name,
                'Сумма'=>$receiptTransaction,
                'Расходы'=>abs($expenseTransaction),
                'Услуги кол-во'=>$countServiceLLinRecord,
                'Услуги сумма'=>$sumServiceLL,
                'Фин операции услуги кол-во'=>$countServiceLL,
                'Абонементы и косметика кол-во'=>$countProductLL,
                'Абонементы и косметика сумма'=>$countProductLL,
                'Безнал'=>$notNalLL,
                'Наличные'=>$nalLL,
                'Лазерная эпиляция' => $countLaser,
                'Лазерная эпиляция пришли'=>$countDoneLaser,
                'Лазерная эпиляция кол-во услуг'=>$countserviceLaser,
                'Лазерная эпиляция сумма'=>$sumserviceLaser,
                '[перечень услуг Лазерная эпиляция]'=>$listServicesLaser,
                'LPG' => $countLpg,
                'LPG пришли' => $countDoneLpg,
                'LPG кол-во услуг'=>$countserviceLpg,
                'LPG сумма'=>$sumserviceLpg,
                '[перечень услуг LPG]'=>$listServicesLpg,
                'Косметология' => $countKosmet,
                'Косметология пришли' => $countDoneKosmet,
                'Косметология кол-во услуг'=>$countserviceKosmetic,
                'Косметология сумма'=>$sumserviceKosmetic,
                '[перечень услуг Косметология]'=>$listServicesKosmetic,
                'Бандажное обертывание' => $countBandaj,
                'Бандажное обертывание пришли' => $countDoneBandaj,
                'Бандажное обертывание кол-во услуг'=>$countserviceBandaj,
                'Бандажное обертывание сумма'=>$sumserviceBandaj,
                '[перечень услуг Бандажное обертывание]'=>$listServicesBandaj,

            ];
        }
        $reportLL=new Report();
        $reportLL->chain_id=2;
        $reportLL->type='Отчет по клиентам и записям LaserLove';
        $reportLL->data=$resultLL;
        $reportLL->save();
//        $dataLL=["fields"=>["Name"=>date("Y-m-d").'_records_NewLaserLove', "Notes"=>json_encode($resultLL,JSON_UNESCAPED_UNICODE)]];
//        Curl::to('https://api.airtable.com/v0/appOOds7b02Z6yjg1/report')
////            ->withHeader('Content-Type:application/json')
//            ->withHeader('Authorization:Bearer keyVWFtF8wCTJ6gjs')
//            ->withData($dataLL)
//            ->post();


    }

    /**
     * Аналитика парсер LL
     *
     * @return JsonResponse
     */
    public function llReport()
    {
       if(Auth::user()->level() < 20) {
           return response()->json([
               'success'=>false,
               'error' => 'Нет прав'],
               403);}

       $groups = Group::all();
       $result=[];
        $it = [];
        $report = collect(Report::where('type', 7)->latest()->first()->data);
        foreach ($groups as $groupOne) {
            foreach ($report as $key=>$reportOne) {
                $resultSalon = [];
                foreach ($reportOne as $profitOne) {
                    foreach ($groupOne->departments as $depOne) {
                        if ($depOne->yc_company_id == $profitOne['id']) {
                            $department = Department::
                            leftJoin('users', 'departments.manager_user_id', '=', 'users.id')
                                ->where('yc_company_id', $profitOne['id'])->select('yc_company_id', 'department_name', 'users.firstname', 'manager_user_id')
                                ->first();
                            $resultSalon[] = [
                                'name' => $department->department_name ?? '',
                                'manager' => $department->firstname ?? '',
                                'value' => $profitOne['value']
                            ];
                        }
//                    }
                    }
                    $it [$key]= collect($resultSalon)->groupBy(function ($item) {
                        return $item['manager'];
                    });
                }
                $result[$groupOne->title]= $it;
            }

        }

        return response()->json([
            'success' => (boolean)$result,
            'data' => $result ?? []
        ]);
    }
}
