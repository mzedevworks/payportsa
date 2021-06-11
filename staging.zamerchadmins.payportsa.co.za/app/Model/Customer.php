<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'customers'; 

    public function bank(){
    	return $this->belongsTo('App\Model\BankDetails','bank_id','id');
    }

    public function firm(){
    	return $this->belongsTo('App\Model\Firm','firm_id','id');
    }
}
