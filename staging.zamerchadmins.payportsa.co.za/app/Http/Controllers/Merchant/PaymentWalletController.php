<?php

namespace App\Http\Controllers\Merchant;

use App\User;
use App\Model\Role;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Hash;
use App\Helpers\Helper;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Helpers\DatatableHelper;
use Illuminate\Support\Facades\DB;
use App\Model\{Firm,PaymentLedgers,PaymentBatches};

class PaymentWalletController extends Controller
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
    public function profilestats(Request $request){
        
        $pagename = "Payment Statement";
        $firmId=auth()->user()->firm_id;
        
        $availableFund=PaymentLedgers::where('firm_id',$firmId)->orderBy("id",'desc')->first();
        $dateUpto=date('Y-m-d');
        $dateFrom=date('Y-m-d',strtotime("-30 days",strtotime($dateUpto)));
        
        if($request->isMethod('post')){

            if($request->payment_to=='' || is_null($request->payment_to)){
                $dateUpto=date('Y-m-d');
            }else{
                $dateUpto=Helper::convertDate($request->payment_to,"Y-m-d");
            }

            
            if($request->payment_from=='' || is_null($request->payment_from)){
                $dateFrom=date('Y-m-d',strtotime("-30 days",strtotime($dateUpto)));
            }else{
                $dateFrom=Helper::convertDate($request->payment_from,"Y-m-d");
            }

            
            
        }


        $firstRecord=PaymentLedgers::where(['firm_id'=>$firmId])->where('entry_date','<=',$dateUpto)->orderBy("entry_date",'desc')->first();
        $paymentStatement=PaymentLedgers::where('firm_id',$firmId)->where('entry_date','>=',$dateFrom)->where('entry_date','<=',$dateUpto)->orderBy("entry_date",'desc')->get();

        return view('merchant.payment-wallet.profilestats',compact('pagename','availableFund','firstRecord','paymentStatement','dateFrom','dateUpto','request'));
    }


    public function batchTranx(Request $request){
        $pagename  = "Transactions in batch";
        $batchId   = decrypt($request->id);
        $firmId=Auth()->user()->firm_id;

        $batch=PaymentBatches::where(['id'=>$batchId,'firm_id'=>$firmId])->first();
        if(is_null($batch)){
            return redirect('merchant/wallet/payment');
        }
        
        return view('merchant.payment-wallet.payment-list',compact('pagename','batchId','batch'));
    }


    public function ajaxBatchTranxList(Request $request){
        $primaryKey = 'id';
        $batchId   = decrypt($request->id);
        
        
        $columns = $this->dtColumnForQueuedList();
        
        $firmId = auth()->user()->firm_id;
        
        $bindings=[$batchId,$firmId];

        $whereConditions ="payments.batch_id =? and payments.firm_id=?";
        $totalCount = DB::table('payments')
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

        
        $data = DB::table('payments')
                ->selectRaw('payments.*,employees.id_number,employees.first_name,employees.last_name')
                ->leftJoin('employees', function ($join) {
                    $join->on('payments.employee_id', '=', 'employees.id');
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

    private function dtColumnForQueuedList(){
        $columns = array(
            array( 
                    'dbAlias'   => 'employees',
                    'db'        => 'id_number',
                    'dt' => 0,
                ),
            array( 
                    'dbAlias'   => 'employees',
                    'db'        => 'first_name',
                    'dt' => 1,
                ),
            array( 
                    'dbAlias'   => 'employees',
                    'db'        => 'last_name',
                    'dt' => 2,
                ),
            array( 'dbAlias'=>'payments','db' => 'account_holder_name',  'dt' => 3),
            array( 'dbAlias'=>'payments','db' => 'account_number',  'dt' => 4),
            array( 'dbAlias'=>'payments','db' => 'account_type',  'dt' => 5),
            array( 
                    'dbAlias'=>'payments',
                    'db' => 'payment_date',
                    'dt' => 6,
                    'formatter' => function( $d, $row ) {
                        return Helper::convertDate($row['payment_date'],'d-m-Y');
                    }
             ),
            
            array(
                'dbAlias'=>'payments',
                'number'=>true,
                'db'        => 'payment_status',
                'dt'        => 7,
                'filterfrom'=> function($searchString){
                    return Helper::getDTCompatibleFilterValueForCollectionStatus($searchString);
                    
                },
                'formatter' => function( $d, $row ) {
                    return Helper::getCollectionStatusTitle($d);
                }
            ),
            array( 'dbAlias'=>'payments','db' => 'amount',  'dt' => 8)
            
        );

        return $columns;
    }
}
