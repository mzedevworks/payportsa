<?php

namespace App\Http\Controllers\Admin;

use App\User;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Helpers\DatatableHelper;
use App\Http\Controllers\Controller;
use App\Model\{OutputFileTransaction,OutputFile};
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Response;
class OutputController extends Controller
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
    public function collection()
    {

        $pagename = "Collection Output Files";
        return view('admin.outputs.collection-list',compact('pagename'));
    }

    public function ajaxCollectionList(Request $request){
        //print_r($request['columns']);
        // Table's primary key
        $primaryKey = 'id';
         
        // Array of database columns which should be read and sent back to DataTables.
        // The `db` parameter represents the column name in the database, while the `dt`
        // parameter represents the DataTables column identifier. In this case simple
        // indexes
        $columns = array(
                array( 'db' => 'transaction_count','dt' => 0 ),
                array( 'db' => 'transaction_amount','dt' => 1 ),
                array( 
                        'db' => 'receiving_date',
                        'dt' => 2,
                        'formatter' => function( $d, $row ) {
                            return Helper::convertDate($row['receiving_date'],'d-m-Y H:i');
                        } 
                    ),
                array( 
                    'dt' => 3,
                    'formatter' => function( $d, $row ) {
                        return encrypt($row['id']);
                    }   
                )
            );
        
        $bindings=['collection'];

        $whereConditions="output_files.file_type=?";
        $totalCount = DB::table('output_files')
                ->selectRaw('count(output_files.'.$primaryKey.') totCount')
                ->whereRaw($whereConditions, $bindings)
                ->get()
                ->toArray();

        //$whereConditions.= " and users.is_primary=1";

        $dtWhere=DatatableHelper::filter ( $request, $columns,$bindings);
        if($dtWhere!==""){
            $whereConditions.=" and ".$dtWhere;
        }

        $orderBy="";
        if(!empty(DatatableHelper::order ( $request, $columns ))){
            $orderBy=DatatableHelper::order ( $request, $columns ).",";
        }

        $orderBy .=' output_files.id DESC';

        $limit=DatatableHelper::limit ( $request, $columns );

       
        $data = DB::table('output_files')
                ->selectRaw('output_files.*')
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
        
        $totalFilteredCount = DB::table('output_files')
                ->selectRaw('count(output_files.'.$primaryKey.') totCount, output_files.'.$primaryKey)
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

    public function outputDownload(Request $request){
        $outputFileId=decrypt($request['outputFileId']);
        $outputFileDetail=OutputFile::find($outputFileId);
        if($outputFileDetail){
            $file    = public_path($outputFileDetail->output_file_path);
            if(file_exists($file)){
                $fileNameParts=explode("/", $outputFileDetail->output_file_path);
                $fileName=end($fileNameParts);
                return Response::download($file,$fileName);
            }
            
        }
       

        Session::flash('status','Sorry Your request Can not be processed');
        Session::flash('class','danger');
        return redirect('admin/outputs');
    }

    public function payment()
    {

        $pagename = "Payment Output Files";
        return view('admin.outputs.payment-list',compact('pagename'));
    }

    public function ajaxPaymentList(Request $request){
        //print_r($request['columns']);
        // Table's primary key
        $primaryKey = 'id';
         
        // Array of database columns which should be read and sent back to DataTables.
        // The `db` parameter represents the column name in the database, while the `dt`
        // parameter represents the DataTables column identifier. In this case simple
        // indexes
        $columns = array(
                array( 'db' => 'transaction_count','dt' => 0 ),
                array( 'db' => 'transaction_amount','dt' => 1 ),
                array( 
                        'db' => 'receiving_date',
                        'dt' => 2,
                        'formatter' => function( $d, $row ) {
                            return Helper::convertDate($row['receiving_date'],'d-m-Y H:i');
                        } 
                    ),
                array( 
                    'dt' => 3,
                    'formatter' => function( $d, $row ) {
                        return encrypt($row['id']);
                    }   
                )
            );
        
        $bindings=['payment'];

        $whereConditions="output_files.file_type=?";
        $totalCount = DB::table('output_files')
                ->selectRaw('count(output_files.'.$primaryKey.') totCount')
                ->whereRaw($whereConditions, $bindings)
                ->get()
                ->toArray();

        //$whereConditions.= " and users.is_primary=1";

        $dtWhere=DatatableHelper::filter ( $request, $columns,$bindings);
        if($dtWhere!==""){
            $whereConditions.=" and ".$dtWhere;
        }

        $orderBy="";
        if(!empty(DatatableHelper::order ( $request, $columns ))){
            $orderBy=DatatableHelper::order ( $request, $columns ).",";
        }

        $orderBy .=' output_files.id DESC';

        $limit=DatatableHelper::limit ( $request, $columns );

       
        $data = DB::table('output_files')
                ->selectRaw('output_files.*')
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
        
        $totalFilteredCount = DB::table('output_files')
                ->selectRaw('count(output_files.'.$primaryKey.') totCount, output_files.'.$primaryKey)
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

    public function collectionDetail(Request $request){
        $outputFileId=decrypt($request['outputFileId']);
        $outputFileDetail=OutputFile::find($outputFileId);
        if($outputFileDetail){
            $pagename  = "Transactions List";
            return view('admin.outputs.collection-details',compact('pagename','outputFileId'));
        }else{
            Session::flash('status','Sorry Your request Can not be processed');
            Session::flash('class','danger');
            return redirect('admin/outputs/collection');
        }
    }

    public function ajaxCollectionDetail(Request $request){
        //print_r($request['columns']);
        // Table's primary key
        $primaryKey = 'id';
        $outputFileId   = decrypt($request->outputFileId);
        
        
        $columns = $this->dtColumnForProcessedList();
        
        
        $bindings=[$outputFileId];

        $whereConditions ="output_file_transactions.output_file_id =?";
        $totalCount = DB::table('output_file_transactions')
                ->selectRaw('count(output_file_transactions.'.$primaryKey.') totCount')
                ->leftJoin('collections', function ($join) {
                    $join->on('collections.id', '=', 'output_file_transactions.target_transaction_id');
                }) 
                ->leftJoin('firms', function ($join) {
                    $join->on('firms.id', '=', 'collections.firm_id');
                }) 
                ->leftJoin('customers', function ($join) {
                    $join->on('collections.customer_id', '=', 'customers.id');
                }) 
                ->leftJoin('transaction_error_codes', 'transaction_error_codes.id', '=', 'collections.tranx_error_id')
                ->whereRaw($whereConditions, $bindings)
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

        $orderBy .=' output_file_transactions.id DESC';

        $limit   = DatatableHelper::limit ( $request, $columns );

        
        $data = DB::table('output_file_transactions')
                ->selectRaw('collections.*,firms.trading_as,customers.mandate_id,customers.first_name,customers.last_name,transaction_error_codes.description')
                ->leftJoin('collections', function ($join) {
                    $join->on('collections.id', '=', 'output_file_transactions.target_transaction_id');
                }) 
                ->leftJoin('firms', function ($join) {
                    $join->on('firms.id', '=', 'collections.firm_id');
                }) 
                ->leftJoin('customers', function ($join) {
                    $join->on('collections.customer_id', '=', 'customers.id');
                }) 
                ->leftJoin('transaction_error_codes', 'transaction_error_codes.id', '=', 'collections.tranx_error_id')
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
                
       
        $totalFilteredCount = DB::table('output_file_transactions')
                ->selectRaw('count(output_file_transactions.'.$primaryKey.') totCount, output_file_transactions.'.$primaryKey)
                ->leftJoin('collections', function ($join) {
                    $join->on('collections.id', '=', 'output_file_transactions.target_transaction_id');
                })
                ->leftJoin('firms', function ($join) {
                    $join->on('firms.id', '=', 'collections.firm_id');
                }) 
                ->leftJoin('customers', function ($join) {
                    $join->on('collections.customer_id', '=', 'customers.id');
                }) 
                ->leftJoin('transaction_error_codes', 'transaction_error_codes.id', '=', 'collections.tranx_error_id')
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

    private function dtColumnForProcessedList(){
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
                    'dbAlias'   => 'customers',
                    'db'        => 'first_name',
                    'dt' => 2,
                ),
            array( 
                    'dbAlias'   => 'customers',
                    'db'        => 'last_name',
                    'dt' => 3,
                ),
            array( 'dbAlias'=>'collections','db' => 'account_holder_name',  'dt' => 4),
            array( 'dbAlias'=>'collections','db' => 'account_number',  'dt' => 5),
            array( 'dbAlias'=>'collections','db' => 'account_type',  'dt' => 6),
            array( 
                    'dbAlias'=>'collections',
                    'db' => 'payment_date',
                    'dt' => 7,
                    'formatter' => function( $d, $row ) {
                        return Helper::convertDate($row['payment_date'],'d-m-Y');
                    }
             ),
            array( 'dbAlias'=>'collections','db' => 'payment_type',  'dt' => 8),
            
            array(
                'dbAlias'=>'collections',
                'number'=>true,
                'db'        => 'transmission_status',
                'dt'        => 9,
                'filterfrom'=> function($searchString){
                    return Helper::getDTCompatibleFilterValueForCollectionStatus($searchString);
                    
                },
                'formatter' => function( $d, $row ) {
                    return Helper::getCollectionTransmissionTitle($d);
                }
            ),
            array(
                'dbAlias'=>'collections',
                'number'=>true,
                'db'        => 'transaction_status',
                'dt'        => 10,
                'filterfrom'=> function($searchString){
                    return Helper::getDTCompatibleFilterValueForCollectionTransactionStatus($searchString);
                    
                },
                'formatter' => function( $d, $row ) {
                    return Helper::getCollectionTransactionTitle($d);
                }
            ),
            array( 'dbAlias'=>'collections','db' => 'amount',  'dt' => 11),
            array( 'dbAlias'=>'transaction_error_codes','db' => 'description',  'dt' => 12),
            
            
        );

        return $columns;
    }

    public function paymentDetail(Request $request){
        $outputFileId=decrypt($request['outputFileId']);
        $outputFileDetail=OutputFile::find($outputFileId);
        if($outputFileDetail && $outputFileDetail->file_type=='payment'){
            $pagename  = "Transactions List";
            return view('admin.outputs.payment-details',compact('pagename','outputFileId'));
        }else{
            Session::flash('status','Sorry Your request Can not be processed');
            Session::flash('class','danger');
            return redirect('admin/outputs/payment');
        }
    }



    public function ajaxPaymentDetail(Request $request){
        //print_r($request['columns']);
        // Table's primary key
        $primaryKey = 'id';
        $outputFileId   = decrypt($request->outputFileId);
        
        
        $columns = $this->dtColumnForPaymentList();
        
        
        $bindings=[$outputFileId];

        $whereConditions ="output_file_transactions.output_file_id =?";
        $totalCount = DB::table('output_file_transactions')
                ->selectRaw('count(output_file_transactions.'.$primaryKey.') totCount')
                ->leftJoin('payments', function ($join) {
                    $join->on('payments.id', '=', 'output_file_transactions.target_transaction_id');
                }) 
                ->leftJoin('firms', function ($join) {
                    $join->on('firms.id', '=', 'payments.firm_id');
                }) 
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
        
        $orderBy="";
        if(!empty(DatatableHelper::order ( $request, $columns ))){
            $orderBy=DatatableHelper::order ( $request, $columns ).",";
        }

        $orderBy .=' output_file_transactions.id DESC';

        $limit   = DatatableHelper::limit ( $request, $columns );

        
        $data = DB::table('output_file_transactions')
                ->selectRaw('payments.*,firms.trading_as,employees.id_number,employees.first_name,employees.last_name,transaction_error_codes.description')
                ->leftJoin('payments', function ($join) {
                    $join->on('payments.id', '=', 'output_file_transactions.target_transaction_id');
                }) 
                ->leftJoin('firms', function ($join) {
                    $join->on('firms.id', '=', 'payments.firm_id');
                }) 
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
                
       
        $totalFilteredCount = DB::table('output_file_transactions')
                ->selectRaw('count(output_file_transactions.'.$primaryKey.') totCount, output_file_transactions.'.$primaryKey)
                ->leftJoin('payments', function ($join) {
                    $join->on('payments.id', '=', 'output_file_transactions.target_transaction_id');
                })
                ->leftJoin('firms', function ($join) {
                    $join->on('firms.id', '=', 'payments.firm_id');
                }) 
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

    private function dtColumnForPaymentList(){
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
                    'dbAlias'   => 'employees',
                    'db'        => 'first_name',
                    'dt' => 2,
                ),
            array( 
                    'dbAlias'   => 'employees',
                    'db'        => 'last_name',
                    'dt' => 3,
                ),
            array( 'dbAlias'=>'payments','db' => 'account_holder_name',  'dt' => 4),
            array( 'dbAlias'=>'payments','db' => 'account_number',  'dt' => 5),
            array( 'dbAlias'=>'payments','db' => 'account_type',  'dt' => 6),
            array( 
                    'dbAlias'=>'payments',
                    'db' => 'payment_date',
                    'dt' => 7,
                    'formatter' => function( $d, $row ) {
                        return Helper::convertDate($row['payment_date'],'d-m-Y');
                    }
             ),
            
            array(
                'dbAlias'=>'payments',
                'number'=>true,
                'db'        => 'transmission_status',
                'dt'        => 8,
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

    public function avs()
    {

        $pagename = "Avs Output Files";
        return view('admin.outputs.avs-list',compact('pagename'));
    }

    public function ajaxAvsList(Request $request){
        //print_r($request['columns']);
        // Table's primary key
        $primaryKey = 'id';
         
        // Array of database columns which should be read and sent back to DataTables.
        // The `db` parameter represents the column name in the database, while the `dt`
        // parameter represents the DataTables column identifier. In this case simple
        // indexes
        $columns = array(
                
                array( 
                        'db' => 'receiving_date',
                        'dt' => 0,
                        'formatter' => function( $d, $row ) {
                            return Helper::convertDate($row['receiving_date'],'d-m-Y H:i');
                        } 
                    ),
                array( 
                    'dt' => 1,
                    'formatter' => function( $d, $row ) {
                        return encrypt($row['id']);
                    }   
                )
            );
        
        $bindings=['avs'];

        $whereConditions="output_files.file_type=?";
        $totalCount = DB::table('output_files')
                ->selectRaw('count(output_files.'.$primaryKey.') totCount')
                ->whereRaw($whereConditions, $bindings)
                ->get()
                ->toArray();

        //$whereConditions.= " and users.is_primary=1";

        $dtWhere=DatatableHelper::filter ( $request, $columns,$bindings);
        if($dtWhere!==""){
            $whereConditions.=" and ".$dtWhere;
        }

        $orderBy="";
        if(!empty(DatatableHelper::order ( $request, $columns ))){
            $orderBy=DatatableHelper::order ( $request, $columns ).",";
        }

        $orderBy .=' output_files.id DESC';

        $limit=DatatableHelper::limit ( $request, $columns );

       
        $data = DB::table('output_files')
                ->selectRaw('output_files.*')
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
        
        $totalFilteredCount = DB::table('output_files')
                ->selectRaw('count(output_files.'.$primaryKey.') totCount, output_files.'.$primaryKey)
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
