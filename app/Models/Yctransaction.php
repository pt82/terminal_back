<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Yctransaction extends Model
{
    use HasFactory;
    protected $table='yctransactions';

    protected $casts = [
        'expense' => 'array',
        'supplier'=> 'array',
        'master'=> 'array',
        'account'=> 'array',
        'client'=> 'array',
      ];

    protected $dates = [
        'date',
        'last_change_date'
    ];

    protected $fillable = [
        'transaction_id',
        'user_id',
        'department_id',
        'chain_id',
        'record_id',
        'expense',
        'date',
        'amount',
        'comment',
        'master',
        'supplier',
        'account',
        'client',
        'last_change_date',
        'visit_id',
        'sold_item_id',
        'sold_item_type',
        'document_id'
    ];
}
