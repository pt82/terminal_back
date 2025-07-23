<?php


namespace App\Services;


use App\Models\Chain;
use App\Models\Department;
use App\Models\Report;
use App\Models\Ycrecord;
use App\Models\Yctransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Ixudra\Curl\Facades\Curl;

class ReportRecordsLLService
{
    //отдельный отчет по LL
    public function __invoke()
    {
        if (App::environment(['production'])) {
            exit;
        }
        $chain = Chain::all();
        $beginDay = Carbon::now()->setTimezone('Africa/Accra')->format('Y-m-d 00:00:00');
        $endDay = Carbon::now()->setTimezone('Africa/Accra')->format('Y-m-d 23:59:00');
        $department_id = Department::where('chain_id', 2)->get();
        $recordsTotalLL = Ycrecord::where('date', '>=', $beginDay)
            ->where('date', '<=', $endDay)
            ->where('chain_id', '=', 2)
            ->get();

        //считаем сколько выполнено записей по LL
        $recordsDoneLL = Ycrecord::where('date', '>=', $beginDay)
            ->where('date', '<=', $endDay)
            ->where('chain_id', '=', 2)
            ->where('attendance', '=', 1)
            ->get();

        $services = DB::table('ycservices')
            ->join('servicecategories', 'servicecategories.id', '=', 'ycservices.servicecategory_id')
            ->select('service_id', 'servicecategories.title', 'ycservices.title as service_title')
            ->get();
        $resultLL = [];

        $countLpgTotal = 0;
        $countLaserTotal = 0;
        $countKosmetTotal = 0;
        $countBandajTotal = 0;
        $recordLLTotall = 0;
        foreach ($department_id as $departmentOne) {

            $countLaser = 0;
            $countLpg = 0;
            $countKosmet = 0;
            $countBandaj = 0;
            $recordLLTotal = 0;
            $countServiceLLinRecord = 0;
            $countDoneLaser = 0;
            $countDoneLpg = 0;
            $countDoneBandaj = 0;
            $countDoneKosmet = 0;
            $countServiceLL = 0;
            $receiptTransaction = 0;
            $expenseTransaction = 0;
            $countProductLL = 0;
            $countserviceLaser = 0;
            $countserviceLpg = 0;
            $countserviceKosmetic = 0;
            $countserviceBandaj = 0;
            $sumserviceLaser = 0;
            $sumserviceLpg = 0;
            $sumserviceKosmetic = 0;
            $sumserviceBandaj = 0;
            $listServicesLaser = [];
            $listServicesLpg = [];
            $listServicesKosmetic = [];
            $listServicesBandaj = [];
            //поступление денег
            $receiptTransaction = Yctransaction::where('date', '>=', $beginDay)
                ->where('date', '<=', $endDay)
                ->where('department_id', '=', $departmentOne->id)
                ->where('amount', '>', 0)
                ->sum('amount');
            //расходы
            $expenseTransaction = Yctransaction::where('date', '>=', $beginDay)
                ->where('date', '<=', $endDay)
                ->where('department_id', '=', $departmentOne->id)
                ->where('amount', '<', 0)
                ->sum('amount');
            //количество транзакция по услугам
            $countServiceLL = $countProductChain = Yctransaction::where('date', '>=', $beginDay)
                ->where('date', '<=', $endDay)
                ->where('department_id', $departmentOne->id)
                ->whereIn('expense->title', ["Оказание услуг"])
                ->count();
            //сумма транзакций по услугам
            $sumServiceLL = $countProductChain = Yctransaction::where('date', '>=', $beginDay)
                ->where('date', '<=', $endDay)
                ->where('department_id', $departmentOne->id)
                ->whereIn('expense->title', ["Оказание услуг"])
                ->where('amount', '>', 0)
                ->sum('amount');
            //сумма транзакций по товарам
            $countProductLL = $countProductChain = Yctransaction::where('date', '>=', $beginDay)
                ->where('date', '<=', $endDay)
                ->where('department_id', $departmentOne->id)
                ->whereIn('expense->title', ["Продажа товаров", "Продажа абонементов"])
                ->count();
            //наличные
            $nalLL = Yctransaction::where('date', '>=', $beginDay)
                ->where('date', '<=', $endDay)
                ->where('department_id', $departmentOne->id)
                ->whereIn('account->title', ["Основная касса", "Наличный расчёт"])
                ->where('amount', '>', 0)
                ->sum('amount');
            //безнал
            $notNalLL = Yctransaction::where('date', '>=', $beginDay)
                ->where('date', '<=', $endDay)
                ->where('department_id', $departmentOne->id)
                ->whereIn('account->title', ["Расчетный счет","Безналичный расчет"])
                ->where('amount', '>', 0)
                ->sum('amount');

            foreach ($recordsTotalLL as $recordOne) {
                if ($recordOne['services']) {
                    foreach ($recordOne->services as $service) {
                        //кол-во услуг по салонам
                        if ($recordOne['department_id'] == $departmentOne->id) {
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
                                foreach ($recordOne->services as $serviceLaser) {
                                    $countserviceLaser++;
                                    $sumserviceLaser += $serviceLaser['cost'];
                                    $listServicesLaser[] = [
                                        'Услуга' => $serviceLaser['title'],
                                        'Цена' => $serviceLaser['cost']
                                    ];
                                }
//                             $laserServices=$recordOne->services;
//                             $laserServicesPrice=$recordOne->services[0]['cost'];
                                if ($recordOne->attendance == 1) {
                                    $countDoneLaser++;
                                }
                            }
                            if ($serviceOne->title == 'LPG') {
                                $countLpg++;
                                $countLpgTotal++;
                                foreach ($recordOne->services as $serviceLpg) {
                                    $countserviceLpg++;
                                    $sumserviceLpg += $serviceLpg['cost'];
                                    $listServicesLpg[] = [
                                        'Услуга' => $serviceLpg['title'],
                                        'Цена' => $serviceLpg['cost']
                                    ];
                                }
                                if ($recordOne->attendance == 1) {
                                    $countDoneLpg++;
                                }
                            }
                            if ($serviceOne->title == 'Косметология') {
                                $countKosmet++;
                                $countKosmetTotal++;
                                foreach ($recordOne->services as $serviceKosmet) {
                                    $countserviceKosmetic++;
                                    $sumserviceKosmetic += $serviceKosmet['cost'];
                                    $listServicesKosmetic[] = [
                                        'Услуга' => $serviceKosmet['title'],
                                        'Цена' => $serviceKosmet['cost']
                                    ];
                                }
                                if ($recordOne->attendance == 1) {
                                    $countDoneKosmet++;
                                }

                            }
                            if ($serviceOne->title == 'Бандажное обертывание') {
                                $countBandaj++;
                                $countBandajTotal++;
                                foreach ($recordOne->services as $serviceBandaj) {
                                    $countserviceBandaj++;
                                    $sumserviceBandaj += $serviceBandaj['cost'];
                                    $listServicesBandaj[] = [
                                        'Услуга' => $serviceBandaj['title'],
                                        'Цена' => $serviceBandaj['cost']
                                    ];
                                }
                                if ($recordOne->attendance == 1) {
                                    $countDoneBandaj++;
                                }

                            }
                        }
                    }
                }
            }

            $resultLL[] = [
                'Дата' => date("d.m.Y"),
                'Салон' => $departmentOne->department_name,
                'Сумма' => $receiptTransaction,
                'Расходы' => abs($expenseTransaction),
                'Услуги кол-во' => $countServiceLLinRecord,
                'Услуги сумма' => $sumServiceLL,
                'Фин операции услуги кол-во' => $countServiceLL,
                'Абонементы и косметика кол-во' => $countProductLL,
                'Абонементы и косметика сумма' => $countProductLL,
                'Безнал' => $notNalLL,
                'Наличные' => $nalLL,
                'Лазерная эпиляция' => $countLaser,
                'Лазерная эпиляция пришли' => $countDoneLaser,
                'Лазерная эпиляция кол-во услуг' => $countserviceLaser,
                'Лазерная эпиляция сумма' => $sumserviceLaser,
                '[перечень услуг Лазерная эпиляция]' => $listServicesLaser,
                'LPG' => $countLpg,
                'LPG пришли' => $countDoneLpg,
                'LPG кол-во услуг' => $countserviceLpg,
                'LPG сумма' => $sumserviceLpg,
                '[перечень услуг LPG]' => $listServicesLpg,
                'Косметология' => $countKosmet,
                'Косметология пришли' => $countDoneKosmet,
                'Косметология кол-во услуг' => $countserviceKosmetic,
                'Косметология сумма' => $sumserviceKosmetic,
                '[перечень услуг Косметология]' => $listServicesKosmetic,
                'Бандажное обертывание' => $countBandaj,
                'Бандажное обертывание пришли' => $countDoneBandaj,
                'Бандажное обертывание кол-во услуг' => $countserviceBandaj,
                'Бандажное обертывание сумма' => $sumserviceBandaj,
                '[перечень услуг Бандажное обертывание]' => $listServicesBandaj,

            ];
        }
        $reportLL = new Report();
        $reportLL->chain_id = 2;
        $reportLL->type = 'Отчет по клиентам и записям LaserLove';
        $reportLL->data = $resultLL;
        $reportLL->save();
        $dataLL = ["fields" => ["Name" => date("Y-m-d") . '_records_NewLaserLove', "Notes" => json_encode($resultLL, JSON_UNESCAPED_UNICODE)]];
        Curl::to('https://api.airtable.com/v0/appOOds7b02Z6yjg1/report')
//            ->withHeader('Content-Type:application/json')
            ->withHeader('Authorization:Bearer keyVWFtF8wCTJ6gjs')
            ->withData($dataLL)
            ->post();
    }
}
