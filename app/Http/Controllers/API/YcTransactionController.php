<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Report;
use App\Models\Terminal;
use App\Models\User;
use App\Models\Ycdb;
use App\Models\Ycitem;
use App\Models\Ycrecord;
use App\Models\Yctransaction;
use App\Services\TerminalRecordService;
use App\Services\YcService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Ixudra\Curl\Facades\Curl;
use React\Dns\Model\Record;
use Illuminate\Support\Facades\Log;
use function RingCentral\Psr7\str;

class YcTransactionController extends Controller
{
    /**
     * Update the specified resource in storage.
     *
     * @return JsonResponse
     */
    public function import()
    {
        //авторизация yC
        $user=(object)['login'=>'paul@strijevski.ru', 'password'=>'2fz2ex'];
//        $user=(object)['login'=>'79501500958', 'password'=>'TIM1986Dolgov'];
        $token= Curl::to('https://api.yclients.com/api/v1/auth')
            ->withHeader('Content-Type:application/json')
            ->withHeader('Accept:application/vnd.yclients.v2+json')
            ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2')
            ->withData($user)
//         ->asJsonRequest()
            ->asJson()
            ->post();
  $companyAll=Department::all();
  foreach($companyAll as $companyOne) {
      $result=[];
    $transactions = Curl::to('https://api.yclients.com/api/v1/transactions/' . $companyOne->yc_company_id . '?&start_date=2015-05-23&end_date=2021-06-01&count=100000')
          ->withHeader('Content-Type:application/json')
          ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2, User ' . $token->data->user_token)
//            ->withData($data)
//            ->asJsonRequest()
//            ->asJson()
          ->get();

      $result = json_decode($transactions, true);
      $data = DB::table('chains')
          ->Join('departments', 'departments.chain_id', '=', 'chains.id')
          ->select('chains.id as chain_id', 'departments.id as department_id')
          ->where('departments.yc_company_id', $companyOne->yc_company_id)
          ->first();
      foreach ($result as $transactionOne) {
//          if ($transactionOne['record_id']) {
          $recordId = Ycrecord::where('record_id', $transactionOne['record_id'])->first();
          if ($recordId) {
              $transactionOne['record_id'] = $recordId->id;
          } elseif (!$recordId) {
              unset($transactionOne['record_id']);
          }

          if ($transactionOne['client']) {
              $userId = User::where('phone', preg_replace('/[^0-9]/', '', $transactionOne['client']['phone']))->first();
              if ($userId) $transactionOne['user_id'] = $userId->id;
          }
          $transactionOne['transaction_id'] = $transactionOne['id'];
          unset($transactionOne['id']);
          $transactionOne['department_id'] = $data->department_id;
          $transactionOne['chain_id'] = $data->chain_id;
          Yctransaction::create($transactionOne);
//      }
      }

  }
        return response()->json([
            "success" => true,

//            "data" =>
        ]);
    }

    public function createTerminalTransaction(Request $request, YcService $yc, TerminalRecordService $terminalRecordService)
    {
        try {
            $company = Department::where('department_id', $request->user()->department_id ?? 'no-id')->first();
            if (!$company)
                abort(404, 'No company (department) found.');

    //        $accounts = collect(
    //            ((array)$yc->to('accounts/' . $company->yc_company_id)->asJson()->get())['data']
    //        );
            $terminal = Terminal::where('department_id', $company->id)->first();
            $accounts = collect([
                (object)[
                    'id' => $terminal->yc_account_cash,
                    'title' => 'Основная касса',
                    'fast_payment'=>1,
                ],
                (object)[
                    'id' => $terminal->yc_account_card,
                    'title' => 'Расчетный счет',
                    'fast_payment'=>2,
                    ]
            ]);

    //        $result = $yc->to('finance_transactions/' . $company->yc_company_id)
    //            ->withData([
    //                "expense_id" => 5,
    //                "amount" => $request->amount ?? 0,
    //                "account_id" => $accounts->firstWhere('title',$request->account ?? '')->id ?? 0,
    //                "client_id" => $request->client_id ?? (Ycitem::where('department_id', $request->user()->department_id ?? 'no-id')
    //                        ->where('person_id', $request->person_id ?? 'no-id')->first()->yc_id ?? 0),
    //                "date" => date('Y-m-d H:i:s'),
    //                "comment" => "Терминал оплаты",
    //            ])
    //            ->asJson()->post();

            $services = [];
            foreach ((array)($request->services ?? []) as $sId => $service) {
                $oneService['id'] = $sId;
                $oneService['record_id'] = $request->record_id ?? 0;
                $oneService['title'] = $service['title'];
                $oneService['cost'] = $service['price'];
                $oneService['cost_per_unit'] = $service['cost_per_unit'] ?? $service['price'];
                $oneService['first_cost'] = $service['first_cost'] ?? $service['price'];
                $oneService['discount'] = $service['discount'];
                $oneService['amount'] = $service['count'] ?? 1;
                $services[] = $oneService;
            }
    //        info([$services, $request->all()]);return response()->json([1], 200);exit;
            $visitUpdate = $yc
                ->to("visits/" . ($request->visit_id ?? 0) . "/" . ($request->record_id ?? 0))
                ->withData([
                    "comment" => 'Привязка транзакции',
                    "attendance" => 1,
    //                "new_transactions" => [
    //                    "id" => $result->data->id ?? 0,
    //                    "amount" => $request->amount ?? 0,
    //                    "account_id" => $accounts->firstWhere('title',$request->account ?? '')->id ?? 0
    //                ],
                    "services" => $services,
                    "fast_payment" => $accounts->firstWhere('title',$request->account ?? '')->fast_payment ?? 0
                ])
                ->asJson()
                ->put();
            Log::channel('terminal_yc_transaction')->info(print_r($visitUpdate, true));
            if($visitUpdate->success==true){
                Ycrecord::where('record_id',$request->record_id ?? 'no-id')->update(['attendance'=>1,'visit_attendance'=>1]);
            }

    //        if ($result->data->id ?? 0)
    //            $terminalRecordService->transactionCommitted($request->record_id ?? 0);
            return response()->json([(array)$visitUpdate], 200);
        } catch (\Exception | \Throwable $e) {
            Log::channel('terminal_yc_transaction')->error($e);
        }
        return response()->json([['success' => '']], 200);
    }

    /**
     * Продажа товара с привязкой к записи
     *
     * @return JsonResponse
     */
    public function bindGoodRecordTransaction(Request $request, YcService $yc, TerminalRecordService $terminalRecordService)
    {
        $company = Department::where('department_id', $request->user()->department_id ?? 'no-id')->first();
        if (!$company)
            abort(404, 'No company (department) found.');

        $terminal = Terminal::where('department_id', $company->id)->first();
        $accounts = collect([
            (object)[
                'id' => $terminal->yc_account_cash,
                'title' => 'Основная касса',
                'fast_payment'=>1,
            ],
            (object)[
                'id' => $terminal->yc_account_card,
                'title' => 'Расчетный счет',
                'fast_payment'=>2,
            ]
        ]);

        $services=[];
        $record=Ycrecord::where('record_id',$request->record_id)->first();

        foreach ((array)($record->services ?? []) as $service) {
            $services[]=[
                'id'=> $service['id'],
                'record_id' => $request->record_id ?? 0,
                'title' => $service['title'],
                'cost' => $service['cost'],
                'cost_per_unit'=>$service['cost_per_unit'],
                'discount' =>$service['discount'],
                'first_cost' =>$service['first_cost'],
                'amount' =>$service['amount'],
            ];
        }
       $document = $yc->to('storage_operations/documents/' . $company->yc_company_id)
            ->withData([
                "type_id" => 1,
//                "storage_id" => $request->storage_id,                       //Идентификатор склада, пока не указываем, продажа осуществляется со склада по умолчанию
                "create_date" => date('Y-m-d H:i:s')
            ])
            ->asJson()->post();

        if ($document->success == true) {
            $goodstransactions = [];
            foreach ($request->goods as $goodOne) {
                $goodOne = (object)$goodOne;
                $dataGoodTransactions = [];
                $dataGoodTransactions = [
                    "document_id" => $document->data->id,                   //Идентификатор документа
                    "good_id" => $goodOne->good_id,                         //Идентификатор товара
                    "amount" => $goodOne->amount,                           //кол-во
                    "cost_per_unit" => $goodOne->cost_per_unit,             //Стоимость за единицу товара
                    "discount" => $goodOne->discount,                                       //скидка
                    "cost" => $goodOne->amount * $goodOne->cost_per_unit,    //Итоговая сумма транзакции
                    "operation_unit_type" => 1,                            //тип единицы измерения: 1 - для продажи, 2 - для списания
                    "client_id" => $request->client_id ?? (Ycitem::where('department_id', $request->user()->department_id ?? 'no-id')
                                ->where('person_id', $request->person_id ?? 'no-id')->first()->yc_id ?? 0),
                    'comment' => 'продажа товара',
                    "master_id" => $record->staff_id,
                ];
                $resultGoodTransactions = $yc->to('storage_operations/goods_transactions/' . $company->yc_company_id)
                    ->withData($dataGoodTransactions)
                    ->asJson()->post();
                $documentId[]=['id'=>$resultGoodTransactions->data->id];
                $goodstransactions[] = [
                    'id' => $resultGoodTransactions->data->id,
                    'comment' => 'Продажа товара, привязка трнзакции',
                    "good_id" => $goodOne->good_id,                         //Идентификатор товара
                    'storage_id' => $resultGoodTransactions->data->storage_id,
                    "amount" => $resultGoodTransactions->data->amount,                                          //кол-во
                    'type' => $resultGoodTransactions->data->type,
                    'master_id' => $record->staff_id,
                    'discount' => $resultGoodTransactions->data->discount,
                    'price' => $resultGoodTransactions->data->cost_per_unit,
                    'cost' => $resultGoodTransactions->data->cost,
                    'operation_unit_type' => $resultGoodTransactions->data->type_id
                ];
            }
        }
        $visitUpdate = $yc
            ->to("visits/" . ($record->documents[0]['visit_id'] ?? 0) . "/" . ($request->record_id ?? 0))
            ->withData([
                "comment" => 'Привязка транзакции',
                "attendance" => 1,
                "services" => $services,
                'goods_transactions' => $goodstransactions,
                "fast_payment" => $accounts->firstWhere('title',$request->account ?? '')->fast_payment ?? 0
            ])
            ->asJsonRequest()
            ->put();
     foreach ($documentId as $documentIdOne) {
         $yc->to("storage_operations/goods_transactions/" . ($company->yc_company_id ?? 0) . "/" . $documentIdOne['id'])
             ->asJsonRequest()
             ->delete();
     }
        return response()->json(['res'=>$documentId,$goodstransactions, $services,$document, (array)$visitUpdate], 200);
        }





    public function GoodRecordTransaction(Request $request, YcService $yc, TerminalRecordService $terminalRecordService)
    {

        $company = Department::where('department_id', $request->user()->department_id ?? 'no-id')->first();
        if (!$company)
            abort(404, 'No company (department) found.');

        $terminal = Terminal::where('department_id', $company->id)->first();

        $accounts = collect([
            (object)[
                'id' => $terminal->yc_account_cash,
                'title' => 'Основная касса',
                'fast_payment' => 1,
            ],
            (object)[
                'id' => $terminal->yc_account_card,
                'title' => 'Расчетный счет',
                'fast_payment' => 2,
            ]
        ]);

        $services = [];
        $record = Ycrecord::where('record_id', $request->record_id)->first();

        foreach ((array)($record->services ?? []) as $service) {
            $services[] = [
                'id' => $service['id'],
                'record_id' => $request->record_id ?? 0,
                'title' => $service['title'],
                'cost' => $service['cost'],
                'cost_per_unit' => $service['cost_per_unit'],
                'discount' => $service['discount'],
                'amount'=>$service['amount'],
                'first_cost' => $service['first_cost']
            ];
        }


        $goodstransactions = [];
        $dataGoodTransactions = [];
            foreach ($request->goods as $goodOne) {
                $goodOne = (object)$goodOne;

        $dataGoodTransactions []= [
            "good_id" => (int)$goodOne->good_id,
            "operation_unit_type" => 1,
            "amount" => (int)$goodOne->amount,                           //кол-во
            "cost_per_unit" => (int)$goodOne->cost_per_unit,             //Стоимость за единицу товара
            "discount" => (int)$goodOne->discount,                                       //скидка
            "cost" => (int)$goodOne->amount * $goodOne->cost_per_unit,    //Итоговая сумма транзакции

            "client_id" => (int)$request->client_id ?? (Ycitem::where('department_id', $request->user()->department_id ?? 'no-id')
                        ->where('person_id', $request->person_id ?? 'no-id')->first()->yc_id ?? 0),
            "comment" => 'продажа товара',
        ];
    }

        $date=[
            'type_id'=>1,
            'comment'=>'Продажа товара, терминал',
            'create_date'=>date('Y-m-d H:i:s'),
            'master_id' => $record->staff_id,
            'client'=>(object)['phone'=>'+'.$request->phone ?? (Ycitem::where('department_id', $request->user()->department_id ?? 'no-id')
                        ->where('person_id', $request->person_id ?? 'no-id')->first()->phone ?? 0)],
            'storage_id'=>$terminal->yc_storage_product,
            'goods_transactions'=>$dataGoodTransactions,
//            'paid'=>(boolean)1,
//            'account_id'=>$accounts->firstWhere('title',$request->account ?? '')->id ?? 0
        ];

        $resultGoodTransactions = $yc->to('storage_operations/operation/' . $company->yc_company_id)
            ->withData(
                $date
            )
            ->asJson()->post();

//        return (array)$resultGoodTransactions->data->goods_transactions[0]->id;
        $goodsRecord=[];
        foreach ($dataGoodTransactions as $GoodTransactionOne){
        $goodsRecord[]=[
            'id'=>(array)$resultGoodTransactions->data->goods_transactions[0]->id,
            'good_id' => (int)$GoodTransactionOne['good_id'],
            'storage_id'=>$terminal->yc_storage_product,
            'amount' => (int)$GoodTransactionOne['amount'],                           //кол-во
            'type'=>1,
            'master_id'=>$record->staff_id,
            'discount'=>(int)$GoodTransactionOne['discount'],
            "cost_per_unit" => (int)$GoodTransactionOne['cost_per_unit'],             //Стоимость за единицу товара
            'price'=>(int)$GoodTransactionOne['cost_per_unit'],
            'cost' => $GoodTransactionOne['cost'],
            'operation_unit_type' => 1
        ];
        }


//                $goodstransactions[] = [
//                    'id' => $resultGoodTransactions->data->id,
//                    'comment' => 'Продажа товара, привязка трнзакции',
//                    "good_id" => $goodOne->good_id,                         //Идентификатор товара
//                    'storage_id' => $resultGoodTransactions->data->storage_id,
//                    "amount" => $resultGoodTransactions->data->amount,                                          //кол-во
//                    'type' => $resultGoodTransactions->data->type,
//                    'master_id' => $record->staff_id,
//                    'discount' => $resultGoodTransactions->data->discount,
//                    'price' => $resultGoodTransactions->data->cost_per_unit,
//                    'cost' => $resultGoodTransactions->data->cost,
//                    'operation_unit_type' => $resultGoodTransactions->data->type_id
//                ];
//            }
//        }
        $visitUpdate = $yc
            ->to("visits/" . ($record->documents[0]['visit_id'] ?? 0) . "/" . ($request->record_id ?? 0))
            ->withData([
                "comment" => 'Привязка транзакции',
                "attendance" => 1,
                "services" => $services,
                'goods_transactions' => $goodsRecord,
                "fast_payment" => $accounts->firstWhere('title',$request->account ?? '')->fast_payment ?? 0
            ])
            ->asJsonRequest()
            ->put();

        return response()->json([$goodstransactions, $services], 200);
    }


    public function newBindGoodRecordTransaction(Request $request, YcService $yc, TerminalRecordService $terminalRecordService)
    {
//        info(print_r($request->all(), true));
      $company = Department::where('department_id', $request->user()->department_id ?? 'no-id')->first();
        if (!$company)
            abort(404, 'No company (department) found.');

        $terminal = Terminal::where('department_id', $company->id)->first();
        $accounts = collect([
            (object)[
                'id' => $terminal->yc_account_cash,
                'title' => 'Основная касса',
                'fast_payment'=>1,
            ],
            (object)[
                'id' => $terminal->yc_account_card,
                'title' => 'Расчетный счет',
                'fast_payment'=>2,
            ]
        ]);

        if(!empty($request->services))
        {
            $services = [];
            foreach ((array)($request->services ?? []) as $sId => $service) {
                $services[] = [
                    'id' => $sId,
                    'record_id' => $request->record_id ?? 0,
                    'title' => $service['title'],
                    'cost' => $service['price'],
                    'cost_per_unit' => $service['cost_per_unit'] ?? $service['price'],
                    'discount' => $service['discount'],
                    'amount'=>$service['count'],
                    'first_cost' => $service['first_cost'] ?? $service['price']
                   ];
            }
        }
        if(!empty($request->goods)) {
            $GoodTransactions = [];
            foreach ($request->goods['goods'] as $goodOne) {
                $goodOne = (object)$goodOne;
                $GoodTransactions [] = [
                    "good_id" => $goodOne->good_id,
                    "storage_id" => (int)$terminal->yc_storage_product,
                    "operation_unit_type" => 1,
                    "amount" => -1 * abs($goodOne->amount),                           //кол-во
                    "cost_per_unit" => $goodOne->cost_per_unit,             //Стоимость за единицу товара
                    "discount" => $goodOne->discount,                                       //скидка
                    "cost" => $goodOne->discount>0 ? $goodOne->amount * $goodOne->cost_per_unit*$goodOne->discount/100 :$goodOne->amount * $goodOne->cost_per_unit,    //Итоговая сумма транзакции
                    "price" => $goodOne->cost_per_unit,    //стоимость товара
                    "master_id"=>$request->staff_id,
                    "client_id" => $request->client_id ?? (Ycitem::where('department_id', $request->user()->department_id ?? 'no-id')
                            ->where('person_id', $request->person_id ?? 'no-id')->first()->yc_id ?? 0),
                    "comment" => 'продажа товара',
                ];
            }
        }

        $visitUpdate = $yc
            ->to("visits/" . ($request->visit_id ?? 0) . "/" . ($request->record_id ?? 0))
            ->withData([
                "comment" => 'Привязка транзакции',
                "attendance" => 1,
                "services" => $services ?? [],
                'goods_transactions' => $GoodTransactions ?? [],
                "fast_payment" => $accounts->firstWhere('title',$request->account ?? '')->fast_payment ?? 0
            ])
            ->asJson()
            ->put();
        if(!empty($request->qrcode)){
            Ycrecord::where('record_id',$request->record_id)->update(['qrcode'=>$request->qrcode]);
          }
        return response()->json([$services ?? [], (array)$visitUpdate], 200);
    }


    public function load(Request $request)
    {
        info((array)$request->all());
        $data=[];
        $data['type']=7;
        $data['title']='Аналитика LL';
        $data['chain_id']=50;
        $data['data']=$request->all();
        Report::create($data);
//        return response()->json([$data, Report::create($data)], 200);
    }

}
