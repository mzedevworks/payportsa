<?php

namespace App\Http\Middleware;
use Auth;
use Closure;
use App\Model\{Ledgers,Firm};
class IsProduct
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next,$productType)
    {
        $firmId=Auth::user()->firm_id;
        $firmDetail = Firm::find($firmId);

        //check if firm is having having reoccur collection as product
        if($productType=='reoccur-collection' &&  $firmDetail->is_reoccur_collection!=1){
            return redirect('home');
        }

        //check if firm is having having normal collection as product
        if($productType=='normal-collection' &&  $firmDetail->is_normal_collection!=1){
            return redirect('home');
        }

        //check if firm is having having normal collection as product
        if($productType=='avs' &&  $firmDetail->is_avs!=1){
            return redirect('home');
        }

        //check if firm is having having salary as product
        if($productType=='salary' &&  $firmDetail->is_salaries!=1){
            return redirect('home');
        }

        if($productType=='creditor' &&  $firmDetail->is_creditors!=1){
            return redirect('home');
        }

        return $next($request);
    }
}
