<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Collections extends Model
{
    protected $table = 'collections';
    public function customer(){
    	return $this->belongsTo('App\Model\Customer','customer_id','id');
    }

    public function firm(){
    	return $this->belongsTo('App\Model\Firm','firm_id','id');
    }

    public function batch(){
        return $this->belongsTo('App\Model\Batch','batch_id','id');
    }

    public function transactionRecord(){
    	return $this->belongsTo('App\Model\TransmissionRecords','transmission_id','id');
    }

    public function transactionErrorCode(){
    	return $this->belongsTo('App\Model\TransactionErrorCodes','tranx_error_id','id');
    	 	//description
    }
}
