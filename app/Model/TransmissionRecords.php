<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class TransmissionRecords extends Model
{
    protected $table = 'transmission_records';

    public function records(){
    	return $this->belongsTo('App\Model\transmission_replied_errors','transmission_record_id','id');
    }

    public function collections(){
    	return $this->hasMany('App\Model\Collections','transmission_id','id');
    }
}
