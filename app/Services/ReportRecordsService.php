<?php


namespace App\Services;


use App\Mail\ReportRecords;
use App\Models\Chain;
use App\Models\Department;
use App\Models\Report;
use App\Models\Ycrecord;
use App\Models\Yctransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Ixudra\Curl\Facades\Curl;

class ReportRecordsService
{
    public function __invoke()
    {
        if (App::environment(['production'])) {
            exit;
        }
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
                ->whereIn('account->title', ["Расчетный счет", "Безналичный расчет"])
                ->where('amount','>',0)
                ->sum('amount');
            //наличные
            $nalChain = Yctransaction::where('date', '>=', $beginDay)
                ->where('date', '<=', $endDay)
                ->where('chain_id', $chainOne->id)
                ->whereIn('account->title', ["Основная касса", "Наличный расчёт"])
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

            //по салонам
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
                    ->whereIn('account->title', ["Расчетный счет","Безналичный расчет"])
                    ->where('amount','>',0)
                    ->sum('amount');
                //наличные
                $nal = Yctransaction::where('date', '>=', $beginDay)
                    ->where('date', '<=', $endDay)
                    ->where('department_id', $departmentOne->id)
                    ->whereIn('account->title', ["Основная касса","Наличный расчёт"])
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
            $data = ["fields" => ["Name" => date("Y-m-d") . '_records_' . $chainOne->name, "Notes" => json_encode($result, JSON_UNESCAPED_UNICODE)]];
            Curl::to('https://api.airtable.com/v0/appOOds7b02Z6yjg1/report')
                ->withHeader('Authorization:Bearer keyVWFtF8wCTJ6gjs')
                ->withData($data)
                ->post();

        }


    }
}
