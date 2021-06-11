<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UserLoginLog extends Model
{
    protected $table = 'user_login_logs'; 
    //public $timestamps = false;
    public function merchant(){
    	return $this->belongsTo('App\User','id','firm_id');
    }
}
