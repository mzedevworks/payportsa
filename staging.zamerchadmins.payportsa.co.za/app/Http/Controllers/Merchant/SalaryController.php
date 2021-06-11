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
use App\Model\{Firm,BankDetails,Role,Employees,Customer,ProfileLimits,Payments,PaymentBatches};
use Excel;
use Response;
use App\Exports\RecurringCollectionReport;


class SalaryController extends Controller
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
        $pagename  = "Transactions in batch";
        $batchId   = decrypt($request->id);
        $firmId=Auth()->user()->firm_id;

        $batchCollections=Payments::where('batch_id',$batchId)->first();
        if($batchCollections->batch->batch_status!='pending'){
            return redirect('merchant/employees/batch/pending');
        }
        return view('merchant.salary.pending-list',compact('pagename','batchId'));
    }
    
    public function pendingListView(Request $request)
    {     
        $pagename  = "Transactions in batch";
        $batchId   = decrypt($request->id);
        $firmId=Auth()->user()->firm_id;

        $batchCollections=Payments::where('batch_id',$batchId)->first();
        if($batchCollections->batch->batch_status!='pending'){
            return redirect('merchant/employees/batch/pending');
        }
        return view('merchant.salary.pending-list-view',compact('pagename','batchId'));
    }

    private function dtColumnForPendingList(){
        $columns = array(
            array( 
                    'dbAlias'   => 'employees',
                    'db'        => 'id_number',
                    'dt' => 0,
                ),
            array( 
                    'dbAlias'   => 'employees',
                    'db'        => 'first_name',
                    'dt' => 1,
                ),
            array( 
                    'dbAlias'   => 'employees',
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
            
            array(
                'dbAlias'=>'payments',
                'number'=>true,
                'db'        => 'payment_status',
                'dt'        => 7,
                'filterfrom'=> function($searchString){
                    return Helper::getDTCompatibleFilterValueForCollectionStatus($searchString);
                    
                },
                'formatter' => function( $d, $row ) {
                    return Helper::getCollectionStatusTitle($d);
                }
            ),
            array( 'dbAlias'=>'payments','db' => 'amount',  'dt' => 8),
            array(
                
                'dt'        => 9,
                'formatter' => function( $d, $row ) {
                   //return encrypt($row['id']);
                    return $row['id'];
                }
            )

            
        );

        return $columns;
    }

    public function ajaxPendingList(Request $request){
        //print_r($request['columns']);
        // Table's primary key
        $primaryKey = 'id';
        $batchId   = decrypt($request->id);
        
        
        $columns = $this->dtColumnForPendingList();
        
        $firmId = auth()->user()->firm_id;
        $paymentBatch=PaymentBatches::where(['firm_id'=>$firmId,'id'=>$batchId,'batch_type'=>'salary'])->first();
        if(is_null($paymentBatch)){
            echo json_encode(
                array(
                        "draw" => 0,
                        "recordsTotal"=> 0,
                        "recordsFiltered" => 0,
                        "data" => []
                    )
            );
            die();
        }

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
                ->selectRaw('payments.*,employees.id_number,employees.first_name,employees.last_name')
                ->leftJoin('employees', function ($join) {
                    $join->on('payments.employee_id', '=', 'employees.id');
                }) 
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
       
        $totalFilteredCount = DB::table('payments')
                ->selectRaw('count(payments.'.$primaryKey.') totCount, payments.'.$primaryKey)
                ->leftJoin('employees', function ($join) {
                    $join->on('payments.employee_id', '=', 'employees.id');
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
            $paymentId = $request->paymentId;
            $amount = $request->amount;
            $firmId=auth()->user()->firm_id;
            if(intval($amount)>0){
                //amount can be updated untill it is not transmitted
                $paymentData = Payments::where(['id' => $paymentId,'firm_id' => $firmId,'transmission_status'=>0])->first();
                if($paymentData){
                    $paymentData->amount = $amount;
                    if ($paymentData->save()) {
                            $requestStatus=['status'=>201,'message'=>'Amount Updated Successfully',"type"=>"success"];
                    }
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
            $paymentId = $request->paymentId;
            $statusTitle=$request->action;
            $status=0;
            $firmId=auth()->user()->firm_id;
            $paymentData = Payments::where(['id' => $paymentId,'firm_id' => $firmId,'transmission_status'=>0])->first();

            if($statusTitle=='approve'){
                $status=1;
            }elseif($statusTitle=='reject'){
                $status=2;
            }
            
            if(!is_null($paymentData) && $status!=0){
                $paymentData->payment_status = $status;
                if ($paymentData->save()) {
                    $requestStatus=['status'=>201,'message'=>'Status Updated Successfully',"type"=>"success"];
                }
            }
            
        }
        echo json_encode($requestStatus);
    }

    public function queuedList(Request $request)
    {     
        $pagename  = "Transactions in batch";
        $batchId   = decrypt($request->id);
        $firmId=Auth()->user()->firm_id;

        $batchCollections=Payments::where('batch_id',$batchId)->first();
        if($batchCollections->batch->batch_status!='approved'){
            return redirect('merchant/employees/batch/queued');
        }
        return view('merchant.salary.queued-list',compact('pagename','batchId'));
    }

    private function dtColumnForQueuedList(){
        $columns = array(
            array( 
                    'dbAlias'   => 'employees',
                    'db'        => 'id_number',
                    'dt' => 0,
                ),
            array( 
                    'dbAlias'   => 'employees',
                    'db'        => 'first_name',
                    'dt' => 1,
                ),
            array( 
                    'dbAlias'   => 'employees',
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
            
            array(
                'dbAlias'=>'payments',
                'number'=>true,
                'db'        => 'payment_status',
                'dt'        => 7,
                'filterfrom'=> function($searchString){
                    return Helper::getDTCompatibleFilterValueForCollectionStatus($searchString);
                    
                },
                'formatter' => function( $d, $row ) {
                    return Helper::getCollectionStatusTitle($d);
                }
            ),
            array( 'dbAlias'=>'payments','db' => 'amount',  'dt' => 8)
            
        );

        return $columns;
    }


    public function ajaxQueuedList(Request $request){
        //print_r($request['columns']);
        // Table's primary key
        $primaryKey = 'id';
        $batchId   = decrypt($request->id);
        
        
        $columns = $this->dtColumnForQueuedList();
        
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
                ->selectRaw('payments.*,employees.id_number,employees.first_name,employees.last_name')
                ->leftJoin('employees', function ($join) {
                    $join->on('payments.employee_id', '=', 'employees.id');
                }) 
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
       
        $totalFilteredCount = DB::table('payments')
                ->selectRaw('count(payments.'.$primaryKey.') totCount, payments.'.$primaryKey)
                ->leftJoin('employees', function ($join) {
                    $join->on('payments.employee_id', '=', 'employees.id');
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
    

    public function processedList(Request $request)
    {     
        $pagename  = "Processed Payments";
        $batchId   = decrypt($request->id);
        $firmId=Auth()->user()->firm_id;

        $batchCollections=Payments::where('batch_id',$batchId)->first();
        if($batchCollections->batch->batch_status!='processed'){
            return redirect('merchant/employees/batch/processed');
        }
        return view('merchant.salary.processed-list',compact('pagename','batchId'));
    }

    private function dtColumnForProcessedList(){
        $columns = array(
            array( 
                    'dbAlias'   => 'employees',
                    'db'        => 'id_number',
                    'dt' => 0,
                ),
            array( 
                    'dbAlias'   => 'employees',
                    'db'        => 'first_name',
                    'dt' => 1,
                ),
            array( 
                    'dbAlias'   => 'employees',
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
            
            array(
                'dbAlias'=>'payments',
                'number'=>true,
                'db'        => 'transmission_status',
                'dt'        => 7,
                'filterfrom'=> function($searchString){
                    return Helper::getDTCompatibleFilterValueForCollectionTransmissionStatus($searchString);
                    
                },
                'formatter' => function( $d, $row ) {
                    return Helper::getCollectionTransmissionTitle($d);
                }
            ),
            array(
                'dbAlias'=>'payments',
                'number'=>true,
                'db'        => 'transaction_status',
                'dt'        => 8,
                'filterfrom'=> function($searchString){
                    return Helper::getDTCompatibleFilterValueForCollectionTransactionStatus($searchString);
                    
                },
                'formatter' => function( $d, $row ) {
                    return Helper::getCollectionTransactionTitle($d);
                }
            ),
            array( 'dbAlias'=>'payments','db' => 'amount',  'dt' => 9),
            array( 'dbAlias'=>'transaction_error_codes','db' => 'description',  'dt' => 10),
            
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
                ->selectRaw('payments.*,employees.id_number,employees.first_name,employees.last_name,transaction_error_codes.description')
                ->leftJoin('employees', function ($join) {
                    $join->on('payments.employee_id', '=', 'employees.id');
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
                ->leftJoin('employees', function ($join) {
                    $join->on('payments.employee_id', '=', 'employees.id');
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

    

    

    
   

    
}
