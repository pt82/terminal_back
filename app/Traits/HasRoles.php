<?php
namespace App\Traits;
use App\Models\Person;
use App\Models\Role;
use App\Models\User;

trait HasRoles
{
    /**
     * @return mixed
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class,'role_user', 'user_id','role_id',);
    }

    public function persons()
    {
        return $this->belongsToMany(Person::class,'role_user', 'role_id', 'user_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class,'role_user','user_id','role_id');
    }

    public function item()
    {
        return $this->belongsToMany(Role::class,'item_user', 'item_id','person_id',);
    }
}
