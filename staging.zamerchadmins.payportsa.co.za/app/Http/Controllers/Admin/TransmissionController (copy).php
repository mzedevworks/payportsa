<?php

namespace App\Http\Controllers\Admin;

use App\User;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Helpers\DatatableHelper;
use App\Http\Controllers\Controller;
use App\Model\{OutputFileTransaction,OutputFile,TransmissionRecords,PaymentTransmissionRecords};
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
                ->selectRaw('count(payment_transmission_records.'.$primaryKey.') totCount')
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

        $orderBy .=' payment_transmission_records.id DESC';

        $limit=DatatableHelper::limit ( $request, $columns );

       
        $data = DB::table('payment_transmission_records')
                ->selectRaw('payment_transmission_records.*')
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
        
        $totalFilteredCount = DB::table('payment_transmission_records')
                ->selectRaw('count(payment_transmission_records.'.$primaryKey.') totCount, payment_transmission_records.'.$primaryKey)
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

    
}
