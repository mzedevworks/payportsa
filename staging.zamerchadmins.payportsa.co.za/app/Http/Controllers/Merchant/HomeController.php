<?php

namespace App\Http\Controllers\Merchant;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function passwordReset(Request $request)
    {
        if($request->isMethod('get')){
          return view('merchant.password-reset');  
        }else{
            print_r($request->all());
            die;
        }
    }
}
