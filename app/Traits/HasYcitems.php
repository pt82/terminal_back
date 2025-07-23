<?php
namespace App\Traits;
use App\Models\Item;
use App\Models\Person;
use App\Models\Role;
use App\Models\Ycitem;

trait HasYcitems

{
    /**
     * @return mixed
     */


    public function ycitems()
    {
        return $this->hasMany(Ycitem::class,'person_id','person_id');
    }

}
