<?php

namespace App\Http\Controllers\Merchant;

use App\User;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\DatatableHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use App\Model\{Firm,BankDetails,Role,CompanyInformation,Employees,Customer,TempCustomers,PublicHolidays,PaymentBatches,Payments,PaymentLedgers};
//use Maatwebsite\Excel\Facades\Excel;
use Response;

class CreditorBatchController extends Controller
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
    public function index()
    {     
        $pagename  = "Pending Batches";
        return view('merchant.creditor-batch.index',compact('pagename'));
        
        
    }

    private function dataTableColumnBindings(){
        $columns = array(
            array( 'db' => 'batch_name', 'dt' => 0,
                'formatter' => function( $d, $row ) {
                    $row['id']=encrypt($row['id']);
                   return $row;
                } ),
            array( 'db' => 'batch_service_type', 'dt' => 1,
                    'formatter' => function( $d, $row ) {
                       return ucfirst($row['batch_service_type']);
                    }
                ),
            array( 
                'dbAlias'   => 'payment_batches',
                'db' => 'payment_date',  'dt' => 2,
                    'formatter' => function( $d, $row ) {
                    return Helper::convertDate($row['payment_date'],'d-m-Y');
                    }
             ),
            array( 'db' => 'created_on',  'dt' => 3,
                    'formatter' => function( $d, $row ) {
                    return Helper::convertDate($row['created_on'],'d-m-Y');
                    }
             ),
            array(
                
                'dt'        => 4,
                'formatter' => function( $d, $row ) {
                   return $row['amount'];
                }
            ),
            array(
                
                'dt'        => 5,
                'formatter' => function( $d, $row ) {
                   return $row['trnx_count'];
                }
            ),
            array(
                
                'dt'        => 6,
                'formatter' => function( $d, $row ) {
                   return encrypt($row['id']);
                }
            )
        );

        return $columns;
    }

    public function ajaxPendingList(Request $request){
        
        $firmId = auth()->user()->firm_id;
        
        $bindings=['credit',$firmId,'pending'];
        $whereConditions ="payment_batches.batch_type =? and payment_batches.firm_id=? and payment_batches.batch_status=?";
        echo $this->getBatchData($request,$bindings,$whereConditions);
        
        die();
    }

    public function statusUpdate(Request $request){
        $requestStatus=['status'=>402,'message'=>'Problem in updating the record',"type"=>"danger"];


        if($request->isMethod('post')){
            $batchId=decrypt($request->batchId);
            $statusTitle=$request->action;

            $status='cancelled';
            if($statusTitle=='approve'){
                $statusTitle="approved";
            }elseif($statusTitle=='cancel'){
                $statusTitle="cancelled";
            }
            
            
            $firmId = auth()->user()->firm_id;
            $paymentBatch = PaymentBatches::where('id',$batchId)->where('firm_id',$firmId)->where('batch_status','pending')->first();
            
            if(!is_null($paymentBatch)){
                $batchAmount=Payments::where(['batch_id' => $batchId])->whereIn('payment_status',[0,1])->groupBy('batch_id')->selectRaw('*, sum(amount) as tot_amount')->first(); 
                
                $paymentBatch->batch_status = $statusTitle;
                $paymentLedger=PaymentLedgers::where('firm_id',$firmId)->orderBy("id",'desc')->first();

                if($paymentLedger->closing_amount>=$batchAmount['tot_amount'] || $statusTitle=='cancelled'){

                    if ($paymentBatch->save()) {
                        if($statusTitle=='approved'){
                            $this->addBatchToLedger($paymentBatch,$paymentLedger,$batchAmount);
                            $this->changePaymentStatus(1,$paymentBatch->id);
                        }else{
                            $this->changePaymentStatus(2,$paymentBatch->id);
                        }
                        $requestStatus=['status'=>201,'message'=>'Batch '.$statusTitle.' successfully',"type"=>"success"];
                    }
                }else{
                    $requestStatus=['status'=>201,'message'=>'Batch amount is greater then available amount, Should be less then '.$paymentLedger->closing_amount,"type"=>"danger"];
                }
            }
            
        }
        echo json_encode($requestStatus);
        exit();
    }

    private function addBatchToLedger($paymentBatch,$paymentLedger,$batchAmount){
        $newLedgerEntry= new PaymentLedgers();
        $newLedgerEntry->firm_id=$paymentBatch->firm_id;
        $newLedgerEntry->target_reffrence_id=$paymentBatch->id;
        $newLedgerEntry->transaction_type='batch_payment';
        $newLedgerEntry->ledger_desc="Payment for batch ".$paymentBatch->batch_name;
        $newLedgerEntry->amount=$batchAmount['tot_amount']*(-1);
        $newLedgerEntry->closing_amount=$paymentLedger->closing_amount-$batchAmount['tot_amount'];
        $newLedgerEntry->entry_type='dr';
        $newLedgerEntry->entry_date=date('Y-m-d');
        $newLedgerEntry->save();
    }

    private function changePaymentStatus($status,$batchId){
        Payments::where(['batch_id' => $batchId])->whereIn('payment_status',[0,1])->update(['payment_status' => $status]);
    }
    public function queued()
    {     
        $pagename  = "Queued to Bank";
        return view('merchant.creditor-batch.queued',compact('pagename'));
        
        
    }

    public function ajaxQueuedList(Request $request){
        
        $firmId = auth()->user()->firm_id;
        
        $bindings=['credit',$firmId,'approved','sent'];
        $whereConditions ="payment_batches.batch_type =? and payment_batches.firm_id=? and (payment_batches.batch_status=? or payment_batches.batch_status=? )";
        echo $this->getBatchData($request,$bindings,$whereConditions);
        
        die();
    }

    public function getBatchData($request,$bindings,$whereConditions){
        //print_r($request['columns']);
        // Table's primary key
        $primaryKey = 'id';
        
        $columns = $this->dataTableColumnBindings();

        
        $totalCount = DB::table('payment_batches')
                ->selectRaw('count(payment_batches.'.$primaryKey.') totCount')
                ->leftJoin('payments', function ($join) {
                    $join->on('payments.batch_id', '=', 'payment_batches.id');
                })
                ->whereRaw($whereConditions, $bindings)
                ->groupBy('payments.batch_id')
                ->get()
                ->toArray();

        $dtWhere=DatatableHelper::filter ( $request, $columns,$bindings);
        if($dtWhere!==""){
            $whereConditions.=" and ".$dtWhere;
        }
        $orderBy = DatatableHelper::order ( $request, $columns );
        $limit   = DatatableHelper::limit ( $request, $columns );

        
        $data = DB::table('payment_batches')
                ->selectRaw('payment_batches.*,sum(payments.amount) as amount,count(payments.id) as trnx_count')
                ->leftJoin('payments', function ($join) {
                    $join->on('payments.batch_id', '=', 'payment_batches.id');
                })
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->groupBy('payments.batch_id')
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
       
        $totalFilteredCount = DB::table('payment_batches')
                ->selectRaw('count(payment_batches.'.$primaryKey.') totCount, payment_batches.'.$primaryKey)
                ->leftJoin('payments', function ($join) {
                    $join->on('payments.batch_id', '=', 'payment_batches.id');
                })
                ->whereRaw($whereConditions, $bindings)
                ->groupBy('payments.batch_id')
                ->get()
                ->count();
                
        if(is_null($totalCount) || sizeof($totalCount)<=0){
            $totalRecords=0;
        }else{
            $totalRecords=$totalCount[0]->totCount;
        }

        

        return json_encode(
            array(
                    "draw" => isset ( $request['draw'] ) ? intval( $request['draw'] ) :0,
                    "recordsTotal"=> intval( $totalRecords ),
                    "recordsFiltered" => intval( $totalFilteredCount ),
                    "data" => DatatableHelper::data_output( $columns, $data )
                )
        );
        
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function processedList()
    {     
        $pagename  = "Processed Batches";
        return view('merchant.creditor-batch.processed',compact('pagename'));
    }

    public function ajaxProcessedList(Request $request){
        
        $firmId = auth()->user()->firm_id;
        
        $bindings=['credit',$firmId,'processed'];
        $whereConditions ="payment_batches.batch_type =? and payment_batches.firm_id=? and payment_batches.batch_status=?";
        echo $this->getBatchData($request,$bindings,$whereConditions);
    }

    
}
