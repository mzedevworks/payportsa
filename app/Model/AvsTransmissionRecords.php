<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class AvsTransmissionRecords extends Model
{
    protected $table = 'avs_transmission_records';

    public function payments(){
    	return $this->hasMany('App\Model\Payments','transmission_id','id');
    }
}
