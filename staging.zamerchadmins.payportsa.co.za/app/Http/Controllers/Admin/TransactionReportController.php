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
use App\Model\{Batch,Collections,ChangeTracker,Firm,AvsEnquiry};
use Excel;
use Response;
use App\Exports\RecurringCollectionReport;


class TransactionReportController extends Controller
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

    function collection(Request $request){
        $pagename  = "Collection Report";
        //$firmId = auth()->user()->firm_id;
        $bindings=[2];

        //$whereConditions ="customers.cust_type='normal' and collections.transmission_status=?";
        $whereConditions ="collections.transmission_status=?";

        

        $firms = Firm::where('status',1)->get();

        if(sizeof($request->query())>0){
            return view('admin.transaction-report.collection-report',compact('pagename','request','firms'));
        }else{
            return view('admin.transaction-report.collection-template',compact('pagename','request','firms'));
        }
        
    }

    private function dtColumnForCollectionReport(){
        $columns = array(
             array( 
                    'dbAlias'   => 'customers',
                    'db'        => 'mandate_id',
                    'dt' => 0,
                ),
            array( 
                    'dbAlias'   => 'firms',
                    'db'        => 'trading_as',
                    'dt' => 1,
                ),
            array( 
                    'dbAlias'   => 'collections',
                    'db'        => 'account_number',
                    'dt' => 2,
                ),
            array( 
                    'dbAlias'=>'collections',
                    'db' => 'payment_date',
                    'dt' => 3,
                    'formatter' => function( $d, $row ) {
                        return Helper::convertDate($row['payment_date'],'d-m-Y');
                    }
             ),
            array( 
                    'dbAlias'   => 'collections',
                    'db'        => 'reffrence',
                    'dt' => 4,
                    'formatter' => function( $d, $row ) {
                        return $row;
                    }
                ),
            array( 'dbAlias'=>'customers','db' => 'cust_type',  'dt' => 5,
                    'formatter' => function( $d, $row ) {

                        if($row['cust_type']=='reoccur'){
                            return 'Recurring';
                        }elseif($row['cust_type']=='normal'){
                            return 'Standard';
                        }else{
                            return $row['cust_type'];
                        }
                    }
                ),
            array( 'dbAlias'=>'collections','db' => 'service_type',  'dt' => 6),
            array( 'dbAlias'=>'collections','db' => 'amount',  'dt' => 7),
            array(
                'dbAlias'=>'collections',
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
            array( 
                    'dbAlias'=>'collections',
                    'db' => 'date_of_failure',
                    'dt' => 9,
                    'formatter' => function( $d, $row ) {
                        return Helper::convertDate($row['date_of_failure'],'d-m-Y');
                    }
             ),
            
        );

        return $columns;
    }

    function ajaxCollection(Request $request){
        $primaryKey = 'id';
        
        
        
        $columns = $this->dtColumnForCollectionReport();
        
        //$firmId = auth()->user()->firm_id;
        
        $bindings=[2];

        //$whereConditions ="customers.cust_type='normal' and collections.transmission_status=?";
        $whereConditions ="collections.transmission_status=?";
        if(isset($request->firmid) && !empty($request->firmid)){
            $bindings[]=$request->firmid;
            $whereConditions.=" and collections.firm_id=?";
        }

        if(isset($request->startat) && !empty($request->startat)){
            
            $bindings[]=$request->startat;
            $whereConditions.=" and collections.payment_date>=?";
        }

        if(isset($request->upto) && !empty($request->upto)){
            
            $bindings[]=$request->upto;
            $whereConditions.=" and collections.payment_date<=?";
        }
        if(isset($request->mandate_id) && !empty($request->mandate_id)){
            $bindings[]=$request->mandate_id;
            $whereConditions.=" and customers.mandate_id=?";
        }

        if(isset($request->refrence) && !empty($request->refrence)){
            $bindings[]=$request->refrence;
            $whereConditions.=" and collections.reffrence =?";

        }

        if(isset($request->product) && !empty($request->product)){
            $bindings[]=$request->product;
            $whereConditions.=" and customers.cust_type =?";
        }

        if(isset($request->serviceType) && !empty($request->serviceType)){
            $bindings[]=$request->serviceType;
            $whereConditions.=" and collections.service_type =?";
        }

        if(isset($request->amount) && !empty($request->amount)){
            $bindings[]=$request->amount;
            $whereConditions.=" and collections.amount =?";
        }

        if($request->status>=0){
            $bindings[]=$request->status;
            $whereConditions.=" and collections.transaction_status =?";
        }
        
        

        $totalCount = DB::table('collections')
                ->selectRaw('count(collections.'.$primaryKey.') totCount')
                ->leftJoin('customers', function ($join) {
                    $join->on('collections.customer_id', '=', 'customers.id');
                })
                ->leftJoin('firms', function ($join) {
                    $join->on('collections.firm_id', '=', 'firms.id');
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

        
        $data = DB::table('collections')
                ->selectRaw('collections.*,customers.first_name,customers.last_name,customers.mandate_id,customers.cust_type,firms.trading_as')
                ->leftJoin('customers', function ($join) {
                    $join->on('collections.customer_id', '=', 'customers.id');
                    })
                ->leftJoin('firms', function ($join) {
                    $join->on('collections.firm_id', '=', 'firms.id');
                }) 
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
       
        $totalFilteredCount = DB::table('collections')
                ->selectRaw('count(collections.'.$primaryKey.') totCount, collections.'.$primaryKey)
                ->leftJoin('customers', function ($join) {
                    $join->on('collections.customer_id', '=', 'customers.id');
                })
                ->leftJoin('firms', function ($join) {
                    $join->on('collections.firm_id', '=', 'firms.id');
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

    function payment(Request $request){
        $pagename  = "Payment Reports";
        //$firmId = auth()->user()->firm_id;
        $bindings=[2];

        //$whereConditions ="customers.cust_type='normal' and collections.transmission_status=?";
        $whereConditions ="collections.transmission_status=?";

        

        $firms = Firm::where('status',1)->get();

        if(sizeof($request->query())>0){
            return view('admin.transaction-report.payment-report',compact('pagename','request','firms'));
        }else{
            return view('admin.transaction-report.payment-template',compact('pagename','request','firms'));
        }
        
    }

    private function dtColumnForPaymentReport(){
        $columns = array(
             array( 
                    'dbAlias'   => 'employees',
                    'db'        => 'id_number',
                    'dt' => 0,
                ),
            array( 
                    'dbAlias'   => 'firms',
                    'db'        => 'trading_as',
                    'dt' => 1,
                ),
            array( 
                    'dbAlias'   => 'payments',
                    'db'        => 'account_number',
                    'dt' => 2,
                ),
            array( 
                    'dbAlias'=>'payments',
                    'db' => 'payment_date',
                    'dt' => 3,
                    'formatter' => function( $d, $row ) {
                        return Helper::convertDate($row['payment_date'],'d-m-Y');
                    }
             ),
            array( 
                    'dbAlias'   => 'payments',
                    'db'        => 'reffrence',
                    'dt' => 4,
                    'formatter' => function( $d, $row ) {
                        return $row;
                    }
                ),
            array( 'dbAlias'=>'employees','db' => 'employee_type',  'dt' => 5,
                    'formatter' => function( $d, $row ) {
                        return ucfirst($row['employee_type']);
                    }
                ),
            array( 'dbAlias'=>'payments','db' => 'service_type',  'dt' => 6),
            array( 'dbAlias'=>'payments','db' => 'amount',  'dt' => 7),
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
            array( 
                    'dbAlias'=>'payments',
                    'db' => 'date_of_failure',
                    'dt' => 9,
                    'formatter' => function( $d, $row ) {
                        return Helper::convertDate($row['date_of_failure'],'d-m-Y');
                    }
             ),
            
        );

        return $columns;
    }

    function ajaxPayment(Request $request){
        $primaryKey = 'id';
        
        
        
        $columns = $this->dtColumnForPaymentReport();
        
        //$firmId = auth()->user()->firm_id;
        
        $bindings=[2];

        //$whereConditions ="customers.cust_type='normal' and payments.transmission_status=?";
        $whereConditions ="payments.transmission_status=?";
        if(isset($request->firmid) && !empty($request->firmid)){
            $bindings[]=$request->firmid;
            $whereConditions.=" and payments.firm_id=?";
        }

        if(isset($request->startat) && !empty($request->startat)){
            
            $bindings[]=$request->startat;
            $whereConditions.=" and payments.payment_date>=?";
        }

        if(isset($request->upto) && !empty($request->upto)){
            
            $bindings[]=$request->upto;
            $whereConditions.=" and payments.payment_date<=?";
        }
        if(isset($request->employee_id) && !empty($request->employee_id)){
            $bindings[]=$request->employee_id;
            $whereConditions.=" and employees.id_number=?";
        }

        if(isset($request->refrence) && !empty($request->refrence)){
            $bindings[]=$request->refrence;
            $whereConditions.=" and payments.reffrence =?";

        }

        if(isset($request->product) && !empty($request->product)){
            $bindings[]=$request->product;
            $whereConditions.=" and employees.employee_type =?";
        }

        if(isset($request->serviceType) && !empty($request->serviceType)){
            $bindings[]=$request->serviceType;
            $whereConditions.=" and payments.service_type =?";

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
                ->leftJoin('employees', function ($join) {
                    $join->on('payments.employee_id', '=', 'employees.id');
                })
                ->leftJoin('firms', function ($join) {
                    $join->on('payments.firm_id', '=', 'firms.id');
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
                ->selectRaw('payments.*,employees.first_name,employees.last_name,employees.id_number,employees.employee_type,firms.trading_as')
                ->leftJoin('employees', function ($join) {
                    $join->on('payments.employee_id', '=', 'employees.id');
                    })
                ->leftJoin('firms', function ($join) {
                    $join->on('payments.firm_id', '=', 'firms.id');
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
                ->leftJoin('firms', function ($join) {
                    $join->on('payments.firm_id', '=', 'firms.id');
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

    function ajaxLogList(Request $request){
        $transactionType=$request->trxType;
        $targetId=$request->id;
        $transRecords=ChangeTracker::where(['change_type'=>$transactionType,'target_id'=>$targetId])->orderBy('created_at','asc')->get();
        return view('admin.transaction-report.log-list',compact('transRecords','transactionType','targetId'));
    }

    function avs(Request $request){
        $pagename  = "Avs Reports";

        $firms = Firm::where('status',1)->get();

        if(sizeof($request->query())>0){
            return view('admin.transaction-report.avs-report',compact('pagename','request','firms'));
        }else{
            return view('admin.transaction-report.avs-template',compact('pagename','request','firms'));
        }
        
    }
    private function avsDtColumns(){
        $columns = array(
            array( 'db' => 'avs_type', 'dt' => 0 ),
            array( 'db' => 'beneficiary_id_number', 'dt' => 1 ),
            array( 'db' => 'beneficiary_initial',  'dt' => 2 ),
            array( 'db' => 'beneficiary_last_name',     'dt' => 3 ),
            array( 'db' => 'bank_name','dt'        => 4),
            array( 'db' => 'branch_code',     'dt' => 5 ),
            array( 'db' => 'bank_account_number',     'dt' => 6 ),
            array( 'db' => 'avs_status',     'dt' => 7 ),
            array( 'db' => 'created_on',
                    'dt' => 8,
                    'formatter' => function( $d, $row ) {
                        return Helper::convertDate($row['created_on'],'d-m-Y');
                    }
                ),
            array(
                
                'dt'        => 9,
                'formatter' => function( $d, $row ) {
                   $row['id']= encrypt($row['id']);
                   return $row;
                }
            )
        );

        return $columns;
    }


    public function ajaxAvs(Request $request){
        //print_r($request['columns']);
        // Table's primary key
        $primaryKey = 'id';
        
        $columns = $this->avsDtColumns();
        
        $bindings=[];

        //$whereConditions ="customers.cust_type='normal' and payments.transmission_status=?";
        $whereConditions =" 1=1 ";
        if(isset($request->firmid) && !empty($request->firmid)){
            $bindings[]=$request->firmid;
            $whereConditions.=" and avs_enquiries.firm_id=?";
        }

        if(isset($request->startat) && !empty($request->startat)){
            
            $bindings[]=$request->startat ." 00:00:00";
            $whereConditions.=" and avs_enquiries.created_on>=?";
        }

        if(isset($request->upto) && !empty($request->upto)){
            
            $bindings[]=$request->upto." 23:59:59";
            $whereConditions.=" and avs_enquiries.created_on<=?";
        }
        
        if(isset($request->acc_num) && !empty($request->acc_num)){
            $bindings[]=$request->acc_num;
            $whereConditions.=" and avs_enquiries.bank_account_number=?";
        }

        if(isset($request->serviceType) && !empty($request->serviceType)){
            $bindings[]=$request->serviceType;
            $whereConditions.=" and avs_enquiries.avs_type =?";
        }

        if(isset($request->f_name) && !empty($request->f_name)){
            $bindings[]=$request->f_name;
            $whereConditions.=" and avs_enquiries.beneficiary_initial =?";
        }

        if(isset($request->l_name) && !empty($request->l_name)){
            $bindings[]=$request->l_name;
            $whereConditions.=" and avs_enquiries.l_name =?";
        }

        

        $totalCount = DB::table('avs_enquiries')
                ->selectRaw('count('.$primaryKey.') totCount')
                ->whereRaw($whereConditions, $bindings)
                ->get()
                ->toArray();

        $dtWhere=DatatableHelper::filter ( $request, $columns,$bindings);                                                   
        if($dtWhere!==""){
            $whereConditions.=" and ".$dtWhere;
        }
        $orderBy ='avs_enquiries.created_on DESC, '. DatatableHelper::order ( $request, $columns );
        $limit   = DatatableHelper::limit ( $request, $columns );

        
        $data = DB::table('avs_enquiries')
                ->selectRaw('avs_enquiries.*')
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
       
        $totalFilteredCount = DB::table('avs_enquiries')
                ->selectRaw('count(avs_enquiries.'.$primaryKey.') totCount, avs_enquiries.'.$primaryKey)
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


    function ajaxAvsDetail(Request $request){
        $avsId   = decrypt($request->id);

        $avsRecord=AvsEnquiry::where(['id'=>$avsId])->first();
        if(is_null($avsRecord)){
            Session::flash('status','Not a valid Avs record');
            Session::flash('class','danger');
            return redirect('admin/tranx-report/avs');
        }
        $resultSet=[];
        if(!is_null($avsRecord->avs_json_result)){
            $resultSet=json_decode($avsRecord->avs_json_result,true);
        }
        
        return view('admin.transaction-report.avs-result',compact('avsId','avsRecord','resultSet'));
    }
}
