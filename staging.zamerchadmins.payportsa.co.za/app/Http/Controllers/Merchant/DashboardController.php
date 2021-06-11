<?php

namespace App\Http\Controllers\Merchant;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use App\User;
use Illuminate\Support\Facades\DB;
use App\Model\{ProfileLimits,ProfileTransactions,Firm,Ledgers};
use App\Helpers\Helper;
class DashboardController extends Controller
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
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {

        
        

        //var_dump($pubKey);
        //Helper::authABSA();
        //Helper::authenticateABSA();
       
        $pagename = "Dashboard";
        $firmId=auth()->user()->firm_id;
        $transactionLimit=ProfileTransactions::where('firm_id',$firmId)->where('product_type','collection_topup')->orderBy("transmission_date",'desc')->first();
        $days=30;
        $dateUpto=date('Y-m-d');
        $dateUptoTs=strtotime($dateUpto);
        
        $fromDate    = date('Y-m-d',strtotime("-".$days." day",$dateUptoTs));

        $barchartData=$this->_getCollectionTranxNumbers($firmId,$fromDate,$dateUpto);

        $paymentBarchartData=$this->_getPaymentTranxNumbers($firmId,$fromDate,$dateUpto);


        $transactedAmount=DB::table('collections')
                ->selectRaw('sum(amount) as tot_amount')
                ->whereRaw("payment_date>=? and payment_date<=?",[$fromDate,$dateUpto])
                ->where(['collections.firm_id'=>$firmId,'collection_status'=>1])
                ->whereIn('transmission_status',[0,1,2])
                ->first();
        
        $firmDetails = Firm::find($firmId);

        $profileLimits  = ProfileLimits::where(['firm_id' => $firmId ])->first();
        
        $collectionTranx=$this->_getCollectionTranxStats($firmId,$fromDate,$dateUpto);

        $paymentTranx=$this->_getPaymentTranxStats($firmId,$fromDate,$dateUpto);
        
        return view('merchant.dashboard',compact('pagename','transactionLimit','transactedAmount','firmDetails','profileLimits','collectionTranx','paymentTranx','barchartData','paymentBarchartData'));
    }

    public function collectionGraph(Request $request){
        $days           =  $request->dayLimit;
        $dateFrom       =  $request->fromDate; 
        $dateUpto       =  $request->toDate; 
        if($days=="custom"){
            
            $dateUpto=$dateUpto;
            $fromDate = $dateFrom;
        }else{
            if(intval($days)>0){
                $days=$days;
            }else{
                $days=30;
            }
            
            $dateUpto=date('Y-m-d');
            $dateUptoTs=strtotime($dateUpto);
            $fromDate    = date('Y-m-d',strtotime("-".$days." day",$dateUptoTs));
        }
        
        $firmId=auth()->user()->firm_id;
        
        $barchartData=$this->_getCollectionTranxNumbers($firmId,$fromDate,$dateUpto);
        $collectionTranx=$this->_getCollectionTranxStats($firmId,$fromDate,$dateUpto);
        $data=array_merge($collectionTranx,['barchart'=>$barchartData,$fromDate,$dateUpto,$request->dayLimit]);
        echo json_encode($data);

    }

    public function paymentGraph(Request $request){
        $days           =  $request->dayLimit;
        $dateFrom       =  $request->fromDate; 
        $dateUpto       =  $request->toDate; 
        if($days=="custom"){
            
            $dateUpto=$dateUpto;
            $fromDate = $dateFrom;
        }else{
            if(intval($days)>0){
                $days=$days;
            }else{
                $days=30;
            }
            
            $dateUpto=date('Y-m-d');
            $dateUptoTs=strtotime($dateUpto);
            
            $fromDate    = date('Y-m-d',strtotime("-".$days." day",$dateUptoTs));
        }
        
        $firmId=auth()->user()->firm_id;
        
        $barchartData=$this->_getPaymentTranxNumbers($firmId,$fromDate,$dateUpto);
        $collectionTranx=$this->_getPaymentTranxStats($firmId,$fromDate,$dateUpto);
        $data=array_merge($collectionTranx,['barchart'=>$barchartData,$fromDate,$dateUpto,$request->dayLimit]);
        echo json_encode($data);

    }

    function fillNoTrnxDate($barchartData,$fillForDate,$fillUptoDate){

        while ( $fillForDate<= $fillUptoDate) {
                
            $barchartData[]=[
                'day'=>date('d-M',$fillForDate),
                'tranx'=>0
            ];
            $fillForDate    = strtotime("+1 day",$fillForDate);
        }
        return $barchartData;
    }

    private function _getCollectionTranxNumbers($firmId,$fromDate,$dateUpto){
        $tsBegin=strtotime($fromDate);
        $dateUptoTs=strtotime($dateUpto);
        $monthlyTraxs=DB::table('collections')
                ->selectRaw('sum(amount) as tot_amount,count(id) as transmissions,payment_date')
                ->whereRaw("payment_date>=? and payment_date<=?",[$fromDate,$dateUpto])
                ->where(['collections.firm_id'=>$firmId,'collection_status'=>1])
                ->whereIn('transmission_status',[0,1,2])
                ->groupBy('payment_date')
                ->orderBy('payment_date','asc')
                ->get()
                ->toArray();
                
        $barchartData=[];
        
        foreach ($monthlyTraxs as $key => $eachTranx) {
            $tranxDate=strtotime($eachTranx->payment_date);
            $barchartData=$this->fillNoTrnxDate($barchartData,$tsBegin,$tranxDate);
            
             $tsBegin    = strtotime("+1 day",$tranxDate);
             
             if(!is_null(array_key_last($barchartData))){
                $lastKey=array_key_last($barchartData);
                if($barchartData[$lastKey]['day']==date('d-M',$tranxDate)){
                    $barchartData[$lastKey]=[
                        'day'=>date('d-M',$tranxDate),
                        'tranx'=>$eachTranx->transmissions
                    ];
                }else{
                    $barchartData[]=[
                        'day'=>date('d-M',$tranxDate),
                        'tranx'=>$eachTranx->transmissions
                    ];
                }
             }else{
                $barchartData[]=[
                    'day'=>date('d-M',$tranxDate),
                    'tranx'=>$eachTranx->transmissions
                ];
             }
            
            
        }
        
        $barchartData=$this->fillNoTrnxDate($barchartData,$tsBegin,$dateUptoTs);
        return $barchartData;
    }

    private function _getPaymentTranxNumbers($firmId,$fromDate,$dateUpto){
        $tsBegin=strtotime($fromDate);
        $dateUptoTs=strtotime($dateUpto);
        $monthlyTraxs=DB::table('payments')
                ->selectRaw('sum(amount) as tot_amount,count(id) as transmissions,payment_date')
                ->whereRaw("payment_date>=? and payment_date<=?",[$fromDate,$dateUpto])
                ->where(['payments.firm_id'=>$firmId,'payment_status'=>1])
                ->whereIn('transmission_status',[0,1,2])
                ->groupBy('payment_date')
                ->orderBy('payment_date','asc')
                ->get()
                ->toArray();
                
        $barchartData=[];
        
        foreach ($monthlyTraxs as $key => $eachTranx) {
            $tranxDate=strtotime($eachTranx->payment_date);
            $barchartData=$this->fillNoTrnxDate($barchartData,$tsBegin,$tranxDate);
            
             $tsBegin    = strtotime("+1 day",$tranxDate);
             
             if(!is_null(array_key_last($barchartData))){
                $lastKey=array_key_last($barchartData);
                if($barchartData[$lastKey]['day']==date('d-M',$tranxDate)){
                    $barchartData[$lastKey]=[
                        'day'=>date('d-M',$tranxDate),
                        'tranx'=>$eachTranx->transmissions
                    ];
                }else{
                    $barchartData[]=[
                        'day'=>date('d-M',$tranxDate),
                        'tranx'=>$eachTranx->transmissions
                    ];
                }
             }else{
                $barchartData[]=[
                    'day'=>date('d-M',$tranxDate),
                    'tranx'=>$eachTranx->transmissions
                ];
             }
            
            
        }
        
        $barchartData=$this->fillNoTrnxDate($barchartData,$tsBegin,$dateUptoTs);
        return $barchartData;
    }
    private function _getCollectionTranxStats($firmId,$fromDate,$dateUpto){
        
        $collectionTranxRecord = DB::table('collections')
                ->selectRaw('sum(collections.amount) as transaction_amount,count(collections.id) as transaction_count,collections.transaction_status,transaction_error_codes.is_dispute')
                ->leftJoin('transaction_error_codes', function ($join) {
                    $join->on('collections.tranx_error_id', '=', 'transaction_error_codes.id');
                })
                ->whereRaw("collections.payment_date>=? and collections.payment_date<=?",[$fromDate,$dateUpto])
                ->whereIn('collections.transmission_status',[0,1,2])
                ->where(['collections.firm_id'=>$firmId,'collections.collection_status'=>1])
                ->groupBy('transaction_error_codes.is_dispute')
                ->get()
                ->toArray();
                
        $collectionTranxCount=[['label'=>'Successful','value'=>0],['label'=>'Failed','value'=>0],['label'=>'Disputed','value'=>0]];
        
        $collectionTranxAmount=[['label'=>'Successful','value'=>0],['label'=>'Failed','value'=>0],['label'=>'Disputed','value'=>0]];

        $collectionTranxPercent=[['label'=>'Successful','value'=>0],['label'=>'Failed','value'=>0],['label'=>'Disputed','value'=>0]];
        $total=0;
        foreach ($collectionTranxRecord as $key => $eachTranxRecord) {
            $total+=$eachTranxRecord->transaction_count;
        }

        foreach ($collectionTranxRecord as $key => $eachTranxRecord) {
            if($eachTranxRecord->transaction_status==1){
                $collectionTranxCount[0]['value']=$eachTranxRecord->transaction_count;
                $collectionTranxAmount[0]['value']=$eachTranxRecord->transaction_amount;
                $collectionTranxPercent[0]['value']=round(($eachTranxRecord->transaction_count/$total)*100,2);
                
            }elseif($eachTranxRecord->is_dispute==1 && ($eachTranxRecord->transaction_status==2 || $eachTranxRecord->transaction_status==3)){
                $collectionTranxCount[2]['value']=$eachTranxRecord->transaction_count;
                $collectionTranxAmount[2]['value']=$eachTranxRecord->transaction_amount;
                $collectionTranxPercent[2]['value']=round(($eachTranxRecord->transaction_count/$total)*100,2);
            }elseif($eachTranxRecord->is_dispute==0 && $eachTranxRecord->transaction_status==2){
                $collectionTranxCount[1]['value']=$eachTranxRecord->transaction_count;
                $collectionTranxAmount[1]['value']=$eachTranxRecord->transaction_amount;
                $collectionTranxPercent[1]['value']=round(($eachTranxRecord->transaction_count/$total)*100,2);
            }
        }
        return ['count'=>$collectionTranxCount,'percent'=>$collectionTranxPercent,'amount'=>$collectionTranxAmount];
    }

    private function _getPaymentTranxStats($firmId,$fromDate,$dateUpto){
        
        $paymentTranxRecord = DB::table('payments')
                ->selectRaw('sum(payments.amount) as transaction_amount,count(payments.id) as transaction_count,payments.transaction_status,transaction_error_codes.is_dispute')
                ->leftJoin('transaction_error_codes', function ($join) {
                    $join->on('payments.tranx_error_id', '=', 'transaction_error_codes.id');
                })
                ->whereRaw("payments.payment_date>=? and payments.payment_date<=?",[$fromDate,$dateUpto])
                ->whereIn('payments.transmission_status',[0,1,2])
                ->where(['payments.firm_id'=>$firmId,'payments.payment_status'=>1])
                ->groupBy('transaction_error_codes.is_dispute')
                ->get()
                ->toArray();
                
        $paymentTranxCount=[['label'=>'Successful','value'=>0],['label'=>'Failed','value'=>0]];
        $paymentTranxAmount=[['label'=>'Successful','value'=>0],['label'=>'Failed','value'=>0],['label'=>'Disputed','value'=>0]];
        $paymentTranxPercent=[['label'=>'Successful','value'=>0],['label'=>'Failed','value'=>0]];
        $total=0;
        foreach ($paymentTranxRecord as $key => $eachTranxRecord) {
            $total+=$eachTranxRecord->transaction_count;
        }

        foreach ($paymentTranxRecord as $key => $eachTranxRecord) {
            if($eachTranxRecord->transaction_status==1){
                $paymentTranxCount[0]['value']=$eachTranxRecord->transaction_count;
                $paymentTranxAmount[0]['value']=$eachTranxRecord->transaction_amount;
                $paymentTranxPercent[0]['value']=round(($eachTranxRecord->transaction_count/$total)*100,2);
                
            }else{
                $paymentTranxCount[1]['value']=$eachTranxRecord->transaction_count;
                $paymentTranxAmount[1]['value']=$eachTranxRecord->transaction_amount;
                $paymentTranxPercent[1]['value']=round(($eachTranxRecord->transaction_count/$total)*100,2);
            }
        }
        return ['count'=>$paymentTranxCount,'percent'=>$paymentTranxPercent,'amount'=>$paymentTranxAmount];
    }

    public function loginAsAdmin(){
        Auth::logout();
        $admin_id = Session::get('admin_id');
        $user = Auth::loginUsingId($admin_id);
        return redirect('home');
    }


    public function editProfile(Request $request){
        
        $pagename = "Update Profile";
        if($request->isMethod('post')){
                $validator = \Validator::make($request->all(), [
                    "first_name"           => 'required', 
                    "last_name"            => 'required', 
                    "email"                => [
                                                'required',
                                                'email',
                                                Rule::unique('users')->ignore(auth()->user()->id)
                                            ],
                    "contact_number"       => 'required|numeric', 
                ]);
                if ($validator->fails())
                {
                    return Redirect::to('admin/update/administor/'.encrypt($userId))->withErrors($validator)->withInput();
                }

                $userRes = User::find(auth()->user()->id);

                $userRes->first_name           =  $request->first_name;
                $userRes->last_name            =  $request->last_name; 
                $userRes->email                =  $request->email; 
                $userRes->contact_number       =  $request->contact_number;
        
                if($userRes->save()){
                    Session::flash('status','User Updated successfully');
                    Session::flash('class','success');
                }else{
                     Session::flash('status','Unable to Update User! Please try again later');
                     Session::flash('class','danger');
                }
                return redirect()->back();
        }else{
           return view('merchant.profile-edit',compact('pagename'));
        }
       
    }

    public function collectionWallet(){

        $profileLimits = ProfileLimits::where('firm_id',auth()->user()->firm_id)->orderBy('id','desc')->first();
        return view('merchant.collection',compact('profileLimits'));
    }

    public function collectionSummary(Request $request){
        //print_r($request->all());
        $start_date = $request->from_date;
        $end_date   = $request->to_date;
        $graphParamArray = array();
        while (strtotime($start_date) <= strtotime($end_date)) {
            $graphParamArray['date'][]         = $start_date;
            $graphParamArray['first_value'][]  = 2000;
            $graphParamArray['second_value'][] = 8000;
            $start_date = date ("d-m-Y", strtotime("+1 month", strtotime($start_date)));
        }
        return view('merchant.collection-summary',compact('graphParamArray'));
    }
}
