<?php

namespace App\Http\Controllers\Admin;

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
        $pagename = "Dashboard";
        
        $firmId=auth()->user()->firm_id;
        
        $days=30;
        $dateUpto=date('Y-m-d');
        $dateUptoTs=strtotime($dateUpto);
        
        $fromDate    = date('Y-m-d',strtotime("-".$days." day",$dateUptoTs));

        $barchartData=$this->_getCollectionTranxNumbers($fromDate,$dateUpto);

        $paymentBarchartData=$this->_getPaymentTranxNumbers($fromDate,$dateUpto);


        $transactedAmount=DB::table('collections')
                ->selectRaw('sum(amount) as tot_amount')
                ->whereRaw("payment_date>=? and payment_date<=?",[$fromDate,$dateUpto])
                ->where(['collection_status'=>1])
                ->whereIn('transmission_status',[0,1,2])
                ->first();
        
        
        $firmDetails = Firm::find($firmId);

        $profileLimits  = ProfileLimits::where(['firm_id' => $firmId ])->first();
        
        $collectionTranx=$this->_getCollectionTranxStats($firmId,$fromDate,$dateUpto);

        $paymentTranx=$this->_getPaymentTranxStats($firmId,$fromDate,$dateUpto);
                
        
        
        $collectionTranx=$this->_getCollectionTranxStats($fromDate,$dateUpto);

        $paymentTranx=$this->_getPaymentTranxStats($fromDate,$dateUpto);

        return view('admin.dashboard',compact('pagename','transactedAmount','firmDetails','profileLimits','collectionTranx','paymentTranx','barchartData','paymentBarchartData'));
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
        
        $barchartData=$this->_getCollectionTranxNumbers($fromDate,$dateUpto);
        $collectionTranx=$this->_getCollectionTranxStats($fromDate,$dateUpto);
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
        
        $barchartData=$this->_getPaymentTranxNumbers($fromDate,$dateUpto);
        $collectionTranx=$this->_getPaymentTranxStats($fromDate,$dateUpto);
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
    private function _getCollectionTranxNumbers($fromDate,$dateUpto){
        $tsBegin=strtotime($fromDate);
        $dateUptoTs=strtotime($dateUpto);
        $monthlyTraxs=DB::table('collections')
                ->selectRaw('sum(amount) as tot_amount,count(id) as transmissions,payment_date')
                ->whereRaw("payment_date>=? and payment_date<=?",[$fromDate,$dateUpto])
                ->where(['collection_status'=>1])
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

    private function _getPaymentTranxNumbers($fromDate,$dateUpto){
        $tsBegin=strtotime($fromDate);
        $dateUptoTs=strtotime($dateUpto);
        $monthlyTraxs=DB::table('payments')
                ->selectRaw('sum(amount) as tot_amount,count(id) as transmissions,payment_date')
                ->whereRaw("payment_date>=? and payment_date<=?",[$fromDate,$dateUpto])
                ->where(['payment_status'=>1])
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

    private function _getCollectionTranxStats($fromDate,$dateUpto){
        
        $collectionTranxRecord = DB::table('collections')
                ->selectRaw('sum(collections.amount) as transaction_amount,count(collections.id) as transaction_count,collections.transaction_status,transaction_error_codes.is_dispute')
                ->leftJoin('transaction_error_codes', function ($join) {
                    $join->on('collections.tranx_error_id', '=', 'transaction_error_codes.id');
                })
                ->whereRaw("collections.payment_date>=? and collections.payment_date<=?",[$fromDate,$dateUpto])
                ->whereIn('collections.transmission_status',[0,1,2])
                ->where(['collections.collection_status'=>1])
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

    private function _getPaymentTranxStats($fromDate,$dateUpto){
        
        $paymentTranxRecord = DB::table('payments')
                ->selectRaw('sum(payments.amount) as transaction_amount,count(payments.id) as transaction_count,payments.transaction_status,transaction_error_codes.is_dispute')
                ->leftJoin('transaction_error_codes', function ($join) {
                    $join->on('payments.tranx_error_id', '=', 'transaction_error_codes.id');
                })
                ->whereRaw("payments.payment_date>=? and payments.payment_date<=?",[$fromDate,$dateUpto])
                ->whereIn('payments.transmission_status',[0,1,2])
                ->where(['payments.payment_status'=>1])
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

    public function editProfile(Request $request){

         $pagename = "Update Profile";
        if($request->isMethod('get')){
            return view('admin.profile-edit');
        }else{
            $validator = \Validator::make($request->all(), [
                "email"                => 'required|email|unique:users,email,'.auth()->user()->email.',email', 
                "first_name"           => "required",
                "last_name"            => "required"
            ]);
            if ($validator->fails()){
                return redirect()->back()->withErrors($validator)->withInput();;
            }else{
                $user = User::find(auth()->user()->id);
                $user->email = $request->email;
                $user->first_name = $request->first_name;
                $user->last_name = $request->last_name;
                $user->save();
                Session::flash('status','Successfullt updated profile');
                Session::flash('class', 'success');
                return redirect()->back();
            }
        }
    }
}
