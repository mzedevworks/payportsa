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
    public function index()
    {     
        $pagename  = "Batches Queued For Bank";
        return view('merchant.reoccur-batch.index',compact('pagename'));
        
        
    }

    private function dataTableColumnBindings(){
        $columns = array(
            array( 'db' => 'batch_name', 'dt' => 0,
                'formatter' => function( $d, $row ) {
                    $row['id']=encrypt($row['id']);
                   return $row;
                } ),
            array( 'db' => 'action_date',  'dt' => 1,
                    'formatter' => function( $d, $row ) {
                    return Helper::convertDate($row['action_date'],'d-m-Y');
                    }
             ),
            array( 'db' => 'created_at',  'dt' => 2,
                    'formatter' => function( $d, $row ) {
                    return Helper::convertDate($row['created_at'],'d-m-Y');
                    }
             ),
            array(
                
                'dt'        => 3,
                'formatter' => function( $d, $row ) {
                   return $row['amount'];
                }
            ),
            array(
                
                'dt'        => 4,
                'formatter' => function( $d, $row ) {
                   return $row['trnx_count'];
                }
            ),
            array(
                
                'dt'        => 5,
                'formatter' => function( $d, $row ) {
                   return encrypt($row['id']);
                }
            )
        );

        return $columns;
    }


    public function ajaxApprovalList(Request $request){
        
        $firmId = auth()->user()->firm_id;
        
        $bindings=['reocurr-collection',$firmId,'pending'];
        $whereConditions ="batches.batch_type =? and batches.firm_id=? and batches.batch_status=?";
        echo $this->getBatchData($request,$bindings,$whereConditions);
        
        die();
    }

    public function getBatchData($request,$bindings,$whereConditions){
        //print_r($request['columns']);
        // Table's primary key
        $primaryKey = 'id';
        
        $columns = $this->dataTableColumnBindings();

        
        $totalCount = DB::table('batches')
                ->selectRaw('count(batches.'.$primaryKey.') totCount')
                ->leftJoin('collections', function ($join) {
                    $join->on('collections.batch_id', '=', 'batches.id');
                })
                ->whereRaw($whereConditions, $bindings)
                ->groupBy('collections.batch_id')
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
        $orderBy .=' batches.action_date DESC';
        $limit   = DatatableHelper::limit ( $request, $columns );

        
        $data = DB::table('batches')
                ->selectRaw('batches.*,ROUND(sum(collections.amount),2) as amount,count(collections.id) as trnx_count')
                ->leftJoin('collections', function ($join) {
                    $join->on('collections.batch_id', '=', 'batches.id');
                })
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->groupBy('collections.batch_id')
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
       
        $totalFilteredCount = DB::table('batches')
                ->selectRaw('count(batches.'.$primaryKey.') totCount, batches.'.$primaryKey)
                ->leftJoin('collections', function ($join) {
                    $join->on('collections.batch_id', '=', 'batches.id');
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
                    "draw" => isset ( $request['draw'] ) ? intval( $request['draw'] ) :0,
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
    public function processedList()
    {     
        $pagename  = "Processed Batches";
        return view('merchant.reoccur-batch.processed',compact('pagename'));
        
        
    }

    public function ajaxProcessedList(Request $request){
        
        $firmId = auth()->user()->firm_id;
        
        $bindings=['reocurr-collection',$firmId,'processed',1];
        $whereConditions ="batches.batch_type =? and batches.firm_id=? and batches.batch_status=? and collections.collection_status=?";
        echo $this->getBatchData($request,$bindings,$whereConditions);
    }

    public function submittedList()
    {     
        $pagename  = "Submitted to bank";
        return view('merchant.reoccur-batch.submitted',compact('pagename'));
    }

    public function ajaxSubmittedList(Request $request){
       
        $firmId = auth()->user()->firm_id;
        
        $bindings=['reocurr-collection',$firmId,'sent',1];
        $whereConditions ="batches.batch_type =? and batches.firm_id=? and batches.batch_status=? and collections.collection_status=?";
        echo $this->getBatchData($request,$bindings,$whereConditions);
        die();
    }
}
