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
use App\Model\{Firm,BankDetails,Role,CompanyInformation,Employees,Customer,TempCustomers,PublicHolidays,Batch};
//use Maatwebsite\Excel\Facades\Excel;
use Response;

class CreditBatchController extends Controller
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
    public function pending()
    {     
        $pagename  = "Pending Batches";
        return view('admin.credit-batch.pending',compact('pagename'));
    }

    private function dataTableColumnBindings(){
        $columns = array(
            array( 'dbAlias'=>'payment_batches','db' => 'batch_name', 'dt' => 0,
                'formatter' => function( $d, $row ) {
                    $row['id']=encrypt($row['id']);
                   return $row;
                } ),
            array( 'dbAlias'=>'firms','db' => 'business_name', 'dt' => 1
                ),
            array( 'dbAlias'=>'payment_batches','db' => 'payment_date',  'dt' => 2,
                    'formatter' => function( $d, $row ) {
                    return Helper::convertDate($row['payment_date'],'d-m-Y');
                    }
             ),
            array( 'dbAlias'=>'payment_batches','db' => 'created_on',  'dt' =>3,
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
                'dbAlias'=>'payment_transmission_records',
                'db' => 'combined_status',
                'dt'        => 6,
                'formatter' => function( $d, $row ) {

                   return ucfirst(strtolower($row['combined_status']??'Pending'));
                }
                
            )
        );

        return $columns;
    }


    public function ajaxPendingList(Request $request){
        
        
        
        $bindings=['credit','pending'];
        $whereConditions ="payment_batches.batch_type =? and payment_batches.batch_status=?";
        echo $this->getBatchData($request,$bindings,$whereConditions);
        
        die();
    }

    public function getBatchData($request,$bindings,$whereConditions){
        //print_r($request['columns']);
        // Table's primary key
        $primaryKey = 'id';
        
        $columns = $this->dataTableColumnBindings();

        
        $totalCount = DB::table('payment_batches')
                ->selectRaw('count( distinct payment_batches.'.$primaryKey.') totCount')
                ->leftJoin('payments', function ($join) {
                    $join->on('payments.batch_id', '=', 'payment_batches.id');
                })
                ->leftJoin('payment_transmission_records', function ($join) {
                    $join->on('payments.transmission_id', '=', 'payment_transmission_records.id');
                })
                ->leftJoin('firms', function ($join) {
                    $join->on('payment_batches.firm_id', '=', 'firms.id');
                })
                ->whereRaw($whereConditions, $bindings)
                //->groupBy('batches.id')
                ->get()
                ->toArray();

        $dtWhere=DatatableHelper::filter ( $request, $columns,$bindings);
        if($dtWhere!==""){
            $whereConditions.=" and ".$dtWhere;
        }
        $orderBy="";
        if(!empty(DatatableHelper::order ( $request, $columns ))){
            $orderBy=DatatableHelper::order ( $request, $columns ).",";
        }

        $orderBy .=' payment_batches.id DESC';
        $limit   = DatatableHelper::limit ( $request, $columns );

        
        $data = DB::table('payment_batches')
                ->selectRaw('payment_batches.*,sum(payments.amount) as amount,count(payments.id) as trnx_count,firms.business_name,payment_transmission_records.combined_status')
                ->leftJoin('payments', function ($join) {
                    $join->on('payments.batch_id', '=', 'payment_batches.id');
                })
                ->leftJoin('payment_transmission_records', function ($join) {
                    $join->on('payments.transmission_id', '=', 'payment_transmission_records.id');
                })
                ->leftJoin('firms', function ($join) {
                    $join->on('payment_batches.firm_id', '=', 'firms.id');
                })
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->groupBy('payments.batch_id')
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
       
        $totalFilteredCount = DB::table('payment_batches')
                ->selectRaw('count(distinct payment_batches.'.$primaryKey.') totCount,count(payments.id) as trnx_count, payment_batches.'.$primaryKey)
                ->leftJoin('payments', function ($join) {
                    $join->on('payments.batch_id', '=', 'payment_batches.id');
                })
                ->leftJoin('payment_transmission_records', function ($join) {
                    $join->on('payments.transmission_id', '=', 'payment_transmission_records.id');
                })
                ->leftJoin('firms', function ($join) {
                    $join->on('payment_batches.firm_id', '=', 'firms.id');
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
                    "draw" => isset ( $request['draw'] ) ?
                        intval( $request['draw'] ) :
                        0,
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
    public function processed()
    {     
        $pagename  = "Processed Batches";
        return view('admin.credit-batch.processed',compact('pagename'));
    }

    public function ajaxProcessedList(Request $request){
        
        $bindings=['credit','processed',1];
        $whereConditions ="payment_batches.batch_type =? and payment_batches.batch_status=? and payments.payment_status=?";
        echo $this->getBatchData($request,$bindings,$whereConditions);
    }

    public function queued()
    {     
        $pagename  = "Queued to bank";
        return view('admin.credit-batch.submitted',compact('pagename'));
        
        
    }

    public function ajaxQueuedList(Request $request){
       
        
        $bindings=['credit','sent','approved',1];
        $whereConditions ="payment_batches.batch_type =? and (payment_batches.batch_status=? or payment_batches.batch_status=?) and payments.payment_status=?";
        echo $this->getBatchData($request,$bindings,$whereConditions);
        die();
    }
}
