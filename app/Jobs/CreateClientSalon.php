<?php

namespace App\Jobs;

use App\Models\Chain;
use App\Models\Department;
use App\Models\User;
use App\Models\Ycitem;
use App\Services\YcService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class CreateClientSalon implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Добавляем клиента в салон YC если он есть в БИС, но нет в конкретном салоне
     *
     * @return void
     */
    public function handle(YcService $yc)
    {
        $countUser=Ycitem::where('phone', 'like', '%' . $this->data['phone'] . '%')
            ->where('department_id', $this->data['department_id'])
            ->count();
        $client = User::where('phone', 'like', '%' . $this->data['phone'] . '%')->first();
        $company = Department::where('department_id', $this->data['department_id'])->first();
        $chainId=Chain::where('id', $company->chain_id)->first();
        if($countUser==0) {
            $clientSearch = $yc->to('clients/'.$company->yc_id)
                ->withData([
                    'phone'=>$this->data['phone']
                ])
                ->asJson()->get();
            $clientSearch=  (array)$clientSearch->data[0];
            if(count($clientSearch)>0){
                $data= $clientSearch;
            }
            elseif (count($clientSearch)==0){
                $clientYc = $yc->to('clients/' . $company->yc_company_id)
                    ->withData([
                        'name' => $client->firstname ?? 'NoneName',
                        'phone' =>substr(preg_replace('/[^0-9]/', '', $client->phone), -10)
                    ])
                    ->asJson()
//                ->asJsonRequest()
                    ->post();
                $data = (array)$clientYc->data;
            }
                if ($data) {
                $data['ycitem_id'] = Str::uuid()->toString();
                $data['department_id'] = $company->department_id;
                $data['chain_id'] = $chainId->chain_id;
                $data['yc_id'] = $data['id'];
                unset($data['id']);
                $data['phone'] = preg_replace('/[^0-9]/', '', $data['phone']);
                $client->ycitems()->create($data);
            }
        }
    }
}
