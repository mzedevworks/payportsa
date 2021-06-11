<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class TempCustomers extends Model
{
    protected $table = 'tempory_customers'; 

    public function addedBy(){
    	return $this->belongsTo('App\Model\User','added_by','id');
    }
}
