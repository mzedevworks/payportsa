<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class AvsEnquiry extends Model
{
    protected $table = 'avs_enquiries'; 

    public $timestamps = false;

    public function firm(){
    	return $this->belongsTo('App\Model\Firm','firm_id','id');
    }
}
