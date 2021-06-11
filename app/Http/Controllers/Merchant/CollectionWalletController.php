<?php

namespace App\Http\Controllers\Merchant;

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
        
        $pagename = "Collection Profile";
        $firmId=auth()->user()->firm_id;
        
        $transactionLimit=ProfileTransactions::where('firm_id',$firmId)->where('product_type','collection_topup')->orderBy("transmission_date",'desc')->first();

        $transactedAmount=DB::table('collections')
                ->selectRaw('sum(amount) as tot_amount')
                ->whereRaw("DATE_FORMAT(payment_date, '%Y-%m')=?",[date('Y-m')])
                ->where(['collections.firm_id'=>$firmId,'collection_status'=>1])
                ->whereIn('transmission_status',[0,1,2])
                ->first();
        
        
        $profileLimitAtStart=$this->_getProfileLimitAtMonthStart($firmId);
        $profileLimitChanges=$this->_getProfileLimitChanges($firmId);

        $transFrom=date('Y-m-1');
        $closingAmount=0;
        if(!is_null($profileLimitAtStart)){
            $closingAmount=$profileLimitAtStart->closing_balance;
        }
        

        $ledgerData=[
            [
                'desc'=>'Opening Balance','amount'=>$closingAmount,'closing_balance'=>$closingAmount,'trnx_date'=>$transFrom
            ]
        ];
        
        foreach ($profileLimitChanges as $key => $eachLimitChange) {

            $transUpto=date('Y-m-d',strtotime($eachLimitChange->transmission_date));
            $batchTranxs=$this->_transmissionInDateRange($firmId,$transFrom,$transUpto);

            $processedLedger=$this->_processProfileLimitLedger($ledgerData,$batchTranxs,$closingAmount);
            $ledgerData=$processedLedger['ledger'];
            $closingAmount=$processedLedger['closing'];


            $amount=$eachLimitChange->amount;
            $closingAmount=$closingAmount+($amount);
            $ledgerData[]=[
                'desc'=>'Opening Balance','amount'=>$amount,'closing_balance'=>$closingAmount,'trnx_date'=>$transUpto];
            $transFrom=$transUpto;
        }

        $transUpto=date('Y-m-d',strtotime("+3 day",time()));;
        $batchTranxs=$this->_transmissionInDateRange($firmId,$transFrom,$transUpto);

        $processedLedger=$this->_processProfileLimitLedger($ledgerData,$batchTranxs,$closingAmount);
        $ledgerData=$processedLedger['ledger'];
            


        $profileLimits  = ProfileLimits::where(['firm_id' => $firmId ])->first();

            $firmDetails = Firm::find($firmId);

        return view('merchant.collection-wallet.profilestats',compact('pagename','transactionLimit','transactedAmount','firmDetails','profileLimits','ledgerData'));
    }

    private function _processProfileLimitLedger($ledgerData,$batchTranxs,$closingAmount){
        foreach ($batchTranxs as $key => $eachBatchTranx) {
                $amount=$eachBatchTranx->trnx_amount*(-1);
                $closingAmount=$closingAmount+($amount);
                $ledgerData[]=
                ['desc'=>$eachBatchTranx->batch_name,'amount'=>$amount,'closing_balance'=>$closingAmount,'trnx_date'=>$eachBatchTranx->payment_date];
            }
        return ['ledger'=>$ledgerData,'closing'=>$closingAmount];
    }
    private function _transmissionInDateRange($firmId,$transFrom,$transUpto){
        $collectionTranxRecord = DB::table('collections')
                ->selectRaw('sum(collections.amount) as trnx_amount,collections.payment_date,batches.batch_name')
                ->leftJoin('batches', function ($join) {
                    $join->on('batches.id', '=', 'collections.batch_id');
                })
                ->whereRaw("collections.payment_date>=? and collections.payment_date<?",[$transFrom,$transUpto])
                ->whereIn('collections.transmission_status',[0,1,2]) //pending,success or failed
                ->where(['collections.firm_id'=>$firmId,'collections.collection_status'=>1])
                ->groupBy('collections.batch_id')
                ->get()
                ->toArray();
        return $collectionTranxRecord;
    }
    private function _getProfileLimitAtMonthStart($firmId){
        $profileLimit=ProfileTransactions::where('firm_id',$firmId)
        ->where('product_type','collection_topup')
        ->whereRaw("DATE_FORMAT(transmission_date, '%Y-%m-%d')<=?",[date('Y-m-1')])
        ->orderBy("transmission_date",'desc')
        //->selectRaw('closing_balance')
        ->first();
        return $profileLimit;
    }
    
    private function _getProfileLimitChanges($firmId){
        $profileLimits=ProfileTransactions::where('firm_id',$firmId)
        ->where('product_type','collection_topup')
        ->whereRaw("DATE_FORMAT(transmission_date, '%Y-%m-%d')>?",[date('Y-m-1')])
        ->whereRaw("DATE_FORMAT(transmission_date, '%Y-%m')=?",[date('Y-m')])
        ->orderBy("transmission_date",'asc')
        ->get();
        return $profileLimits;
    }
}
