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
use App\Model\{Firm,BankDetails,Role,CompanyInformation,Employees,Customer,TempCustomers,PublicHolidays,Batch,Collections};
//use Maatwebsite\Excel\Facades\Excel;
use Response;

class NormalBatchController extends Controller
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
        $pagename  = "Pending Batches";
        return view('merchant.normal-batch.index',compact('pagename'));
        
        
    }

    private function dataTableColumnBindings(){
        $columns = array(
            array( 'db' => 'batch_name', 'dt' => 0,
                'formatter' => function( $d, $row ) {
                    $row['id']=encrypt($row['id']);
                   return $row;
                } ),
            array( 'db' => 'batch_service_type', 'dt' => 1,
                    'formatter' => function( $d, $row ) {
                       return ucfirst($row['batch_service_type']);
                    }
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
                
                'dt'        => 6,
                'formatter' => function( $d, $row ) {
                   return encrypt($row['id']);
                }
            )
        );

        return $columns;
    }


    public function ajaxApprovalList(Request $request){
        
        $firmId = auth()->user()->firm_id;
        
        $bindings=['normal-collection',$firmId,'pending'];
        $whereConditions ="batches.batch_type =? and batches.firm_id=? and batches.batch_status=?";
        echo $this->getBatchData($request,$bindings,$whereConditions);
        
        die();
    }

    public function statusUpdate(Request $request){
        $requestStatus=['status'=>402,'message'=>'Problem in updating the record',"type"=>"danger"];


        if($request->isMethod('post')){
            $batchId=decrypt($request->batchId);
            $statusTitle=$request->action;
            $firmId = auth()->user()->firm_id;
            $collectionBatch = Batch::where('id',$batchId)->where('firm_id',$firmId)->where('batch_status','pending')->first();

            $status='cancelled';
            if($statusTitle=='approve'){
                $statusTitle="approved";
            }elseif($statusTitle=='cancel'){
                $statusTitle="cancelled";
            }
            
            
            
            
            if(!is_null($collectionBatch)){
                $batchAmount=Collections::where(['batch_id' => $batchId])->whereIn('collection_status',[0,1])->groupBy('batch_id')->selectRaw('*, sum(amount) as tot_amount')->first(); 
                
                $collectionBatch->batch_status = $statusTitle;
                if ($collectionBatch->save()) {
                    if($statusTitle=='approved'){
                        $statusValue=1;
                    }else{
                        $statusValue=2;
                    }
                    Collections::where(['batch_id' => $batchId])->whereIn('collection_status',[0,1])->update(['collection_status' => $statusValue]);
                    $requestStatus=['status'=>201,'message'=>'Batch '.$statusTitle.' successfully',"type"=>"success"];
                }
            }
            
        }
        echo json_encode($requestStatus);
        exit();
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
                ->whereRaw($whereConditions, $bindings)
                //->groupBy('collections.batch_id')
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
                ->selectRaw('batches.*,sum(collections.amount) as amount,count(collections.id) as trnx_count')
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
                ->selectRaw('count( distinct batches.'.$primaryKey.') totCount, batches.'.$primaryKey)
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
    public function processedList()
    {     
        $pagename  = "Processed Batches";
        return view('merchant.normal-batch.processed',compact('pagename'));
        
        
    }

    public function ajaxProcessedList(Request $request){
        
        $firmId = auth()->user()->firm_id;
        
        $bindings=['normal-collection',$firmId,'processed',1];
        $whereConditions ="batches.batch_type =? and batches.firm_id=? and batches.batch_status=? and collections.collection_status=?";
        echo $this->getBatchData($request,$bindings,$whereConditions);
    }

    public function submittedList()
    {     
        $pagename  = "Queued to Bank";
        return view('merchant.normal-batch.submitted',compact('pagename'));
        
        
    }

    public function ajaxSubmittedList(Request $request){
       
        $firmId = auth()->user()->firm_id;
        
        $bindings=['normal-collection',$firmId,'approved','sent',1];
        $whereConditions ="batches.batch_type =? and batches.firm_id=? and (batches.batch_status=? or batches.batch_status=?) and collections.collection_status=?";

        echo $this->getBatchData($request,$bindings,$whereConditions);
        die();
    }

    public function approvedList()
    {     
        $pagename  = "Approved Batches";
        return view('merchant.normal-batch.approved',compact('pagename'));
        
        
    }

    public function ajaxApprovedList(Request $request){
       
        $firmId = auth()->user()->firm_id;
        
        $bindings=['normal-collection',$firmId,'approved',1];
        $whereConditions ="batches.batch_type =? and batches.firm_id=? and batches.batch_status=? and collections.collection_status=?";
        echo $this->getBatchData($request,$bindings,$whereConditions);
        die();
    }
}
