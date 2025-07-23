<?php

namespace App\Services;

use App\Contracts\ApiService;
use App\Models\Chain;
use App\Models\Department;
use App\Models\Franchise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Ixudra\Curl\Facades\Curl;



class YcService implements ApiService
{

    protected $baseUrl = 'https://api.yclients.com/api/v1/';
    protected $curl;
    protected $user_token;

    public function __construct()
    {
//        if(Auth::user()) {
//            $this->user_token = Franchise::where('id', Auth::user()->franchise_admin_id)->first()->user_token ??
//                Franchise::where('id', Department::where('department_id', Auth::user()->department_id)->first()->franchise_id)->first()->user_token ?? NULL;
//        }
    }

    public function auth($token)
    {
        $this->user_token = $token;
        return $this;
    }

    protected function defaultHeaders()
    {
         $this->curl = $this->curl->withHeader('Content-Type: application/json')
            ->withHeader('Accept:application/vnd.yclients.v2+json')
            ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2, User bfa9b62b0a65591945db1e56539fa226');
//            ->withHeader('Authorization:Bearer 5c8pfgzh7tp6fw8b22d2, User '.$this->user_token);
    }

    public function to($to)
    {
        $this->curl = Curl::to($this->baseUrl . $to);
        $this->defaultHeaders();
        return $this;
    }

    public function withData($data)
    {
        $this->curl = $this->curl->withData($data);
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

    public function get()
    {
        return $this->curl->get();
    }

    public function post()
    {
        return $this->curl->post();
    }

    public function delete()
    {
        return $this->curl->delete();
    }

    public function put()
    {
        return $this->curl->put();
    }

    public function chainDepartments(): array
    {
        // TODO: Implement chainDepartments() method.
    }

    public function getCompaniesInfo(): array
    {
        // TODO: Implement companyInfo() method.
    }

    public function chainStaff(): array
    {
        // TODO: Implement companyInfo() method.
    }




}
