<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ChangeTracker extends Model
{
    protected $table = 'change_tracker';
    public $timestamps = false;
    
    public function collection(){
    	return $this->belongsTo('App\Model\Collections','target_id','id');
    }

    public function payment(){
    	return $this->belongsTo('App\Model\Payments','target_id','id');
    }
}
