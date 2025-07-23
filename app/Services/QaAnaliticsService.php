<?php


namespace App\Services;


use App\Models\Report;
use App\Models\Ycrecord;
use App\Models\Yctransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QaAnaliticsService
{

    public function __invoke()
    {
//        if (App::environment(['test', 'production'])) {
//            exit;
//        }


        //сети доступные пользователю
        $chainId = [1,49];
//        $chainId = DB::table('chain_user')
//            ->join('chains', 'chains.id', '=', 'chain_user.chid')
//            ->where('user_id', Auth::user()->id)
//            ->pluck('chains.id');

        foreach ($chainId as $chainIdOne) {
            $result = [];
            //id клиентов с одним посещением за все время
            $clientOneRecord = Ycrecord::
            selectRaw('ycrecords.user_id,  count(user_id) as total')
                ->where('chain_id', $chainIdOne)
                ->groupBy('user_id')
                ->having('total', '=', 1)
                ->pluck('user_id');//id клиентов с одним посещением за все время


            $arrayStaff = Ycrecord::
            join('users', 'users.yc_staff_id', '=', 'ycrecords.staff_id')
                ->select('roles.slug', 'staff_id', 'users.id', 'users.firstname as firstname', 'users.lastname as lastname', 'users.fatherland as fatherland', DB::raw('count(ycrecords.id) as countRecord'))
                ->where('ycrecords.chain_id', $chainIdOne)
                ->Join('role_user', 'users.id', '=', 'role_user.user_id')
                ->Join('roles', 'role_user.role_id', '=', 'roles.id')
                ->where('roles.slug', 'master')
                ->whereNull('users.deleted_at')
                ->groupBy('staff_id', 'users.firstname', 'users.lastname', 'users.fatherland', 'roles.slug', 'users.id')
                ->get();

            Report::where('type', '5')->delete();
            foreach ($arrayStaff as $staffId) {
                $resultOneStaff = [];
                //запис клиентов с 1 посещением у мастера за 30дней
                $clientRecord = array_first(Ycrecord::
                select(DB::raw('count(ycrecords.id) as count'))
                    ->where('ycrecords.staff_id', $staffId->staff_id)
                    ->where('date', '>=', Carbon::now()->firstOfMonth()->format('Y-m-d H:i:s'))
                    ->where('date', '<=', Carbon::now()->format('Y-m-d H:i:s'))
                    ->whereIn('ycrecords.user_id', $clientOneRecord)
                    ->pluck('count'));

                //кол-во записей с фото
                $clientStaffWithPhoto = Ycrecord::
                where('ycrecords.staff_id', $staffId->staff_id)
                    ->join('photos', 'photos.ycrecord_id', '=', 'ycrecords.id')
                    ->select(DB::raw('count(ycrecords.id) as count'))
                    ->count(DB::raw('DISTINCT photos.ycrecord_id'));

                //кол-во записей с фото c начала месяца
                $clientStaffWithPhotofirstOfMonth = Ycrecord::
                where('ycrecords.staff_id', $staffId->staff_id)
                    ->join('photos', 'photos.ycrecord_id', '=', 'ycrecords.id')
                    ->where('date', '>=', Carbon::now()->firstOfMonth()->format('Y-m-d H:i:s'))
                    ->where('date', '<=', Carbon::now()->format('Y-m-d H:i:s'))
                    ->select(DB::raw('count(ycrecords.id) as count'))
                    ->count(DB::raw('DISTINCT photos.ycrecord_id'));

                //массив id записи за 30 дней
                $clientStaffRecords = Ycrecord::
                where('ycrecords.staff_id', $staffId->staff_id)
                    ->select('id')
                    ->where('date', '>=', Carbon::now()->subDays(30)->format('Y-m-d H:i:s'))
                    ->where('date', '<=', Carbon::now()->format('Y-m-d H:i:s'))
                    ->pluck('id');

                // массив id записи c начала месяца
                $clientStaffRecordsfirstOfMonth = Ycrecord::
                where('ycrecords.staff_id', $staffId->staff_id)
                    ->select('id')
                    ->where('date', '>=', Carbon::now()->firstOfMonth()->format('Y-m-d H:i:s'))
                    ->where('date', '<=', Carbon::now()->format('Y-m-d H:i:s'))
                    ->count('id');


                //сумма по транзакциям
                $sumTransaction = Yctransaction::
                select(DB::raw('sum(amount) as sum'))
                    ->whereIn('record_id', $clientStaffRecords)
                    ->get();

                //кол-во записей с фото за 30 дней
                $clientStaffOneRecordWithPhoto = Ycrecord::
                where('ycrecords.staff_id', $staffId->staff_id)
                    ->join('photos', 'photos.ycrecord_id', '=', 'ycrecords.id')
                    ->where('date', '>=', Carbon::now()->firstOfMonth()->format('Y-m-d H:i:s'))
                    ->where('date', '<=', Carbon::now()->format('Y-m-d H:i:s'))
                    ->whereIn('ycrecords.user_id', $clientOneRecord)
                    ->count(DB::raw('DISTINCT photos.ycrecord_id'));

                //средняя стрижки оценка за 180 дней
                $avg_rating = Ycrecord::
                where('ycrecords.staff_id', $staffId->staff_id)
                    ->where('date', '>=', Carbon::now()->subDays(180)->format('Y-m-d H:i:s'))
                    ->where('date', '<=', Carbon::now()->format('Y-m-d H:i:s'))
                    ->select(DB::raw('avg(ycrecords.rating) as avg'))
                    ->get();
//                return $avg_rating[0]->avg;

                if ($clientRecord > 0) {
                    $QA_new_client = round($clientStaffOneRecordWithPhoto / $clientRecord * 100, 0);
                } elseif ($clientRecord == 0) {
                    $QA_new_client = 0;
                }
                if ($staffId->countRecord > 0) {
                    $clientAndRecords = round($clientStaffWithPhoto / $staffId->countRecord * 100, 0);
                } elseif ($staffId->countRecord == 0) {
                    $clientAndRecords = 0;
                }
                if ($clientStaffRecords->count() > 0) {
                    $avg_buy = $sumTransaction[0]->sum / $clientStaffRecords->count();
                } elseif ($clientStaffRecords->count() == 0) {
                    $avg_buy = 0;
                }
                if ($clientStaffRecordsfirstOfMonth > 0) {
                    $clientAndRecordsfirstOfMonth = round($clientStaffWithPhotofirstOfMonth / $clientStaffRecordsfirstOfMonth * 100, 0);
                } elseif ($clientStaffRecordsfirstOfMonth == 0) {
                    $clientAndRecordsfirstOfMonth = 0;
                }

                $resultOneStaff = [
                    'staff_name' => ($staffId->lastname ?? '') . ' ' . ($staffId->firstname ?? '') . ' ' . ($staffId->fatherland ?? ''), // Мастер
                    'clientAndRecords' => '(' . $staffId->countRecord . ' / ' . $clientStaffWithPhoto . ')', // Клиенты и стрижки
                    'clientAndRecords_percent' => $clientAndRecords, // % Клиенты и стрижки
                    'QA_new_client' => ' (' . $clientRecord . ' / ' . $clientStaffOneRecordWithPhoto . ')', //контрой качества по новым клиентам
                    'QA_new_client_percent' => $QA_new_client, //контрой качества по новым клиентам
                    'avg_rating' => round($avg_rating[0]->avg, 0), // средний рейтинг
                    'avg_buy' => round($avg_buy, 0), //средний чек
                    'clientAndRecordsfirstOfMonth' => '(' . $clientStaffRecordsfirstOfMonth . ' / ' . $clientStaffWithPhotofirstOfMonth . ')',// Клиенты и фото стрижек с начала месяца
                    'clientAndRecordsfirstOfMonth_percent' => $clientAndRecordsfirstOfMonth, // %Клиенты и фото стрижек с начала месяца
                    'id' => $staffId->id
                ];

                $result[] = [
                    'staff_name' => ($staffId->lastname ?? '') . ' ' . ($staffId->firstname ?? '') . ' ' . ($staffId->fatherland ?? ''), // Мастер

                    'clientAndRecords' => '(' . $staffId->countRecord . ' / ' . $clientStaffWithPhoto . ')', // Клиенты и стрижки
                    'countRecord' => $staffId->countRecord, //Всего записей
                    'countRecordWithPhoto' => $clientStaffWithPhoto, //Всего записей с фото
                    'clientAndRecords_percent' => $clientAndRecords, // % Клиенты и стрижки

                    'QA_new_client' => ' (' . $clientRecord . ' / ' . $clientStaffOneRecordWithPhoto . ')', //контрой качества по новым клиентам
                    'clientRecord' => $clientRecord, //кол-во записей клиентов с 1 посещением у мастера за 30дней
                    'clientRecordWithPhoto' => $clientStaffOneRecordWithPhoto, //кол-во записей клиентов с 1 посещением у мастера за 30дней с фото
                    'QA_new_client_percent' => $QA_new_client, //контроль качества по новым клиентам

                    'avg_rating' => round($avg_rating[0]->avg, 0), // средний рейтинг
                    'avg_buy' => round($avg_buy, 0), //средний чек

                    'clientAndRecordsfirstOfMonth' => '(' . $clientStaffRecordsfirstOfMonth . ' / ' . $clientStaffWithPhotofirstOfMonth . ')',// Клиенты и фото стрижек с начала месяца
                    'clientStaffRecordsfirstOfMonth' => $clientStaffRecordsfirstOfMonth,//  Клиенты и фото стрижек с начала месяца
                    'clientStaffWithPhotofirstOfMonth' => $clientStaffWithPhotofirstOfMonth,// Клиенты и фото стрижек с начала месяца с фото
                    'clientAndRecordsfirstOfMonth_percent' => $clientAndRecordsfirstOfMonth, // %Клиенты и фото стрижек с начала месяца
                    'id' => $staffId->id
                ];
//                if (Carbon::now()->format('Y-m-d') == Carbon::now()->endOfMonth()->format('Y-m-d')) {
//                    Report::create(['user_id' => $staffId->id, 'chain_id' => $chainIdOne, 'type' => 7, 'title' => 'Аналитика по загруженным фото на сотрудника месяц Стрижевский', 'data' => $resultOneStaff]);
//                }
//                Report::create(['user_id' => $staffId->id, 'chain_id' => $chainIdOne, 'type' => 5, 'title' => 'Аналитика по загруженным фото на сотрудника Стрижевский', 'data' => $resultOneStaff]);
            }

            Report::where('type', '4')->where('chain_id',$chainIdOne)->delete();
            if (Carbon::now()->format('Y-m-d') == Carbon::now()->endOfMonth()->format('Y-m-d')) {
                Report::create(['chain_id' => $chainIdOne, 'type' => 6, 'title' => 'Аналитика по загруженным фото месяц Стрижевский', 'data' => $result]);
            }
            Report::create(['chain_id' => $chainIdOne, 'type' => 4, 'title' => 'Аналитика по загруженным фото Стрижевский', 'data' => $result]);
        }
    }
}
