<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class PaymentLedgers extends Model
{
    protected $table = 'payment_ledger'; 
    public $timestamps = false;

    public function firm(){
    	return $this->belongsTo('App\Model\Firm','firm_id','id');
    }

    public function paymentBatch(){
    	// return $this->hasManyThrough('App\Model\Payments','App\Model\PaymentBatches','');
    	return $this->belongsTo('App\Model\PaymentBatches','target_reffrence_id','id');
    }

    public function payments(){
    	 return $this->hasManyThrough('App\Model\Payments','App\Model\PaymentBatches','');
    	}
    	
}
