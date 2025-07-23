<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected $myToken = '2424|nkG259jl3VDIRNYnKGldMoUUWs24M0V6QWasM8Ky';

    protected function withMyToken()
    {
        return $this->withToken($this->myToken);
    }
}
