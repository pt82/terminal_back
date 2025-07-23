<?php

namespace App\Models;

use App\Helpers\ApiServiceHelper;
use App\Traits\HasChains;
use App\Traits\HasYcitems;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use jeremykenedy\LaravelRoles\Traits\HasRoleAndPermission;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasChains, Notifiable, HasYcitems;
    use HasRoleAndPermission;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];
    protected $fillable = [
        'id',
        'person_id',
        'person_ivideon_id',
        'face_gallery_id',
        'department_id',
        'franchise_admin_id',
        'yc_staff_id',
        'work_posts_id',
        'role_id',
        'login',
        'password',
        'birth_date',
        'firstname',
        'lastname',
        'fatherland',
        'phone',
        'rating',
        'software',
        'activity',
        'period_haircut',
        'success_data',
        'time_ring',
        'time_remind',
        'access_granted',
        'root_access',
        'terminal_name',
        'avatar',
        'email',
        'name',
        'yc_id',
        'yc_name',
        'comment',
        'sex',
        'description_person',
        'ivideon_done',
        'level',
        'invite_code'
        ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function hasPerms($permission, $all = false)
    {
        if ($this->root_access ?? 0)
            return true;
        return $this->hasPermission($permission, $all);
    }

    public function hasRoles($role, $all = false)
    {
        if ($this->root_access ?? 0)
            return true;
        return $this->hasRole($role, $all);
    }

    public function franchises()
    {
      $chain_id=Chain::whereIn('id',DB::table('chain_user')
         ->select('chain_user.chid')
         ->where('chain_user.user_id',$this->id))
         ->pluck('franchise_id');
      return $this->franchise_admin_id!=NULL ? Franchise::where('id', $this->franchise_admin_id)->get() : Franchise::whereIn('id', $chain_id)->get();
    }

    public function franchise()
    {
      return $this->franchises()->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function lessons()
    {
        return $this->belongsToMany(Lesson::class);
    }

    public function rolePerms()
    {
        if (!($this->attributes['id'] ?? 0))
            return [];
        return Permission
            ::join('permission_role', 'permissions.id', '=', 'permission_role.permission_id')
            ->join('role_user', 'permission_role.role_id', '=', 'role_user.role_id')
            ->where('role_user.user_id', $this->attributes['id'])
            ->select('permissions.id', 'permissions.name', 'permissions.slug', 'permission_role.role_id', 'permission_role.permission_id', 'role_user.role_id', 'role_user.user_id')
            ->get()
            ->unique('permission_id');
    }

    public function apiService()
    {
        return ApiServiceHelper::api(['user' => $this]);
    }

    public function chains()
    {
        return $this->belongsToMany(Chain::class, 'chain_user', 'user_id', 'chid');
    }

}
