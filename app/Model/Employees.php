<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Employees extends Model
{
    protected $table = 'employees'; 

    public function bank(){
    	return $this->belongsTo('App\Model\BankDetails','bank_id','id');
    }

    public function firm(){
    	return $this->belongsTo('App\Model\Firm','firm_id','id');
    }
}
