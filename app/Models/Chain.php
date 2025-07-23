<?php

namespace App\Models;

use App\Traits\HasChains;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Chain extends Model
{
    use HasFactory, HasChains;
    use SoftDeletes;
    protected $table='chains';
    protected $fillable = [
        'id',
        'chain_id',
        'franchise_id',
        'tariff_id',
        's_company_id',
        'name',
        'moysklad_login',
        'moysklad_password',
        'token',
        'system',
        'token_ivideon',
        'city',
        'contactName',
        'requisites',
        'access_token_amo',
        'refresh_token_amo',
        'token_date_amo',
        'tariff_start',
        'tariff_end',
        'palette',
        'type_company',
        'main_name',
        'inn',
        'ogrn',
        'use_mask',
        'type_tax',
        'tick_access',
        'tick_appearance',
        'tick_complect',
        'tick_type_device',
        'tick_status',
        'tick_source',
        'tick_type_repair',
        'token_ms',
        'terminal_enabled'
    ];

    protected $hidden = [
        'moysklad_password',
       ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'token_date_amo',
        'tariff_begin',
        'tariff_end',
    ];
    protected $casts = [
        'palette' => 'array',
        'tick_complect' => 'array',
        'tick_appearance' => 'array',
        'tick_source' => 'array',
        'tick_type_device' => 'array',
        'tick_status' => 'array',
        'tick_type_repair' => 'array',
        'moysklad_requisites' => 'array'
        ];
    protected $appends = ['subscribe_end'];

    public function franchise()
    {
        return $this->belongsTo(Franchise::class);
    }

    public function tariff()
    {
        return $this->belongsTo(Tariff::class);
    }

    public function storeMoySklad()
    {
        return $this->hasMany(StoreMoySklad::class)->whereNull('deleted_at');
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function getSubscribeEndAttribute()
    {
        $time = $this->attributes['tariff_end'] ?? 0;
        if(!$time){
            return '';
        }
        return Carbon::parse($time, 'Asia/Novosibirsk')->setTimezone('UTC')->format('d.m.Y');
    }

    public function isTerminalEnabled()
    {
        return (bool)$this->attributes['terminal_enabled'];
    }


}
