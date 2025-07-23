<?php

namespace App\Models;

use App\Traits\HasHistories;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ycrecord extends Model
{
    use HasFactory, HasHistories, SoftDeletes;
    protected $table='ycrecords';

    protected $casts = [
        'services' => 'array',
        'goods_transactions'=> 'array',
        'staff'=> 'array',
        'client'=> 'array',
        'record_labels'=> 'array',
        'custom_fields'=> 'array',
        'documents'=> 'array',
    ];

    protected $fillable = [
        'id',
        'user_id',
        'department_id',
        'chain_id',
        'record_id',
        'company_id',
        'staff_id',
        'services',
        'goods_transactions',
        'staff',
        'client',
        'clients_count',
        'date',
        'datetime',
        'create_date',
        'comment',
        'online',
        'visit_attendance',
        'attendance',
        'confirmed',
        'seance_length',
        'length',
        'sms_before',
        'sms_now',
        'sms_now_text',
        'email_now',
        'notified',
        'master_request',
        'api_id',
        'from_url',
        'review_requested',
        'visit_id',
        'created_user_id',
        'deleted',
        'paid_full',
        'prepaid',
        'prepaid_confirmed',
        'last_change_date',
        'custom_color',
        'custom_font_color',
        'record_labels',
        'activity_id',
        'custom_fields',
        'documents',
        'record_from',
        'record_from',
        'bookform_id',
        'is_mobile',
        'rating',
        'typeform_status',
        'record_done',
        'qrcode',
        'is_update_blocked'
       ];
    //при добавление полей, добавить в массив разрешенных полей в YcWebHookController
}
