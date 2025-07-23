<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Services\YcService;

class TerminalController extends Controller
{
    public function sendSmsStrizhevsky()
    {
        try {
            require_once base_path('external_libs/sms_ru/sms.ru.php');
            if ($phone = trim(request('phone', ''))) {

                $smsru = new \SMSRU('0BCA4462-6A57-8923-1596-E94E731D2D62');
                $data = new \stdClass();
                $data->to = $phone;
                $data->from = 'strizhevski';
                $data->text = 'Подробно о франшизе Стрижевский https://clck.ru/ZC9C5. Оставьте заявку';
//                $data->test = 1; // Позволяет выполнить запрос в тестовом режиме без реальной отправки сообщения
                $sms = $smsru->send_one($data);

                if ($sms->status == "OK") { // Запрос выполнен успешно
//                    echo "Сообщение отправлено успешно. ";
//                    echo "ID сообщения: $sms->sms_id. ";
//                    echo "Ваш новый баланс: $sms->balance";
                    $error = false;
                } else {
//                    echo "Сообщение не отправлено. ";
//                    echo "Код ошибки: $sms->status_code. ";
//                    echo "Текст ошибки: $sms->status_text.";
                    $error = true;
                }

                return response()->json(['status' => $error ? $sms->status_text : 'ok']);
            }
        } catch (\Throwable $e) {
            report($e);
        }
        return response()->json(['status' => 'error']);
    }

//    public function sendSmsStrizhevsky()
//    {
//        $GLOBALS['smsc_login'] = 'bis20x80';
//        $GLOBALS['smsc_password'] = 'dmAo8Y1fgwBQ';
//        $GLOBALS['smsc_login'] = 'ticketdb';
//        $GLOBALS['smsc_password'] = 'fa95f848b85e8ae6c9c45f0797a68c46';
//        $GLOBALS['smsc_login'] = '20x80fran';
//        $GLOBALS['smsc_password'] = '79836000493';
//        try {
//            require_once base_path('external_libs/smsc_api.php');
//            if ($phone = trim(request('phone', ''))) {
//                $result = send_sms($phone, 'Подробно о франшизе Стрижевский https://clck.ru/ZC9C5. Оставьте заявку');
//                return response()->json(['status' => (count($result) === 2 ? 'error' : 'ok')]);
//            }
//        } catch (\Throwable $e) {}
//        return response()->json(['status' => 'error']);
//    }

    public function listCategories(YcService $yc)
    {
       $company = Department::where('department_id', Auth()->user()->department_id ?? 'no-id')->first();
       return $visitUpdate = $yc
            ->to("labels/" . $company->yc_company_id  . '/1')
            ->asJson()
            ->get();
    }
}

