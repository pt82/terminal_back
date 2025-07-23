<?php


namespace App\Traits;
use App\Models\History;
use App\Models\Ycrecord;

trait HasHistories
{
    /**
     * @return mixed
     */

    public function histories()
    {
        return $this->hasMany(History::class);
    }
    public function ycrecords()
    {
        return $this->belongsTo(Ycrecord::class);
    }
}
