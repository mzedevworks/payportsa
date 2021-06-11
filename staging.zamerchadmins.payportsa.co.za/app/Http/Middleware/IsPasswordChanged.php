<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
class IsPasswordChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
       // echo auth()->user()->is_passsword_changed;


        

        if(auth()->user()->is_passsword_changed===1){
            //dd('ddd');
            return  $next($request);
        }else{
            //dd('rrr');
            Session::flash('status', 'Please change your password!');
            Session::flash('class', 'danger');
            return redirect('change/password');
        }
         
        
    }
}
