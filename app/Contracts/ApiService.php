<?php


namespace App\Contracts;


interface ApiService
{
    public function to($to);

    public function withData($data);

    public function asJson();

    public function asJsonRequest();

    public function get();

    public function post();

    public function delete();

    public function put();

    public function chainDepartments() : array;

    public function getCompaniesInfo() : array;

    public function chainStaff() : array;

}
