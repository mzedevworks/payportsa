<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class PaymentBatches extends Model
{
    protected $table = 'payment_batches'; 
    public $timestamps = false;

    public function payments(){
    	
    	return $this->hasMany('App\Model\Payments','batch_id','id');
    }

    public function firm(){
    	return $this->belongsTo('App\Model\Firm','firm_id','id');
    }
}
