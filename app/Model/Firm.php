<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Firm extends Model
{
    protected $table = 'firms'; 

    public function merchant(){
    	return $this->belongsTo('App\User','id','firm_id');
    }

    public function profileTransactions(){
    	return $this->hasMany('App\ProfileTransactions','firm_id');
    }
}
