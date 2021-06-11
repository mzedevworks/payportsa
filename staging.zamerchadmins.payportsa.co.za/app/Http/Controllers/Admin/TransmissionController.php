<?php

namespace App\Http\Controllers\Admin;

use App\User;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Helpers\DatatableHelper;
use App\Http\Controllers\Controller;
use App\Model\{OutputFileTransaction,OutputFile,TransmissionRecords,PaymentTransmissionRecords,AvsTransmissionRecords,AvsEnquiry};
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Response;
class TransmissionController extends Controller
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

        $pagename = "Collection Files";
        return view('admin.transmission.collection-list',compact('pagename'));
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
                array( 'db' => 'transmission_date','dt' => 0,
                        'formatter' => function( $d, $row ) {
                            return Helper::convertDate($row['transmission_date'],'d-m-Y');
                        } 
                 ),
                array( 'db' => 'file_path','dt' => 1,
                        'formatter' => function( $d, $row ) {
                            $row['id']=encrypt($row['id']);
                            return $row;
                        } 
                    ),
                array( 
                        'db' => 'reply_file',
                        'dt' => 2,
                        'formatter' => function( $d, $row ) {
                            $row['id']=encrypt($row['id']);
                            return $row;
                        } 
                    ),
                array( 
                    'dt' => 3,
                    'formatter' => function( $d, $row ) {
                        $row['id']=encrypt($row['id']);
                       return $row;
                    }  
                ),
                array(
                
                    'dt'        => 4,
                    'formatter' => function( $d, $row ) {
                       return $row['amount'];
                    }
                ),
                array( 
                    'db' => 'combined_status',
                    'dt' => 5,
                    'formatter' => function( $d, $row ) {
                        if(is_null($row['combined_status'])){
                            return '';
                        }else{
                            return $row['combined_status'];
                        }
                        
                    }   
                )
            );
        
        $bindings=[];

        $whereConditions="1=1";
        $totalCount = DB::table('transmission_records')
                ->selectRaw('count(transmission_records.'.$primaryKey.') totCount,sum(collections.amount) as amount,count(collections.id) as trnx_count')
                ->whereRaw($whereConditions, $bindings)
                ->leftJoin('collections', function ($join) {
                    $join->on('collections.transmission_id', '=', 'transmission_records.id');
                })
                ->groupBy('collections.transmission_id')
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

        $orderBy .=' transmission_records.id DESC';

        $limit=DatatableHelper::limit ( $request, $columns );

       
        $data = DB::table('transmission_records')
                ->selectRaw('transmission_records.*,sum(collections.amount) as amount,count(collections.id) as trnx_count')
                ->leftJoin('collections', function ($join) {
                    $join->on('collections.transmission_id', '=', 'transmission_records.id');
                })
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->groupBy('collections.transmission_id')
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
        
        $totalFilteredCount = DB::table('transmission_records')
                ->selectRaw('count(transmission_records.'.$primaryKey.') totCount, transmission_records.'.$primaryKey.',sum(collections.amount) as amount,count(collections.id) as trnx_count')
                ->leftJoin('collections', function ($join) {
                    $join->on('collections.transmission_id', '=', 'transmission_records.id');
                })
                ->whereRaw($whereConditions, $bindings)
                ->groupBy('collections.transmission_id')
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

    public function collectionTranDownload(Request $request){
        $outputFileId=decrypt($request['outputFileId']);
        $outputFileDetail=TransmissionRecords::find($outputFileId);
        if($outputFileDetail){
            $file    = public_path($outputFileDetail->file_path);
            if(file_exists($file)){
                $fileNameParts=explode("/", $outputFileDetail->file_path);
                $fileName=end($fileNameParts);
                return Response::download($file,$fileName);
            }
        }
       

        Session::flash('status','Sorry Your request Can not be processed');
        Session::flash('class','danger');
        return redirect('admin/transmission');
    }

    public function collectionReplyDownload(Request $request){
        $outputFileId=decrypt($request['outputFileId']);
        $outputFileDetail=TransmissionRecords::find($outputFileId);
        
        if($outputFileDetail){
            $file    = public_path($outputFileDetail->reply_file);
            if(file_exists($file)){
                $fileNameParts=explode("/", $outputFileDetail->reply_file);
                $fileName=end($fileNameParts);
                return Response::download($file,$fileName);
            }
        }
       

        Session::flash('status','Sorry Your request Can not be processed');
        Session::flash('class','danger');
        return redirect('admin/transmission');
    }

    public function collectionDetail(Request $request){
        $transmissionId=decrypt($request['transmissionId']);
        $outputFileDetail=TransmissionRecords::find($transmissionId);
        if($outputFileDetail){
            $pagename  = "Transactions List";
            return view('admin.transmission.collection-details',compact('pagename','transmissionId'));
        }else{
            Session::flash('status','Sorry Your request Can not be processed');
            Session::flash('class','danger');
            return redirect('admin/transmission');
        }
    }

    public function ajaxCollectionDetail(Request $request){
        //print_r($request['columns']);
        // Table's primary key
        $primaryKey = 'id';
        $transmissionId   = decrypt($request->transmissionId);
        
        
        $columns = $this->dtColumnForCollectionList();
        
        
        $bindings=[$transmissionId];

        $whereConditions ="collections.transmission_id =?";
        $totalCount = DB::table('collections')
                ->selectRaw('count(collections.'.$primaryKey.') totCount')
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

        $orderBy .=' collections.id DESC';

        $limit   = DatatableHelper::limit ( $request, $columns );

        
        $data = DB::table('collections')
                ->selectRaw('collections.*,firms.trading_as,customers.mandate_id,customers.first_name,customers.last_name,transaction_error_codes.description')
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
                
       
        $totalFilteredCount = DB::table('collections')
                ->selectRaw('count(collections.'.$primaryKey.') totCount, collections.'.$primaryKey)
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

    private function dtColumnForCollectionList(){
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
    public function payment()
    {

        $pagename = "Payment Files";
        return view('admin.transmission.payment-list',compact('pagename'));
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
                array( 'db' => 'transmission_date','dt' => 0,
                        'formatter' => function( $d, $row ) {
                            return Helper::convertDate($row['transmission_date'],'d-m-Y');
                        } 
                 ),
                array( 'db' => 'file_path','dt' => 1,
                        'formatter' => function( $d, $row ) {
                            $row['id']=encrypt($row['id']);
                            return $row;
                        } 
                    ),
                array( 
                        'db' => 'reply_file',
                        'dt' => 2,
                        'formatter' => function( $d, $row ) {
                            $row['id']=encrypt($row['id']);
                            return $row;
                        } 
                    ),
                array( 
                    'dt' => 3,
                    'formatter' => function( $d, $row ) {
                        $row['id']=encrypt($row['id']);
                       return $row;
                    }  
                ),
                array(
                
                    'dt'        => 4,
                    'formatter' => function( $d, $row ) {
                       return $row['amount'];
                    }
                ),
                array( 
                    'db' => 'combined_status',
                    'dt' => 5,
                    'formatter' => function( $d, $row ) {
                        if(is_null($row['combined_status'])){
                            return '';
                        }else{
                            return $row['combined_status'];
                        }
                        
                    }   
                )
            );
        
        $bindings=[];

        $whereConditions="1=1";
        $totalCount = DB::table('payment_transmission_records')
                ->selectRaw('count(payment_transmission_records.'.$primaryKey.') totCount,sum(payments.amount) as amount,count(payments.id) as trnx_count')
                ->leftJoin('payments', function ($join) {
                    $join->on('payments.transmission_id', '=', 'payment_transmission_records.id');
                })
                ->whereRaw($whereConditions, $bindings)
                ->groupBy('payments.transmission_id')
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

        $orderBy .=' payment_transmission_records.id DESC';

        $limit=DatatableHelper::limit ( $request, $columns );

       
        $data = DB::table('payment_transmission_records')
                ->selectRaw('payment_transmission_records.*,sum(payments.amount) as amount,count(payments.id) as trnx_count')
                ->leftJoin('payments', function ($join) {
                    $join->on('payments.transmission_id', '=', 'payment_transmission_records.id');
                })
                ->whereRaw($whereConditions, $bindings)
                ->groupBy('payments.transmission_id')
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
        
        $totalFilteredCount = DB::table('payment_transmission_records')
                ->selectRaw('count(payment_transmission_records.'.$primaryKey.') totCount, payment_transmission_records.'.$primaryKey.',sum(payments.amount) as amount,count(payments.id) as trnx_count')
                ->leftJoin('payments', function ($join) {
                    $join->on('payments.transmission_id', '=', 'payment_transmission_records.id');
                })
                ->whereRaw($whereConditions, $bindings)
                ->groupBy('payments.transmission_id')
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

    public function paymentTranDownload(Request $request){
        $outputFileId=decrypt($request['outputFileId']);
        $outputFileDetail=PaymentTransmissionRecords::find($outputFileId);
        if($outputFileDetail){
            $file    = public_path($outputFileDetail->file_path);
            if(file_exists($file)){
                $fileNameParts=explode("/", $outputFileDetail->file_path);
                $fileName=end($fileNameParts);
                return Response::download($file,$fileName);
            }
        }
       

        Session::flash('status','Sorry Your request Can not be processed');
        Session::flash('class','danger');
        return redirect('admin/transmission');
    }

    public function paymentReplyDownload(Request $request){
        $outputFileId=decrypt($request['outputFileId']);
        $outputFileDetail=PaymentTransmissionRecords::find($outputFileId);
        if($outputFileDetail){
            $file    = public_path($outputFileDetail->reply_file);
            if(file_exists($file)){
                $fileNameParts=explode("/", $outputFileDetail->reply_file);
                $fileName=end($fileNameParts);
                return Response::download($file,$fileName);
            }
        }
       

        Session::flash('status','Sorry Your request Can not be processed');
        Session::flash('class','danger');
        return redirect('admin/transmission');
    }
    

    public function paymentDetail(Request $request){
        $transmissionId=decrypt($request['transmissionId']);
        $outputFileDetail=PaymentTransmissionRecords::find($transmissionId);
        if($outputFileDetail){
            $pagename  = "Transactions List";
            return view('admin.transmission.payment-details',compact('pagename','transmissionId'));
        }else{
            Session::flash('status','Sorry Your request Can not be processed');
            Session::flash('class','danger');
            return redirect('admin/transmission/payment');
        }
    }

    public function ajaxPaymentDetail(Request $request){
        //print_r($request['columns']);
        // Table's primary key
        $primaryKey = 'id';
        $transmissionId   = decrypt($request->transmissionId);
        
        
        $columns = $this->dtColumnForPaymentList();
        
        
        $bindings=[$transmissionId];

        $whereConditions ="payments.transmission_id =?";
        $totalCount = DB::table('payments')
                ->selectRaw('count(payments.'.$primaryKey.') totCount')
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

        $orderBy .=' payments.id DESC';

        $limit   = DatatableHelper::limit ( $request, $columns );

        
        $data = DB::table('payments')
                ->selectRaw('payments.*,firms.trading_as,employees.id_number,employees.first_name,employees.last_name,transaction_error_codes.description')
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
                
       
        $totalFilteredCount = DB::table('payments')
                ->selectRaw('count(payments.'.$primaryKey.') totCount, payments.'.$primaryKey)
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

        $pagename = "Avs Files";
        return view('admin.transmission.avs-list',compact('pagename'));
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
                array( 'db' => 'transmission_date','dt' => 0,
                        'formatter' => function( $d, $row ) {
                            return Helper::convertDate($row['transmission_date'],'d-m-Y');
                        } 
                 ),
                array( 'db' => 'file_path','dt' => 1,
                        'formatter' => function( $d, $row ) {
                            $row['id']=encrypt($row['id']);
                            return $row;
                        } 
                    ),
                array( 
                        'db' => 'reply_file',
                        'dt' => 2,
                        'formatter' => function( $d, $row ) {
                            $row['id']=encrypt($row['id']);
                            return $row;
                        } 
                    ),
                array( 
                    'dt' => 3,
                    'formatter' => function( $d, $row ) {
                        $row['id']=encrypt($row['id']);
                       return $row;
                    }  
                ),
                array( 
                    'db' => 'combined_status',
                    'dt' => 4,
                    'formatter' => function( $d, $row ) {
                        if(is_null($row['combined_status'])){
                            return '';
                        }else{
                            return $row['combined_status'];
                        }
                        
                    }   
                )
            );
        
        $bindings=[];

        $whereConditions="1=1";
        $totalCount = DB::table('avs_transmission_records')
                ->selectRaw('count(avs_transmission_records.'.$primaryKey.') totCount,count(avs_enquiries.id) as trnx_count')
                ->leftJoin('avs_enquiries', function ($join) {
                    $join->on('avs_enquiries.avs_transmission_id', '=', 'avs_transmission_records.id');
                })
                ->whereRaw($whereConditions, $bindings)
                ->groupBy('avs_enquiries.avs_transmission_id')
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

        $orderBy .=' avs_transmission_records.id DESC';

        $limit=DatatableHelper::limit ( $request, $columns );

       
        $data = DB::table('avs_transmission_records')
                ->selectRaw('avs_transmission_records.*,count(avs_enquiries.id) as trnx_count')
                ->leftJoin('avs_enquiries', function ($join) {
                    $join->on('avs_enquiries.avs_transmission_id', '=', 'avs_transmission_records.id');
                })
                ->whereRaw($whereConditions, $bindings)
                ->groupBy('avs_enquiries.avs_transmission_id')
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
        
        $totalFilteredCount = DB::table('avs_transmission_records')
                ->selectRaw('count(avs_transmission_records.'.$primaryKey.') totCount, avs_transmission_records.'.$primaryKey.',count(avs_enquiries.id) as trnx_count')
                ->leftJoin('avs_enquiries', function ($join) {
                    $join->on('avs_enquiries.avs_transmission_id', '=', 'avs_transmission_records.id');
                })
                ->whereRaw($whereConditions, $bindings)
                ->groupBy('avs_enquiries.avs_transmission_id')
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

    public function avsTranDownload(Request $request){
        $outputFileId=decrypt($request['outputFileId']);
        $outputFileDetail=AvsTransmissionRecords::find($outputFileId);
        if($outputFileDetail){
            $file    = public_path($outputFileDetail->file_path);
            if(file_exists($file)){
                $fileNameParts=explode("/", $outputFileDetail->file_path);
                $fileName=end($fileNameParts);
                return Response::download($file,$fileName);
            }
        }
       

        Session::flash('status','Sorry Your request Can not be processed');
        Session::flash('class','danger');
        return redirect('admin/transmission/avs');
    }

    public function avsReplyDownload(Request $request){
        $outputFileId=decrypt($request['outputFileId']);
        $outputFileDetail=AvsTransmissionRecords::find($outputFileId);
        if($outputFileDetail){
            $file    = public_path($outputFileDetail->reply_file);
            if(file_exists($file)){
                $fileNameParts=explode("/", $outputFileDetail->reply_file);
                $fileName=end($fileNameParts);
                return Response::download($file,$fileName);
            }
        }
       

        Session::flash('status','Sorry Your request Can not be processed');
        Session::flash('class','danger');
        return redirect('admin/transmission/avs');
    }

    public function avsDetail(Request $request){
        $transmissionId=decrypt($request['transmissionId']);
        $outputFileDetail=AvsTransmissionRecords::find($transmissionId);
        if($outputFileDetail){
            $pagename  = "Avs List";
            return view('admin.transmission.avs-details',compact('pagename','transmissionId'));
        }else{
            Session::flash('status','Sorry Your request Can not be processed');
            Session::flash('class','danger');
            return redirect('admin/transmission/avs');
        }
    }

    public function ajaxAvsDetail(Request $request){
        //print_r($request['columns']);
        // Table's primary key
        $primaryKey = 'id';
        $transmissionId   = decrypt($request->transmissionId);
        
        
        $columns = $this->avsDtColumns();
        
        
        $bindings=[$transmissionId];

        $whereConditions ="avs_enquiries.avs_transmission_id =?";
        $totalCount = DB::table('avs_enquiries')
                ->selectRaw('count(avs_enquiries.'.$primaryKey.') totCount')
                ->leftJoin('firms', function ($join) {
                    $join->on('firms.id', '=', 'avs_enquiries.firm_id');
                }) 
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

        $orderBy .=' avs_enquiries.id DESC';

        $limit   = DatatableHelper::limit ( $request, $columns );

        
        $data = DB::table('avs_enquiries')
                ->selectRaw('avs_enquiries.*,firms.trading_as')
                ->leftJoin('firms', function ($join) {
                    $join->on('firms.id', '=', 'avs_enquiries.firm_id');
                }) 
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
                
       
        $totalFilteredCount = DB::table('avs_enquiries')
                ->selectRaw('count(avs_enquiries.'.$primaryKey.') totCount, avs_enquiries.'.$primaryKey)
                ->leftJoin('firms', function ($join) {
                    $join->on('firms.id', '=', 'avs_enquiries.firm_id');
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

    private function avsDtColumns(){
        $columns = array(
            array( 'dbAlias'   => 'firms','db' => 'trading_as', 'dt' => 0 ),
            array( 'db' => 'avs_type', 'dt' => 1 ),
            array( 'db' => 'beneficiary_id_number', 'dt' => 2 ),
            array( 'db' => 'beneficiary_initial',  'dt' => 3 ),
            array( 'db' => 'beneficiary_last_name',     'dt' => 4 ),
            array( 'db' => 'bank_name','dt'        => 5),
            array( 'db' => 'branch_code',     'dt' => 6 ),
            array( 'db' => 'bank_account_number',     'dt' => 7 ),
            array( 'db' => 'avs_status',     'dt' => 8 ),
            array( 'db' => 'created_on',
                    'dt' => 9,
                    'formatter' => function( $d, $row ) {
                        return Helper::convertDate($row['created_on'],'d-m-Y');
                    }
                ),
            array(
                
                'dt'        => 10,
                'formatter' => function( $d, $row ) {
                   return encrypt($row['id']);
                }
            )
        );

        return $columns;
    }

    public function showAvsResult(Request $request){
        $pagename  = "AVS Result";
        $avsId   = decrypt($request->avsId);
        

        $avsRecord=AvsEnquiry::where(['id'=>$avsId])->first();
        if(is_null($avsRecord)){
            Session::flash('status','Not a valid Avs record');
            Session::flash('class','danger');
            return redirect('admin/transmission/avs');
        }
        $resultSet=[];
        if(!is_null($avsRecord->avs_json_result)){
            $resultSet=json_decode($avsRecord->avs_json_result,true);
        }
        return view('admin.transmission.avsResults',compact('pagename','avsId','avsRecord','resultSet'));
    }
}
