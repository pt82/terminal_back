<?php

namespace App\Models;

use App\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use jeremykenedy\LaravelRoles\Traits\HasRoleAndPermission;

class Role extends Model
{
    use HasFactory;
    use HasRoleAndPermission;

    protected $table='roles';
    protected $appends = ['bis_name'];
    protected $fillable = [
        'id',
        'name',
        'slug ',
        'description',
        'level',
        'created_at',
        'updated_at'
    ];

    public function rolelabels($franchiseId = 0)
    {
        $query = $this->belongsToMany(Rolelabel::class, 'franchise_rolelabel', 'role_id', 'rolelabel_id');

        if ($franchiseId)
            return $this->wherePivotFranchise($query, $franchiseId);
        return $query;
    }

    public function scopeWithLabels($query, $franchiseId = 0)
    {
        if (!$franchiseId and auth()->id())
            $franchiseId = auth()->user()->franchise()->id ?? 0;

        return $query->with(['rolelabels' => function ($query) use ($franchiseId) {
            $this->wherePivotFranchise($query, $franchiseId);
        }]);
    }

    private function wherePivotFranchise($query, $franchiseId)
    {
        if (is_array($franchiseId))
            return $query->wherePivotIn('franchise_id', $franchiseId);
        return $query->wherePivot('franchise_id', $franchiseId);
    }

    public function getNameAttribute()
    {
        return $this->getRelationValue('rolelabels')->first()->name ?? ($this->attributes['name'] ?? '');
    }

    public function getBisNameAttribute()
    {
        return $this->attributes['name'] ?? '';
    }
}

