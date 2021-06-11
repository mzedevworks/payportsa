<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class TempAvs extends Model
{
    protected $table = 'temporary_avs_enquiries'; 

    public function addedBy(){
    	return $this->belongsTo('App\Model\User','added_by','id');
    }
}
