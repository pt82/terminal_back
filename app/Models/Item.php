<?php

namespace App\Models;

use App\Traits\HasItems;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory, HasItems;
    protected $table='items';
    protected $fillable = [
        'item_id',
        'name',
        'city',
        'adress',
        'created_at',
        'updated_at'
    ];

}
