<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CustomerTransaction extends Model
{
    protected $table = 'customer_transaction';

    public function customer(){
    	return $this->belongsTo('App\Model\Customer','customer_id','id');
    }

    public function firm(){
    	return $this->belongsTo('App\Model\Firm','firm_id','id');
    }
}
