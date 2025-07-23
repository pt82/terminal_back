<?php

namespace App\Services;

use App\Contracts\ApiService;
use Ixudra\Curl\Facades\Curl;


class PosterService implements ApiService
{

    protected $baseUrl = 'https://joinposter.com/api/';
    protected $curl;
    protected $token;
    protected $to = '';
    protected $queryParams = [];

    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    public function to($to)
    {
        $parts = parse_url($to);
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
            $this->queryParams = $this->queryParams + $query;
        }
        $this->to = $this->baseUrl . $parts['path'];
        $this->curl = Curl::to('');
        return $this;
    }

    public function withData($data)
    {
        $this->curl = $this->curl->withData($data);
        return $this;
    }

    public function withQueryParams($params)
    {
        $this->queryParams = $this->queryParams + $params;
        return $this;
    }

    public function asJson()
    {
        $this->curl = $this->curl->asJson();
        return $this;
    }

    public function asJsonRequest()
    {
        $this->curl = $this->curl->asJsonRequest();
        return $this;
    }

    protected function withAllData()
    {
        $this->queryParams['token'] = $this->token;
        $query = '?' . http_build_query($this->queryParams);
        $this->curl = $this->curl->to($this->to . $query);
        return $this->curl;
    }

    public function get()
    {
        return $this->withAllData()->get();
    }

    public function post()
    {
        return $this->withAllData()->post();
    }

    public function delete()
    {
        return $this->withAllData()->delete();
    }

    public function put()
    {
        return $this->withAllData()->put();
    }

    public function chainDepartments(): array
    {
        $departments = $this->to('access.getSpots')->asJson()->get()->response ?? null;
        $result=[];
        if (!empty($departments)){
            foreach ($departments as $departmentsOne){
                $result[]=[
                    'yc_company_id'=>$departmentsOne->spot_id,
                    'department_name'=>$departmentsOne->spot_name,
                    'department_address'=>$departmentsOne->spot_adress,
                    ];
            }
        }
        return [
            'success' => (bool)$result ?? false,
            'data' => $result ?? []
        ];
    }

    public function getCompaniesInfo(): array
    {
        $company = $this->to('settings.getAllSettings')->asJson()->get()->response ?? null;
        return [
            'success' => (bool)$company,
            'data' => (bool)$company ? [
                [
                    's_company_id' => $company->COMPANY_ID,
                    'name' => $company->company_name
                ]
            ] : []
        ];
    }

    public function chainStaff(): array
    {
       $staff = $this->to('access.getEmployees')->asJson()->get()->response ?? null;
        if (!empty($staff)){
            $result=[];
            foreach ($staff as $staffOne){
                list($firstName, $lastName, $fatherland) = array_pad(explode(' ', trim($staffOne->name)), 3, null);
                $result[]=[
                    'yc_staff_id'=>$staffOne->user_id,
                    'firstname'=>$firstName,
                    'lastname'=>$lastName,
                    'fatherland'=>$fatherland,
                    'email' => $staffOne->login
                ];
            }
        }
        return [
            'success' => (bool)$result ?? false,
            'data' => $result ?? []
        ];
    }

}
