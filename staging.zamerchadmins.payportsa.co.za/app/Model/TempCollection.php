<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class TempCollection extends Model
{
    protected $table = 'tempory_collections'; 

    public function addedBy(){
    	return $this->belongsTo('App\Model\User','added_by','id');
    }
}
