<?php
namespace App\Helpers;

use App\Models\Chain;
use App\Models\User;
use App\Services\PosterService;
use App\Services\YcService;

class ApiServiceHelper
{
    public $service;

    public function __construct()
    {
        app()->singleton(PosterService::class, PosterService::class);
        $this->service = app(PosterService::class);
    }

    public static function api(array $params)
    {
        if (!empty($params['token'])) {
            $api = self::apiSelect('poster');
            $api->setToken($params['token']);
        }
        if (!empty($params['chain'])) {
            if (!is_object($params['chain'])) {
                $fieldChainId = is_numeric($params['chain']) ? 'id' : 'chain';
                $chain = Chain::where($fieldChainId, $params['chain'])->first();
            } else {
                $chain = $params['chain'];
            }
            $api = self::apiSelect(!empty($chain->login) ? 'yc' :'poster');
            $api->setToken($chain->user_token);
        }
        if (!empty($params['user'])) {
            if (is_numeric($params['user'])) {
                $chain = User::find($params['user'])->chains->first();
            }
            if (is_object($params['user'])) {
                $chain = $params['user']->chains->first();
            }
            return self::api(['chain' => $chain ?? null]);
        }
        return $api ?? null;
    }

    protected static function apiSelect($service)
    {
        $services = [
            'yc' => YcService::class,
            'poster' => PosterService::class
        ];
        app()->singleton($services[$service], $services[$service]);
        return app($services[$service]);
    }
}
