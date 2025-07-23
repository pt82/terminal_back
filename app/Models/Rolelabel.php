<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rolelabel extends Model
{
    use HasFactory;

    protected $table = 'rolelabels';
    protected $fillable = [
        'name',
        'created_at',
        'updated_at'
    ];

    public function franchises()
    {
        return $this->belongsToMany(Franchise::class)->withTimestamps()->withPivot(['role_id']);
    }

}
