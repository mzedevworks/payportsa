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

class ReoccurBatchController extends Controller
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
        return view('admin.reoccur-batch.pending',compact('pagename'));
    }

    private function dataTableColumnBindings(){
        $columns = array(
            array( 'dbAlias'=>'batches','db' => 'batch_name', 'dt' => 0,
                'formatter' => function( $d, $row ) {
                    $row['id']=encrypt($row['id']);
                   return $row;
                } ),
            array( 'dbAlias'=>'firms','db' => 'business_name', 'dt' => 1
                ),
            array( 'dbAlias'=>'batches','db' => 'action_date',  'dt' => 2,
                    'formatter' => function( $d, $row ) {
                    return Helper::convertDate($row['action_date'],'d-m-Y');
                    }
             ),
            array( 'dbAlias'=>'batches','db' => 'created_at',  'dt' =>3,
                    'formatter' => function( $d, $row ) {
                    return Helper::convertDate($row['created_at'],'d-m-Y');
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
                'dbAlias'=>'transmission_records',
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
        
        
        
        $bindings=['reocurr-collection','pending'];
        $whereConditions ="batches.batch_type =? and batches.batch_status=?";
        echo $this->getBatchData($request,$bindings,$whereConditions);
        
        die();
    }

    public function getBatchData($request,$bindings,$whereConditions){
        //print_r($request['columns']);
        // Table's primary key
        $primaryKey = 'id';
        
        $columns = $this->dataTableColumnBindings();

        
        $totalCount = DB::table('batches')
                ->selectRaw('count( distinct batches.'.$primaryKey.') totCount')
                ->leftJoin('collections', function ($join) {
                    $join->on('collections.batch_id', '=', 'batches.id');
                })
                ->leftJoin('transmission_records', function ($join) {
                    $join->on('collections.transmission_id', '=', 'transmission_records.id');
                })
                ->leftJoin('firms', function ($join) {
                    $join->on('batches.firm_id', '=', 'firms.id');
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

        $orderBy .=' batches.id DESC';
        $limit   = DatatableHelper::limit ( $request, $columns );

        
        $data = DB::table('batches')
                ->selectRaw('batches.*,sum(collections.amount) as amount,count(collections.id) as trnx_count,firms.business_name,transmission_records.combined_status')
                ->leftJoin('collections', function ($join) {
                    $join->on('collections.batch_id', '=', 'batches.id');
                })
                ->leftJoin('transmission_records', function ($join) {
                    $join->on('collections.transmission_id', '=', 'transmission_records.id');
                })
                ->leftJoin('firms', function ($join) {
                    $join->on('batches.firm_id', '=', 'firms.id');
                })
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->groupBy('collections.batch_id')
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
       
        $totalFilteredCount = DB::table('batches')
                ->selectRaw('count(distinct batches.'.$primaryKey.') totCount,count(collections.id) as trnx_count, batches.'.$primaryKey)
                ->leftJoin('collections', function ($join) {
                    $join->on('collections.batch_id', '=', 'batches.id');
                })
                ->leftJoin('transmission_records', function ($join) {
                    $join->on('collections.transmission_id', '=', 'transmission_records.id');
                })
                ->leftJoin('firms', function ($join) {
                    $join->on('batches.firm_id', '=', 'firms.id');
                })
                ->whereRaw($whereConditions, $bindings)
                ->groupBy('collections.batch_id')
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
        return view('admin.reoccur-batch.processed',compact('pagename'));
    }

    public function ajaxProcessedList(Request $request){
        
        $bindings=['reocurr-collection','processed',1];
        $whereConditions ="batches.batch_type =? and batches.batch_status=? and collections.collection_status=?";
        echo $this->getBatchData($request,$bindings,$whereConditions);
    }

    public function submitted()
    {     
        $pagename  = "Submitted to bank";
        return view('admin.reoccur-batch.submitted',compact('pagename'));
        
        
    }

    public function ajaxSubmittedList(Request $request){
       
        
        $bindings=['reocurr-collection','sent',1];
        $whereConditions ="batches.batch_type =? and batches.batch_status=? and collections.collection_status=?";
        echo $this->getBatchData($request,$bindings,$whereConditions);
        die();
    }
}
