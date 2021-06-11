<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class TempEmployees extends Model
{
    protected $table = 'tempory_employee'; 

    public function addedBy(){
    	return $this->belongsTo('App\Model\User','added_by','id');
    }
}
