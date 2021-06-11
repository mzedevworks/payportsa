<?php

namespace App\Http\Controllers\Admin;

use App\User;
use App\Model\Role;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Hash;
use App\Helpers\Helper;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Helpers\DatatableHelper;
use Illuminate\Support\Facades\DB;
use App\Model\{Firm,BankDetails,CompanyInformation,ProfileLimits,Rates,ProfileTransactions};

class SettingController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function setProducts(){
        
        $pagename = "Rates Table And Profile Limits";
        $firms = Firm::where('status',1)->get();
        return view('admin.settings.set-products',compact('pagename','firms'));
    }

    public function getMerchantProcucts(Request $request){

            $firmId = $request->id;
            $user = User::where('firm_id',$firmId)->get();
            if(isset($firmId)){
              $rates          = Rates::where(['firm_id' => $firmId,"status" => "active"])->first();
              $profileLimits  = ProfileLimits::where('firm_id',$firmId)->first();
              $firm = Firm::find($firmId);
              $past_rates = Rates::where('firm_id',$firmId)->where('status','<>',1)->get();
            }
           return view('admin.settings.update-rates',compact('rates','profileLimits','firm','past_rates'));
    }

    public function profileLimit(Request $request){
        $pagename = "TopUp Payport Collection Limit";
        $payportFirmId=Config('constants.payportFirmId');
        $payPortInfo=ProfileTransactions::where('firm_id',$payportFirmId)->orderBy('transmission_date','desc')->first();
        $availableBalance=0;
        if($payPortInfo){
            $availableBalance=$payPortInfo->closing_balance;
        }
        $fundLimit=ProfileTransactions::where('firm_id',$payportFirmId)->where('product_type','collection_topup')->groupBy('product_type')->selectRaw('*, sum(amount) as tot_aval')->first();
        if($request->isMethod('post')){
            $additionalValidation=[
                                    "amount"=>  'required|regex:/[0-9]+/|min:1'
                                ];
            $validator    = $this->validation($request->all(),$additionalValidation);
            
            if ($validator->fails()){
                return redirect()->back()->withErrors($validator)->withInput();;
            }
            try {
                DB::beginTransaction();
                DB::table('profile_transactions')->lockForUpdate()->get();
                $payPortInfo=ProfileTransactions::where('firm_id',$payportFirmId)->orderBy('transmission_date','desc')->first();
                $payportTrxRec=new ProfileTransactions();
                $payportTrxRec->firm_id=$payportFirmId;
                $payportTrxRec->transmission_type='cr';
                $payportTrxRec->remark=$request->remark;
                $payportTrxRec->product_type='collection_topup';
                $payportTrxRec->transmission_date=date('Y-m-d H:i:s');
                if($payPortInfo){
                    $payportTrxRec->closing_balance=$payPortInfo->closing_balance+$request->amount;
                }else{
                    $payportTrxRec->closing_balance=$request->amount;
                }
                $payportTrxRec->amount=$request->amount;
                $payportTrxRec->save();
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Session::flash('status','Please try again!');
                Session::flash('class','danger');
            }
            return redirect('admin/collection-wallet/profilestats');
        }
        
        return view('admin.settings.profile-limit',compact('pagename','payPortInfo','fundLimit','availableBalance'));
    }

    private function validation($request,$additionalValidation){
            $entryClassvalues=array_keys(Config('constants.entry_class'));

            $validatorArray = [
                                "remark"=> 'required|no_special_char'
                            ];
            $validationArr = array_merge($validatorArray,$additionalValidation);
            $validator     = \Validator::make($request,$validationArr ,[
                "no_special_char"=>"Should not have any special character"
            ]);
            return $validator;
    }
}
