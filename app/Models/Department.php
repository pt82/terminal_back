<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table='departments';
    protected $fillable = [
        'department_id',
        'manager_user_id',
        'franchise_id',
        'chain_id',
        'yc_company_id',
        'department_name',
        'department_address',
        'timezone_offset',
        'timezone_title',
        'coordinates',
        'city',
        'time_begin',
        'time_end',
        'check_requisites',
        'type_company',
        'main_name',
        'inn',
        'ogrn',
        'type_tax',
        'use_paper_receipt',
        'use_el_receipt',
        'send_receipt',
        'use_category',
        'email',
        'created_at',
        'updated_at',
    ];
    protected $casts = [
        'coordinates' => 'array',
    ];
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function groups()
    {
        return $this->belongsToMany(Group::class);
    }

    public function chain()
    {
        return $this->belongsTo(Chain::class);
    }

    public function requisites () {
        if ($this->check_requisites) {
            $requisites = [
                'type_company' => $this->type_company,
                'main_name' => $this->main_name,
                'inn' => $this->inn,
                'orgn' => $this->ogrn,
                'type_tax' => $this->type_tax,
            ];
        } else {
            $chain = $this->getRelationValue('chain');
            $requisites = [
                'type_company' => $chain->type_company,
                'main_name' => $chain->main_name,
                'inn' => $chain->inn,
                'orgn' => $chain->orgn,
                'type_tax' => $chain->type_tax
            ];
        }
        return $requisites ?? [];
    }
}
