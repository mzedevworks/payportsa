<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ProfileTransactions extends Model
{
    protected $table = 'profile_transactions'; 
    public $timestamps = false;
    public function firm(){
    	return $this->belongsTo('App\Firm','firm_id');
    }
}
