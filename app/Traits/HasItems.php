<?php
namespace App\Traits;
use App\Models\Item;
use App\Models\Person;
use App\Models\Role;

trait HasItems
{
    /**
     * @return mixed
     */
    public function item()
    {
        return $this->belongsToMany(Item::class,'item_user', 'person_id','item_id',);
    }

    public function persons()
    {
        return $this->belongsToMany(Person::class,'item_user', 'item_id', 'person_id');
    }

}
