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
use App\Model\{Firm,BankDetails,Role,Employees,Customer,Batch,Collections,ProfileLimits};
use Excel;
use Response;
use App\Exports\RecurringCollectionReport;


class NormalCollectionController extends Controller
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
        $pagename  = "Collection For Approval";
        $batchId   = decrypt($request->id);
        $batchCollections=Collections::where('batch_id',$batchId)->first();
        $pagename  = "Collections For {$batchCollections->batch->batch_name}";
        if($batchCollections->batch->batch_status!='pending'){
            return redirect('admin/batch-collection/normal/pending');
        }
        return view('admin.normal-collection.pending-list',compact('pagename','batchId'));
    }

    public function ajaxPendingList(Request $request){
        //print_r($request['columns']);
        // Table's primary key
        $primaryKey = 'id';
        $batchId   = decrypt($request->id);
        
        
        $columns = $this->dtColumnForApprovalList();
        
        
        $bindings=[$batchId];

        $whereConditions ="collections.batch_id =? ";
        $totalCount = DB::table('collections')
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

        
        $data = DB::table('collections')
                ->selectRaw('collections.*,customers.mandate_id,customers.first_name,customers.last_name')
                ->leftJoin('customers', function ($join) {
                    $join->on('collections.customer_id', '=', 'customers.id');
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

    public function queuedList(Request $request)
    {     
        $pagename  = "Collection Queued to Bank";
        $batchId   = decrypt($request->id);

        $batchCollections=Collections::where('batch_id',$batchId)->first();
        $pagename  = "Collections For {$batchCollections->batch->batch_name}";
        if(!in_array($batchCollections->batch->batch_status, ['sent','approved'])){
            return redirect('batch-collection/normal/queued');
        }
        return view('admin.normal-collection.queued-list',compact('pagename','batchId'));
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
            array( 'dbAlias'=>'collections','db' => 'account_holder_name',  'dt' => 3),
            array( 'dbAlias'=>'collections','db' => 'account_number',  'dt' => 4),
            array( 'dbAlias'=>'collections','db' => 'account_type',  'dt' => 5),
            array( 
                    'dbAlias'=>'collections',
                    'db' => 'payment_date',
                    'dt' => 6,
                    'formatter' => function( $d, $row ) {
                        return Helper::convertDate($row['payment_date'],'d-m-Y');
                    }
             ),
            array(
                'dbAlias'=>'collections',
                'number'=>true,
                'db'        => 'collection_status',
                'dt'        => 7,
                'filterfrom'=> function($searchString){
                    return Helper::getDTCompatibleFilterValueForCollectionStatus($searchString);
                    
                },
                'formatter' => function( $d, $row ) {
                    return Helper::getCollectionStatusTitle($d);
                }
            ),
            array( 'dbAlias'=>'collections','db' => 'amount',  'dt' => 8),
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


    
    
    

    public function processedList(Request $request)
    {     
        $batchId   = decrypt($request->id);

        $batchCollections=Collections::where('batch_id',$batchId)->first();
        $pagename  = "Collections For {$batchCollections->batch->batch_name}";
        if($batchCollections->batch->batch_status!='processed'){
            return redirect('admin/batch-collection/normal/processed');
        }
        return view('admin.normal-collection.processed-list',compact('pagename','batchId'));
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
            array( 'dbAlias'=>'collections','db' => 'account_holder_name',  'dt' => 3),
            array( 'dbAlias'=>'collections','db' => 'account_number',  'dt' => 4),
            array( 'dbAlias'=>'collections','db' => 'account_type',  'dt' => 5),
            array( 
                    'dbAlias'=>'collections',
                    'db' => 'payment_date',
                    'dt' => 6,
                    'formatter' => function( $d, $row ) {
                        return Helper::convertDate($row['payment_date'],'d-m-Y');
                    }
             ),
            
            array(
                'dbAlias'=>'collections',
                'number'=>true,
                'db'        => 'transmission_status',
                'dt'        => 7,
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
                'dt'        => 8,
                'filterfrom'=> function($searchString){
                    return Helper::getDTCompatibleFilterValueForCollectionTransactionStatus($searchString);
                    
                },
                'formatter' => function( $d, $row ) {
                    return Helper::getCollectionTransactionTitle($d);
                }
            ),
            array( 'dbAlias'=>'collections','db' => 'amount',  'dt' => 9),
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
        
        
        $bindings=[$batchId,1];

        $whereConditions ="collections.batch_id =? and collections.collection_status=?";
        $totalCount = DB::table('collections')
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

        
        $data = DB::table('collections')
                ->selectRaw('collections.*,customers.mandate_id,customers.first_name,customers.last_name,transaction_error_codes.description')
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
                ->leftJoin('customers', function ($join) {
                    $join->on('collections.customer_id', '=', 'customers.id');
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
