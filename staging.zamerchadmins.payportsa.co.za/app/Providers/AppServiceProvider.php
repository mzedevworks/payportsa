<?php

namespace App\Providers;
use Validator;
use Illuminate\Support\ServiceProvider;
use App\Model\Firm;
use Studio\Totem\Totem;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        // Validator::extend('foo', function($attribute, $value, $parameters, $validator) {
        //     return $value == 'foo';
        // });
        Validator::extend('without_spaces', function($attr, $value){
            return preg_match('/^\S*$/u', $value);
        });
        Validator::extend('no_special_char', function($attr, $value){
            $regex = preg_match('/[@_!#$%^&*()<>?\/\|}{~:]/',$value); 
            if($regex){
                return false;
            }elseif(preg_match('/[[:^print:]]/', $value)){
                return false;
            }

            return true;
            // else{
            //     return !preg_match('/[^x00-x7F]/i', $value);
            //     //return true;
            // }
            
        });


        view()->composer('elements.merchentSidebar', function ($view) 
        {
            $firmId=auth()->user()->firm_id;

            $firmDetails = Firm::find($firmId);

            // //...with this variable
             $view->with('firmDetails', $firmDetails );
        });  

        Totem::auth(function($request) {
            // return true / false . For e.g.
            return \Auth::check();
        }); 
    }
}
