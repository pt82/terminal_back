<?php
namespace App\Traits;
use App\Models\Chain;
use App\Models\Franchise;
use App\Models\User;
use Illuminate\Support\Facades\DB;


trait HasChains
{
    /**
     * @return mixed
     */
    public function chain()
    {
        return $this->belongsToMany(Chain::class,'chain_user', 'user_id','chid',);
    }

    public function personHasChains()
    {
        return $this->belongsToMany(User::class,'chain_user', 'chid', 'user_id');
    }

}
