<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class PaymentTransmissionRecords extends Model
{
    protected $table = 'payment_transmission_records';

    public function records(){
    	return $this->belongsTo('App\Model\PaymentTransmissionErrors','transmission_record_id','id');
    }

    public function payments(){
    	return $this->hasMany('App\Model\Payments','transmission_id','id');
    }
}
