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

class CollectionWalletController extends Controller
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
    public function profilestats(){
        $payportFirmId=Config('constants.payportFirmId');
        $pagename = "Collection Limit Topups";
        $firms = Firm::where('status',1)->get();
        $payPortInfo=ProfileTransactions::where('firm_id',$payportFirmId)->orderBy('transmission_date','desc')->first();
        $fundLimit=ProfileTransactions::where('firm_id',$payportFirmId)->where('product_type','collection_topup')->groupBy('product_type')->selectRaw('*, sum(amount) as tot_aval')->first();
        
        $statements=ProfileTransactions::where('firm_id',$payportFirmId)
                ->leftJoin('firms', function ($join) {
                    $join->on('firms.id', '=', 'profile_transactions.trans_against_firm_id');
                })->orderBy('transmission_date','desc')->get(['profile_transactions.*', 'firms.business_name']);

        return view('admin.collection-wallet.profilestats',compact('pagename','firms','payPortInfo','statements','fundLimit'));
    }


    public function topups(Request $request){

        $payportFirmId=Config('constants.payportFirmId');
        
        $firms = Firm::where('status',1)->where('id','!=',$payportFirmId)->get();
        $payPortInfo=ProfileTransactions::where('firm_id',$payportFirmId)->orderBy('transmission_date','desc')->first();


        if($request->isMethod('post')){
            $additionalValidation=[
                                    "amount"=>  'required|regex:/[0-9]+/|min:1|max:'.$payPortInfo->closing_balance,
                                ];
            $validator    = $this->validation($request->all(),$additionalValidation);
            
            if ($validator->fails()){
                return redirect()->back()->withErrors($validator)->withInput();;
            }

            try {
                DB::beginTransaction();
                DB::table('profile_transactions')->lockForUpdate()->get();
                DB::table('firms')->whereIn('id', [$payportFirmId,$request->firm_id])->lockForUpdate()->get();
                Helper::writeProfileLimitTrax($payportFirmId,$request->firm_id,$request->amount,$request->remark,$request->transmission_type);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Session::flash('status','Please try again!');
                Session::flash('class','danger');
            }
            return redirect('admin/collection-wallet/profilestats');
        }
        $pagename = "Collection Limit Topups";
        
        return view('admin.collection-wallet.topup',compact('pagename','firms','payPortInfo'));
    }

    private function validation($request,$additionalValidation){
            $entryClassvalues=array_keys(Config('constants.entry_class'));

            $validatorArray = [
                                "firm_id"=> [
                                            'required',
                                            'regex:/[0-9]+/',
                                            Rule::exists('firms','id')->where(function ($query) {
                                                return $query->where('is_deleted', 0)->where('status',1)->where('id','!=',1);
                                            })
                                        ],
                                "transmission_type"     => 'required|in:cr,dr' , 
                                "remark"=> 'required|no_special_char'
                            ];
            $validationArr = array_merge($validatorArray,$additionalValidation);
            $validator     = \Validator::make($request,$validationArr ,[
                "no_special_char"=>"Should not have any special character"
            ]);
            return $validator;
    }
}
