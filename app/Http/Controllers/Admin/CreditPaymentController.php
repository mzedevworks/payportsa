<?php

namespace App\Http\Controllers\Admin;

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

        $batchPayments=Payments::where('batch_id',$batchId)->first();
        if($batchPayments->batch->batch_status!='pending'){
            return redirect('admin/batch-payment/credit/pending');
        }
        return view('admin.credit-payment.pending-list',compact('pagename','batchId'));
    }

    private function dtColumnForApprovalList(){
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
            array( 'dbAlias'=>'payments','db' => 'service_type', 'dt' => 7),
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


    


    public function processedList(Request $request)
    {     
        $pagename  = "Payment Processed";
        $batchId   = decrypt($request->id);

        $batchPayments=Payments::where('batch_id',$batchId)->first();
        if($batchPayments->batch->batch_status!='processed'){
            return redirect('admin/batch-payment/credit/processed');
        }
        return view('admin.credit-payment.processed-list',compact('pagename','batchId'));
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
            array( 'dbAlias'=>'payments','db' => 'service_type',  'dt' => 7),
            
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
            
        );

        return $columns;
    }


    public function ajaxProcessedList(Request $request){
        //print_r($request['columns']);
        // Table's primary key
        $primaryKey = 'id';
        $batchId   = decrypt($request->id);
        
        
        $columns = $this->dtColumnForProcessedList();
        
        
        $bindings=[$batchId];

        $whereConditions ="payments.batch_id =? ";
        $totalCount = DB::table('payments')
                ->selectRaw('count(payments.'.$primaryKey.') totCount')
                ->leftJoin('employees', function ($join) {
                    $join->on('payments.employee_id', '=', 'employees.id');
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
        $pagename  = "Payment Sent to Bank";
        $batchId   = decrypt($request->id);
        

        $batchPayments=Payments::where('batch_id',$batchId)->first();

        if(!in_array($batchPayments->batch->batch_status, ['sent','approved'])){
            return redirect('admin/batch-payment/credit/queued');
        }
        return view('admin.credit-payment.submitted-list',compact('pagename','batchId'));
    }

    
}
