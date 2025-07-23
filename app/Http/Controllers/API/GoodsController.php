<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Terminal;
use App\Models\Ycitem;
use App\Services\YcService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
//use Illuminate\Support\Facades\Auth;
use phpDocumentor\Reflection\Types\Null_;

class GoodsController extends Controller
{
       protected $company_id;
    private $user;
    public function __construct(Request $request)
    {

//        $this->company_id=Department::where('department_id',$request->user()->department_id)->first()->yc_company_id ?? 0;

    }


    /**
     * Список товаров из Yc, зависит от авторизованного пользователя
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function ycGoods(Request $request, YcService $yc)
    {
        $company_id=Department::where('department_id',$request->user()->department_id)->first()->yc_company_id ?? 0;
        $result = $yc->to('goods/' . $company_id)
            ->withData([
                "count" => 100,
               ])
            ->asJson()->get();
        return response()->json([
            'success' =>$result->success ?? null,
            'data' =>$result->data ?? null
        ]);
    }

    /**
     * продажа товара (создаем товарную транзакцию  и финансовую транзакцию в YC)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createGoodTransaction(Request $request, YcService $yc)
    {
        $company = Department::where('department_id', $request->user()->department_id ?? 'no-id')->first();
        if (!$company)
            abort(404, 'No company (department) found.');
        $terminal = Terminal::where('department_id', $company->id)->first();
        //создаем документ продажи товара "type_id" => 1
        $document = $yc->to('storage_operations/documents/' . $company->yc_company_id)
            ->withData([
                "type_id" => 1,
                "storage_id" => (int)$terminal->yc_storage_product,                       //Идентификатор склада, пока не указываем, продажа осуществляется со склада по умолчанию
                "create_date" => date('Y-m-d H:i:s')
            ])
            ->asJson()->post();

        if ($document->success == true) {
            foreach ($request->goods as $goodOne) {
                $goodOne = (object)$goodOne;
                $dataGoodTransactions=[];
                $dataGoodTransactions = [
                    "document_id" => $document->data->id,                   //Идентификатор документа
                    "good_id" => $goodOne->good_id,                         //Идентификатор товара
                    "master_id" => $request->master_id ?? 0,                     //Идентификатор мастера
                    "amount" =>$goodOne->amount,                           //кол-во
                    "cost_per_unit" => $goodOne->cost_per_unit,             //Стоимость за единицу товара
                    "discount" => $goodOne->discount ?? 0,                 //скидка
                    "cost" =>$goodOne->amount*$goodOne->cost_per_unit,     //Итоговая сумма транзакции
                    "operation_unit_type" => 1,                            //тип единицы измерения: 1 - для продажи, 2 - для списания
                    "client_id" => $request->client_id ?? (Ycitem::where('department_id', $request->user()->department_id ?? 'no-id')
                                ->where('person_id', $request->person_id ?? 'no-id')->first()->yc_id ?? 0),
                ];
                $resultGoodTransactions = $yc->to('storage_operations/goods_transactions/' . $company->yc_company_id)
                    ->withData($dataGoodTransactions)
                    ->asJson()->post();
            }
            $terminal = Terminal::where('department_id', $company->id)->first();
            $accounts = collect([
                (object)[
                    'id' => $terminal->yc_account_cash,
                    'title' => 'Основная касса',
                ],
                (object)[
                    'id' => $terminal->yc_account_card,
                    'title' => 'Расчетный счет',
                ]
            ]);
            $dataSale = (object)['payment' =>
                          ['method' =>
                             ["slug" => "account", "account_id" => (int)$accounts->firstWhere('title',$request->account ?? '')->id ?? 0],
                         "amount" => (int)$request->totalSale
                ]];
            $resultSale = $yc->to('company/'. $company->yc_company_id.'/sale/'.$document->data->id.'/payment')
                ->withData($dataSale)
                ->asJson()->post();
            }
            return response()->json([
                'storage_document'=>[
                    'success' => $document->success ?? null,
                    'data' => $document->data ?? null
                ],
                 'Sale'=>[
                    'success' => $resultSale->success ?? null,
                    'data' => $resultSale->data ?? null
                ]
            ]);
    }
}
