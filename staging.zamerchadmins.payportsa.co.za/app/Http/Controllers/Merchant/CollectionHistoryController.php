<?php

namespace App\Http\Controllers\Merchant;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Helpers\DatatableHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use App\Helpers\Helper;
use App\User;
use App\Model\{Batch,CustomerTransaction};

class CollectionHistoryController extends Controller
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

    public function approvedBatchList()
    {
        $pagename = "Batches approved and queued for submission to bank";
        $batches = Batch::where('firm_id',auth()->user()->firm_id)->get();
        return view('merchant.history.collections.approved-list',compact('pagename'));
    }

    /**
     * Display a listing of the customers transaction using ajax.
     *
     * @return \Illuminate\Http\Response
     */
    public function approvedBatchAjaxList(Request $request){
        
        $primaryKey = 'id';      
        $columns = array(
            array( 'dt' => 0, 'db' => 'batch_name'),
            array(
                'dbAlias'   => 'users',
                'db'        => 'first_name',
                'dt'        => 1
            ),
            array( 
                'dt' => 2, 
                'formatter' => function($d,$row){
                    return date('Y-m-d h:i a',strtotime($row['created_at']));
                }
            ),
            array(     
                'dt'        => 3,
                'formatter' => function($d,$row){
                    return encrypt($row['id']);
                } 
            ),
        );

        $firmId = auth()->user()->firm_id;
        
        $bindings=[$firmId];

        $whereConditions ="batches.firm_id=?";
        $totalCount = DB::table('batches')
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

        
        $data = DB::table('batches')
                ->selectRaw('users.first_name,users.last_name as name,batches.*')
                ->leftJoin('users', function ($join) {
                    $join->on('users.id', '=', 'batches.merchant_id');
                })  
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();

        $totalFilteredCount = DB::table('batches')
                ->selectRaw('count(batches.'.$primaryKey.') totCount, batches.'.$primaryKey)
                ->leftJoin('users', function ($join) {
                    $join->on('users.id', '=', 'batches.merchant_id');
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

    public function viewTransactions(Request $request){

        $batch_id     = decrypt($request->id);
        $batch        = Batch::find($batch_id);
        if($batch){
            $pagename = 'Transactions of the batch '.$batch->batch_name;
            $customers = DB::table('customer_transaction')
                            ->select('customers.first_name','customers.last_name', 'customer_transaction.*','customers.id as customer_id','customers.email')
                           ->leftJoin('customers', function ($join) {
                                $join->on('customers.id', '=', 'customer_transaction.customer_id');
                            })
                            ->where('customer_transaction.batch_id',$batch_id)
                            ->get();                
            $batchId = encrypt($request->id);
            return view('merchant.history.collections.transactions-list',compact('pagename','customers'));
        }
    }
}
