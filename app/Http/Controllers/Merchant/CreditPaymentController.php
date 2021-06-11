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
use App\Model\{Firm,BankDetails,Role,Employees,Customer,Batch,Payments,ProfileLimits};
use Excel;
use Response;
use App\Exports\RecurringCollectionReport;


class CreditPaymentController extends Controller
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
    public function pendingList(Request $request)
    {     
        $pagename  = "Payment For Approval";
        $batchId   = decrypt($request->id);
        $firmId=Auth()->user()->firm_id;

        $batchPayments=Payments::where('batch_id',$batchId)->first();
        if($batchPayments->batch->batch_status!='pending'){
            return redirect('merchant/collection/reoccurbatch/approval-list');
        }
        return view('merchant.reoccur-collection.approval-list',compact('pagename','batchId'));
    }

    private function dtColumnForApprovalList(){
        $columns = array(
            array( 
                    'dbAlias'   => 'customers',
                    'db'        => 'mandate_id',
                    'dt' => 0,
                ),
            array( 
                    'dbAlias'   => 'customers',
                    'db'        => 'first_name',
                    'dt' => 1,
                ),
            array( 
                    'dbAlias'   => 'customers',
                    'db'        => 'last_name',
                    'dt' => 2,
                ),
            array( 'dbAlias'=>'payments','db' => 'account_holder_name',  'dt' => 3),
            array( 'dbAlias'=>'payments','db' => 'account_number',  'dt' => 4),
            array( 'dbAlias'=>'payments','db' => 'account_type',  'dt' => 5),
            array( 
                    'dbAlias'=>'payments',
                    'db' => 'payment_date',
                    'dt' => 6,
                    'formatter' => function( $d, $row ) {
                        return Helper::convertDate($row['payment_date'],'d-m-Y');
                    }
             ),
            array( 'dbAlias'=>'payments','db' => 'payment_type', 'dt' => 7),
            array(
                'dbAlias'=>'payments',
                'number'=>true,
                'db'        => 'collection_status',
                'dt'        => 8,
                'filterfrom'=> function($searchString){
                    return Helper::getDTCompatibleFilterValueForCollectionStatus($searchString);
                    
                },
                'formatter' => function( $d, $row ) {
                    return Helper::getCollectionStatusTitle($d);
                }
            ),
            array( 'dbAlias'=>'payments','db' => 'amount',  'dt' => 9),
            array(
                
                'dt'        => 10,
                'formatter' => function( $d, $row ) {
                   //return encrypt($row['id']);
                    return $row['id'];
                }
            )
        );

        return $columns;
    }


    public function ajaxApprovalList(Request $request){
        //print_r($request['columns']);
        // Table's primary key
        $primaryKey = 'id';
        $batchId   = decrypt($request->id);
        
        
        $columns = $this->dtColumnForApprovalList();
        
        $firmId = auth()->user()->firm_id;
        
        $bindings=[$batchId,$firmId];

        $whereConditions ="payments.batch_id =? and payments.firm_id=?";
        $totalCount = DB::table('payments')
                ->selectRaw('count('.$primaryKey.') totCount')
                ->whereRaw($whereConditions, $bindings)
                ->get()
                ->toArray();

        $dtWhere=DatatableHelper::filter ( $request, $columns,$bindings);
        if($dtWhere!==""){
            $whereConditions.=" and ".$dtWhere;
        }
        $orderBy = DatatableHelper::order ( $request, $columns );
        $limit   = DatatableHelper::limit ( $request, $columns );

        
        $data = DB::table('payments')
                ->selectRaw('payments.*,customers.mandate_id,customers.first_name,customers.last_name')
                ->leftJoin('customers', function ($join) {
                    $join->on('payments.customer_id', '=', 'customers.id');
                }) 
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
       
        $totalFilteredCount = DB::table('payments')
                ->selectRaw('count(payments.'.$primaryKey.') totCount, payments.'.$primaryKey)
                ->leftJoin('customers', function ($join) {
                    $join->on('payments.customer_id', '=', 'customers.id');
                }) 
                ->whereRaw($whereConditions, $bindings)
                ->get()
                ->toArray();
                
        
        echo json_encode(
            array(
                    "draw" => isset ( $request['draw'] ) ?
                        intval( $request['draw'] ) :
                        0,
                    "recordsTotal"=> intval( $totalCount[0]->totCount ),
                    "recordsFiltered" => intval( $totalFilteredCount[0]->totCount ),
                    "data" => DatatableHelper::data_output( $columns, $data )
                )
        );
        die();
    }
    
    public function updateAmount(Request $request){
        $requestStatus=['status'=>402,'message'=>'Problem in updating the record',"type"=>"danger"];
        if($request->isMethod('post')){
            //$collectionId = decrypt($request->collectionId);
            $collectionId = $request->collectionId;
            $amount = $request->amount;
            $firmId=auth()->user()->firm_id;
            $profileLimits  = ProfileLimits::where(['firm_id' => $firmId])->first();
            if(intval($amount)>0){
            
                if($profileLimits->line_collection>=intval($amount)){
                    $collectionData = Payments::where(['id' => $collectionId,'firm_id' => $firmId])->first();
                    if($collectionData){
                        $collectionData->amount = $amount;
                        if ($collectionData->save()) {
                            Helper::logStatusChange('collection',$collectionData,"Amount updated");
                                $requestStatus=['status'=>201,'message'=>'Amount Updated Successfully',"type"=>"success"];
                        }
                    }
                }else{
                    $requestStatus=['status'=>402,'message'=>'Should not greater then '.$profileLimits->line_collection,"type"=>"danger"];
                }
            }else{
                $requestStatus=['status'=>402,'message'=>'Check value of amount!',"type"=>"danger"];
            }
        }
        echo json_encode($requestStatus);
    }

    public function updateStatus(Request $request){
        $requestStatus=['status'=>402,'message'=>'Problem in updating the record',"type"=>"danger"];
        if($request->isMethod('post')){
            //$collectionId = decrypt($request->collectionId);
            $collectionId = $request->collectionId;
            $statusTitle=$request->action;
            $status=0;
            $firmId=auth()->user()->firm_id;
            $collectionData = Payments::where(['id' => $collectionId,'firm_id' => $firmId])->first();

            if($statusTitle=='approve'){
                
                if($this->_isProfileLimitCrossed($collectionData)){
                    $requestStatus=['status'=>402,'message'=>'Unable to process,Profile limit exhausted. Increase Profile Limit',"type"=>"danger"];
                    $collectionData=null;
                }else{
                    $status=1;
                }
            }elseif($statusTitle=='reject'){
                $status=2;
            }
            
            if($collectionData && $status!=0){
                $collectionData->collection_status = $status;
                if ($collectionData->save()) {
                    if($status=='reject'){
                        Helper::logStatusChange('collection',$collectionData,"Collection cancelled");
                    }else{
                        Helper::logStatusChange('collection',$collectionData,"Collection approved");
                    }
                    $requestStatus=['status'=>201,'message'=>'Status Updated Successfully',"type"=>"success"];
                }
            }
            
        }
        echo json_encode($requestStatus);
    }

    function _isProfileLimitCrossed($collectionData){
        $firmId=auth()->user()->firm_id;
        $transactionLimit=ProfileTransactions::where('firm_id',$firmId)->where('product_type','collection_topup')->orderBy("transmission_date",'desc')->first();

        $transactedAmount=DB::select(DB::raw("SELECT sum(amount) as tot_amount FROM `payments` where transmission_status in (0,1,2) and collection_status=1 and DATE_FORMAT(payment_date, '%Y-%m')=:monthYear"),array('monthYear'=>date('Y-m')));
        $transactedAmount=$transactedAmount[0];

        if(($transactionLimit->closing_balance-$transactedAmount->tot_amount)<$collectionData->amount){
            return true;
        }
        return false;
    }


    public function processedList(Request $request)
    {     
        $pagename  = "Collection Processed";
        $batchId   = decrypt($request->id);
        $firmId=Auth()->user()->firm_id;

        $batchCollections=Payments::where('batch_id',$batchId)->first();
        if($batchCollections->batch->batch_status!='processed'){
            return redirect('merchant/collection/reoccurbatch/processed-list');
        }
        return view('merchant.reoccur-collection.processed-list',compact('pagename','batchId'));
    }

    private function dtColumnForProcessedList(){
        $columns = array(
            array( 
                    'dbAlias'   => 'customers',
                    'db'        => 'mandate_id',
                    'dt' => 0,
                ),
            array( 
                    'dbAlias'   => 'customers',
                    'db'        => 'first_name',
                    'dt' => 1,
                ),
            array( 
                    'dbAlias'   => 'customers',
                    'db'        => 'last_name',
                    'dt' => 2,
                ),
            array( 'dbAlias'=>'payments','db' => 'account_holder_name',  'dt' => 3),
            array( 'dbAlias'=>'payments','db' => 'account_number',  'dt' => 4),
            array( 'dbAlias'=>'payments','db' => 'account_type',  'dt' => 5),
            array( 
                    'dbAlias'=>'payments',
                    'db' => 'payment_date',
                    'dt' => 6,
                    'formatter' => function( $d, $row ) {
                        return Helper::convertDate($row['payment_date'],'d-m-Y');
                    }
             ),
            array( 'dbAlias'=>'payments','db' => 'payment_type',  'dt' => 7),
            
            array(
                'dbAlias'=>'payments',
                'number'=>true,
                'db'        => 'transmission_status',
                'dt'        => 8,
                'filterfrom'=> function($searchString){
                    return Helper::getDTCompatibleFilterValueForCollectionStatus($searchString);
                    
                },
                'formatter' => function( $d, $row ) {
                    return Helper::getCollectionTransmissionTitle($d);
                }
            ),
            array(
                'dbAlias'=>'payments',
                'number'=>true,
                'db'        => 'transaction_status',
                'dt'        => 9,
                'filterfrom'=> function($searchString){
                    return Helper::getDTCompatibleFilterValueForCollectionTransactionStatus($searchString);
                    
                },
                'formatter' => function( $d, $row ) {
                    return Helper::getCollectionTransactionTitle($d);
                }
            ),
            array( 'dbAlias'=>'payments','db' => 'amount',  'dt' => 10),
            array( 'dbAlias'=>'transaction_error_codes','db' => 'description',  'dt' => 11),
            array( 
                    'dbAlias'=>'payments',
                    'db' => 'date_of_failure',
                    'dt' => 12,
                    'formatter' => function( $d, $row ) {
                        return Helper::convertDate($row['date_of_failure'],'d-m-Y');
                    }
             ),
        );

        return $columns;
    }


    public function ajaxProcessedList(Request $request){
        //print_r($request['columns']);
        // Table's primary key
        $primaryKey = 'id';
        $batchId   = decrypt($request->id);
        
        
        $columns = $this->dtColumnForProcessedList();
        
        $firmId = auth()->user()->firm_id;
        
        $bindings=[$batchId,$firmId];

        $whereConditions ="payments.batch_id =? and payments.firm_id=?";
        $totalCount = DB::table('payments')
                ->selectRaw('count(payments.'.$primaryKey.') totCount')
                ->leftJoin('customers', function ($join) {
                    $join->on('payments.customer_id', '=', 'customers.id');
                }) 
                ->leftJoin('transaction_error_codes', 'transaction_error_codes.id', '=', 'payments.tranx_error_id')
                ->whereRaw($whereConditions, $bindings)
                ->get()
                ->toArray();

        $dtWhere=DatatableHelper::filter ( $request, $columns,$bindings);
        if($dtWhere!==""){
            $whereConditions.=" and ".$dtWhere;
        }
        $orderBy = DatatableHelper::order ( $request, $columns );
        $limit   = DatatableHelper::limit ( $request, $columns );

        
        $data = DB::table('payments')
                ->selectRaw('payments.*,customers.mandate_id,customers.first_name,customers.last_name,transaction_error_codes.description')
                ->leftJoin('customers', function ($join) {
                    $join->on('payments.customer_id', '=', 'customers.id');
                }) 
                ->leftJoin('transaction_error_codes', 'transaction_error_codes.id', '=', 'payments.tranx_error_id')
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
                
       
        $totalFilteredCount = DB::table('payments')
                ->selectRaw('count(payments.'.$primaryKey.') totCount, payments.'.$primaryKey)
                ->leftJoin('customers', function ($join) {
                    $join->on('payments.customer_id', '=', 'customers.id');
                })
                ->leftJoin('transaction_error_codes', 'transaction_error_codes.id', '=', 'payments.tranx_error_id')
                ->whereRaw($whereConditions, $bindings)
                ->get()
                ->toArray();
                
        
        echo json_encode(
            array(
                    "draw" => isset ( $request['draw'] ) ?
                        intval( $request['draw'] ) :
                        0,
                    "recordsTotal"=> intval( $totalCount[0]->totCount ),
                    "recordsFiltered" => intval( $totalFilteredCount[0]->totCount ),
                    "data" => DatatableHelper::data_output( $columns, $data )
                )
        );
        die();
    }

    public function submittedList(Request $request)
    {     
        $pagename  = "Collection Sent to Bank";
        $batchId   = decrypt($request->id);
        $firmId=Auth()->user()->firm_id;

        $batchCollections=Payments::where('batch_id',$batchId)->first();
        if($batchCollections->batch->batch_status!='sent'){
            return redirect('merchant/collection/reoccurbatch/submitted-list');
        }
        return view('merchant.reoccur-collection.submitted-list',compact('pagename','batchId'));
    }

    function failedTransactions(Request $request){
        
        $pagename  = "Failed Transaction";
        $urlParams=[];
        if(isset($request->startat) && !empty($request->startat)){
            $urlParams['startat']=$request->startat;
        }

        if(isset($request->upto) && !empty($request->upto)){
            $urlParams['upto']=$request->upto;
        }
        $urlStrg=http_build_query($urlParams);
        return view('merchant.reoccur-collection.failedTransactions',compact('pagename','transactions','request','urlStrg','urlParams'));
    }

    private function dtColumnForFailedTranx(){
        $columns = array(
             array( 
                    'dbAlias'   => 'customers',
                    'db'        => 'mandate_id',
                    'dt' => 0,
                ),
            array( 
                    'dbAlias'   => 'customers',
                    'db'        => 'first_name',
                    'dt' => 1,
                ),
            array( 
                    'dbAlias'   => 'customers',
                    'db'        => 'last_name',
                    'dt' => 2,
                ),
            array( 'dbAlias'=>'payments','db' => 'account_holder_name',  'dt' => 3),
            array( 'dbAlias'=>'payments','db' => 'account_number',  'dt' => 4),
            array( 'dbAlias'=>'payments','db' => 'account_type',  'dt' => 5),
            array( 
                    'dbAlias'=>'payments',
                    'db' => 'payment_date',
                    'dt' => 6,
                    'formatter' => function( $d, $row ) {
                        return Helper::convertDate($row['payment_date'],'d-m-Y');
                    }
             ),
            array( 'dbAlias'=>'payments','db' => 'payment_type',  'dt' => 7),
            array( 'dbAlias'=>'payments','db' => 'amount',  'dt' => 8),
            array( 'dbAlias'=>'transaction_error_codes','db' => 'description',  'dt' => 9),
            array( 
                    'dbAlias'=>'payments',
                    'db' => 'date_of_failure',
                    'dt' => 10,
                    'formatter' => function( $d, $row ) {
                        return Helper::convertDate($row['date_of_failure'],'d-m-Y');
                    }
             ),
            
        );

        return $columns;
    }

    public function ajaxFailedTranx(Request $request){
        //print_r($request['columns']);
        // Table's primary key
        $primaryKey = 'id';
        
        
        
        $columns = $this->dtColumnForFailedTranx();
        
        $firmId = auth()->user()->firm_id;
        
        $bindings=['reoccur',2,2,0,$firmId];

        $whereConditions ="customers.cust_type=? and payments.transaction_status =? and payments.transmission_status=? and transaction_error_codes.is_dispute=? and payments.firm_id=?";

        if(isset($request->startat) && !empty($request->startat)){
            
            $bindings[]=$request->startat;
            $whereConditions.=" and payments.payment_date>=?";
        }

        if(isset($request->upto) && !empty($request->upto)){
            
            $bindings[]=$request->upto;
            $whereConditions.=" and payments.payment_date<=?";
        }
        

        $totalCount = DB::table('payments')
                ->selectRaw('count(payments.'.$primaryKey.') totCount')
                ->leftJoin('transaction_error_codes', 'transaction_error_codes.id', '=', 'payments.tranx_error_id')
                ->leftJoin('customers', function ($join) {
                    $join->on('payments.customer_id', '=', 'customers.id');
                })
                ->whereRaw($whereConditions, $bindings)
                ->get()
                ->toArray();

        $dtWhere=DatatableHelper::filter ( $request, $columns,$bindings);
        if($dtWhere!==""){
            $whereConditions.=" and ".$dtWhere;
        }
        $orderBy = DatatableHelper::order ( $request, $columns );
        $limit   = DatatableHelper::limit ( $request, $columns );

        
        $data = DB::table('payments')
                ->selectRaw('payments.*,customers.first_name,customers.last_name,customers.mandate_id,transaction_error_codes.description')
                ->leftJoin('transaction_error_codes', 'transaction_error_codes.id', '=', 'payments.tranx_error_id')
                ->leftJoin('customers', function ($join) {
                    $join->on('payments.customer_id', '=', 'customers.id');
                    }) 
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
       
        $totalFilteredCount = DB::table('payments')
                ->selectRaw('count(payments.'.$primaryKey.') totCount, payments.'.$primaryKey)
                ->leftJoin('transaction_error_codes', 'transaction_error_codes.id', '=', 'payments.tranx_error_id')
                ->leftJoin('customers', function ($join) {
                    $join->on('payments.customer_id', '=', 'customers.id');
                }) 
                ->whereRaw($whereConditions, $bindings)
                ->get()
                ->toArray();
                
        
        echo json_encode(
            array(
                    "draw" => isset ( $request['draw'] ) ?
                        intval( $request['draw'] ) :
                        0,
                    "recordsTotal"=> intval( $totalCount[0]->totCount ),
                    "recordsFiltered" => intval( $totalFilteredCount[0]->totCount ),
                    "data" => DatatableHelper::data_output( $columns, $data )
                )
        );
        die();
    }

    function disputedTransactions(Request $request){
      $pagename  = "Disputed Transaction";
      $urlParams=[];
        if(isset($request->startat) && !empty($request->startat)){
            $urlParams['startat']=$request->startat;
        }

        if(isset($request->upto) && !empty($request->upto)){
            $urlParams['upto']=$request->upto;
        }
        $urlStrg=http_build_query($urlParams);
     
      return view('merchant.reoccur-collection.disputedTransactions',compact('pagename','transactions','request','urlStrg','urlParams'));
    }

    public function ajaxDisputedTranx(Request $request){
        //print_r($request['columns']);
        // Table's primary key
        $primaryKey = 'id';
        
        
        
        $columns = $this->dtColumnForFailedTranx();
        
        $firmId = auth()->user()->firm_id;
        
        $bindings=['reoccur',3,2,1,$firmId];

        $whereConditions ="customers.cust_type=? and payments.transaction_status =? and payments.transmission_status=? and transaction_error_codes.is_dispute=? and payments.firm_id=?";

        if(isset($request->startat) && !empty($request->startat)){
            
            $bindings[]=$request->startat;
            $whereConditions.=" and payments.payment_date>=?";
        }

        if(isset($request->upto) && !empty($request->upto)){
            
            $bindings[]=$request->upto;
            $whereConditions.=" and payments.payment_date<=?";
        }

        $totalCount = DB::table('payments')
                ->selectRaw('count(payments.'.$primaryKey.') totCount')
                ->leftJoin('transaction_error_codes', 'transaction_error_codes.id', '=', 'payments.tranx_error_id')
                ->leftJoin('customers', function ($join) {
                    $join->on('payments.customer_id', '=', 'customers.id');
                })
                ->whereRaw($whereConditions, $bindings)
                ->get()
                ->toArray();

        $dtWhere=DatatableHelper::filter ( $request, $columns,$bindings);
        if($dtWhere!==""){
            $whereConditions.=" and ".$dtWhere;
        }
        $orderBy = DatatableHelper::order ( $request, $columns );
        $limit   = DatatableHelper::limit ( $request, $columns );

        
        $data = DB::table('payments')
                ->selectRaw('payments.*,customers.first_name,customers.last_name,customers.mandate_id,transaction_error_codes.description')
                ->leftJoin('transaction_error_codes', 'transaction_error_codes.id', '=', 'payments.tranx_error_id')
                ->leftJoin('customers', function ($join) {
                    $join->on('payments.customer_id', '=', 'customers.id');
                    }) 
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
       
        $totalFilteredCount = DB::table('payments')
                ->selectRaw('count(payments.'.$primaryKey.') totCount, payments.'.$primaryKey)
                ->leftJoin('transaction_error_codes', 'transaction_error_codes.id', '=', 'payments.tranx_error_id')
                ->leftJoin('customers', function ($join) {
                    $join->on('payments.customer_id', '=', 'customers.id');
                }) 
                ->whereRaw($whereConditions, $bindings)
                ->get()
                ->toArray();
                
        
        echo json_encode(
            array(
                    "draw" => isset ( $request['draw'] ) ?
                        intval( $request['draw'] ) :
                        0,
                    "recordsTotal"=> intval( $totalCount[0]->totCount ),
                    "recordsFiltered" => intval( $totalFilteredCount[0]->totCount ),
                    "data" => DatatableHelper::data_output( $columns, $data )
                )
        );
        die();
    }

    function reports1(Request $request){
        $pagename  = "Collection Reports";
        $firmId = auth()->user()->firm_id;
        $urlParams=[];
        $bindings=[$firmId];

        $whereConditions ="customers.firm_id=?";

        
        if(isset($request->mandate_id) && !empty($request->mandate_id)){
            $urlParams['mandate_id']=$request->mandate_id;
            $bindings[]=$request->mandate_id;
            $whereConditions.=" and customers.mandate_id=?";
        }

        if(isset($request->first_name) && !empty($request->first_name)){
            $urlParams['first_name']=$request->first_name;
            $bindings[]=$request->first_name;
            $whereConditions.=" and customers.first_name =?";
        }

        if(isset($request->last_name) && !empty($request->last_name)){
            $urlParams['last_name']=$request->last_name;
            $bindings[]=$request->last_name;
            $whereConditions.=" and customers.last_name =?";
        }

        if(isset($request->amount) && !empty($request->amount)){
            $urlParams['amount']=$request->amount;
            $bindings[]=$request->amount;
            $whereConditions.=" and payments.amount =?";
        }

        if(isset($request->status) && !empty($request->status)){
            $urlParams['status']=$request->status;
            $bindings[]=$request->status;
            $whereConditions.=" and payments.transaction_status =?";
        }

        if(isset($request->colldate) && !empty($request->colldate)){
            $urlParams['colldate']=$request->colldate;
            $bindings[]=Helper::convertDate($request->colldate,'Y-m-d');
            $whereConditions.=" and payments.payment_date =?";
        }

        $transactions=payments::whereRaw($whereConditions, $bindings)
                ->leftJoin('customers', function ($join) {
                    $join->on('payments.customer_id', '=', 'customers.id');
                })->orderBy('payments.id', 'desc')->paginate(10);
        return view('merchant.reoccur-collection.reports',compact('transactions','pagename','request','transactions','urlParams'));
    }

    function reports(Request $request){
        $pagename  = "Transaction Reports";
        $firmId = auth()->user()->firm_id;
        $urlParams=[];
        $bindings=['reoccur',$firmId];

        $whereConditions ="customers.cust_type=? and customers.firm_id=?";

        
        if(isset($request->mandate_id) && !empty($request->mandate_id)){
            $urlParams['mandate_id']=$request->mandate_id;
            
        }

        if(isset($request->first_name) && !empty($request->first_name)){
            $urlParams['first_name']=$request->first_name;
            
        }

        if(isset($request->last_name) && !empty($request->last_name)){
            $urlParams['last_name']=$request->last_name;
            
        }

        if(isset($request->amount) && !empty($request->amount)){
            $urlParams['amount']=$request->amount;
            
        }

        if(isset($request->status) && !empty($request->status)){
            $urlParams['status']=$request->status;
        }

        if(isset($request->startat) && !empty($request->startat)){
            $urlParams['startat']=$request->startat;
        }

        if(isset($request->upto) && !empty($request->upto)){
            $urlParams['upto']=$request->upto;
        }

        if(sizeof($request->query())>0){
            return view('merchant.reoccur-collection.generate-reports',compact('transactions','pagename','request','transactions','urlParams'));
        }else{
            return view('merchant.reoccur-collection.reports',compact('transactions','pagename','request','transactions','urlParams'));
        }
        
    }

    private function dtColumnForReport(){
        $columns = array(
             array( 
                    'dbAlias'   => 'customers',
                    'db'        => 'mandate_id',
                    'dt' => 0,
                ),
            array( 
                    'dbAlias'   => 'customers',
                    'db'        => 'first_name',
                    'dt' => 1,
                ),
            array( 
                    'dbAlias'   => 'customers',
                    'db'        => 'last_name',
                    'dt' => 2,
                ),
            array( 'dbAlias'=>'payments','db' => 'account_holder_name',  'dt' => 3),
            array( 'dbAlias'=>'payments','db' => 'account_number',  'dt' => 4),
            array( 'dbAlias'=>'payments','db' => 'account_type',  'dt' => 5),
            array( 
                    'dbAlias'=>'payments',
                    'db' => 'payment_date',
                    'dt' => 6,
                    'formatter' => function( $d, $row ) {
                        return Helper::convertDate($row['payment_date'],'d-m-Y');
                    }
             ),
            array( 'dbAlias'=>'payments','db' => 'payment_type',  'dt' => 7),
            array( 'dbAlias'=>'payments','db' => 'amount',  'dt' => 8),
            array(
                'dbAlias'=>'payments',
                'number'=>true,
                'db'        => 'transaction_status',
                'dt'        => 9,
                'filterfrom'=> function($searchString){
                    return Helper::getDTCompatibleFilterValueForCollectionTransactionStatus($searchString);
                    
                },
                'formatter' => function( $d, $row ) {

                    return Helper::getCollectionTransactionTitle($d);
                }
            ),
            array( 
                    'dbAlias'=>'payments',
                    'db' => 'date_of_failure',
                    'dt' => 10,
                    'formatter' => function( $d, $row ) {
                        return Helper::convertDate($row['date_of_failure'],'d-m-Y');
                    }
             ),
            
        );

        return $columns;
    }

    function ajaxReports(Request $request){
        $primaryKey = 'id';
        
        
        
        $columns = $this->dtColumnForReport();
        
        $firmId = auth()->user()->firm_id;
        
        $bindings=['reoccur',2,$firmId];

        $whereConditions ="customers.cust_type=? and payments.transmission_status=? and payments.firm_id=?";

        if(isset($request->startat) && !empty($request->startat)){
            
            $bindings[]=$request->startat;
            $whereConditions.=" and payments.payment_date>=?";
        }

        if(isset($request->upto) && !empty($request->upto)){
            
            $bindings[]=$request->upto;
            $whereConditions.=" and payments.payment_date<=?";
        }
        if(isset($request->mandate_id) && !empty($request->mandate_id)){
            $bindings[]=$request->mandate_id;
            $whereConditions.=" and customers.mandate_id=?";
        }

        if(isset($request->first_name) && !empty($request->first_name)){
            $bindings[]=$request->first_name;
            $whereConditions.=" and customers.first_name =?";
        }

        if(isset($request->last_name) && !empty($request->last_name)){
            $bindings[]=$request->last_name;
            $whereConditions.=" and customers.last_name =?";
        }

        if(isset($request->amount) && !empty($request->amount)){
            $bindings[]=$request->amount;
            $whereConditions.=" and payments.amount =?";
        }

        if($request->status>=0){
            $bindings[]=$request->status;
            $whereConditions.=" and payments.transaction_status =?";
        }
        $totalCount = DB::table('payments')
                ->selectRaw('count(payments.'.$primaryKey.') totCount')
                ->leftJoin('customers', function ($join) {
                    $join->on('payments.customer_id', '=', 'customers.id');
                })
                ->whereRaw($whereConditions, $bindings)
                ->get()
                ->toArray();

        $dtWhere=DatatableHelper::filter ( $request, $columns,$bindings);
        if($dtWhere!==""){
            $whereConditions.=" and ".$dtWhere;
        }
        $orderBy = DatatableHelper::order ( $request, $columns );
        $limit   = DatatableHelper::limit ( $request, $columns );

        
        $data = DB::table('payments')
                ->selectRaw('payments.*,customers.first_name,customers.last_name,customers.mandate_id')
                ->leftJoin('customers', function ($join) {
                    $join->on('payments.customer_id', '=', 'customers.id');
                    }) 
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
       
        $totalFilteredCount = DB::table('payments')
                ->selectRaw('count(payments.'.$primaryKey.') totCount, payments.'.$primaryKey)
                ->leftJoin('customers', function ($join) {
                    $join->on('payments.customer_id', '=', 'customers.id');
                }) 
                ->whereRaw($whereConditions, $bindings)
                ->get()
                ->toArray();
                
        
        echo json_encode(
            array(
                    "draw" => isset ( $request['draw'] ) ?
                        intval( $request['draw'] ) :
                        0,
                    "recordsTotal"=> intval( $totalCount[0]->totCount ),
                    "recordsFiltered" => intval( $totalFilteredCount[0]->totCount ),
                    "data" => DatatableHelper::data_output( $columns, $data )
                )
        );
        die();
        
    }

    function exportreport(Request $request){
        $firmId = auth()->user()->firm_id;
        
        $bindings=[$firmId];

        $whereConditions ="customers.firm_id=?";
        if(isset($request->mandate_id) && !empty($request->mandate_id)){
            $bindings[]=$request->mandate_id;
            $whereConditions.=" and customers.mandate_id=?";
        }

        if(isset($request->first_name) && !empty($request->first_name)){
            $bindings[]=$request->first_name;
            $whereConditions.=" and customers.first_name =?";
        }

        if(isset($request->last_name) && !empty($request->last_name)){
            $bindings[]=$request->last_name;
            $whereConditions.=" and customers.last_name =?";
        }

        if(isset($request->amount) && !empty($request->amount)){
            $bindings[]=$request->amount;
            $whereConditions.=" and payments.amount =?";
        }

        if(isset($request->status) && !empty($request->status)){
            $bindings[]=$request->status;
            $whereConditions.=" and payments.transaction_status =?";
        }

        if(isset($request->colldate) && !empty($request->colldate)){
            $bindings[]=Helper::convertDate($request->colldate,'Y-m-d');
            $whereConditions.=" and payments.payment_date =?";
        }
        return (new RecurringCollectionReport($bindings,$whereConditions))->download('recurringCollectionReport.xlsx');

                //->export('xls');

    }
}
