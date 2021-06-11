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
use App\Model\{Firm,BankDetails,Role,CompanyInformation,Employees,Customer,TempCustomers,CustomerTransaction,Batch};
//use Maatwebsite\Excel\Facades\Excel;
use Response;

class CustomerTransationController extends Controller
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
        $pagename  = "Update And Approve List";
        return view('merchant.customer-transactions.customer-transaction-list',compact('pagename'));
    }

    /**
     * Display a listing of the customers transaction using ajax.
     *
     * @return \Illuminate\Http\Response
     */
    public function ajaxList(Request $request){
        $primaryKey = 'id';      
        $columns = array(
            array( 'dt' => 0,
                'formatter' => function( $d, $row ) {
                    //return encrypt($row['id']);
                    return $row['id'];
                }
            ),
            array(
                'dbAlias'   => 'customers',
                'db'        => 'reference',
                'dt'        => 1
            ),
            array(
                'dbAlias'   => 'customers',
                'db'        => 'first_name',
                'dt'        => 2
            ),
            array(
                'dbAlias'   => 'customers',
                'db'        => 'last_name',
                'dt'        => 3
            ),
            array(
                'dbAlias'   => 'customers',
                'db'        => 'account_number',
                'dt'        => 4
            ),
            array(
                'dbAlias'   => 'customers',
                'db'        => 'branch_code',
                'dt'        => 5
            ),
            array( 'db' => 'payment_date',     'dt' => 6 ),
            array( 
                'dt' => 7,
                'formatter' => function($d,$row){
                    $str = '<a href="#" data-type="text" data-pk="1" data-name="recurring_amount" class="editable editable-click editable-open">'.$row['recurring_amount'].'</a>';
                    return $str;
                } 
            ),
            array(     
                'dt'        => 8,
                'formatter' => function($d,$row){
                    $str = '<a href="#" data-type="text" data-pk="1" data-name="once_off_amount" class="editable editable-click editable-open">'.$row['once_off_amount'].'</a>';
                    return $str;
                } 
            ),
            array(
                'dbAlias'   => 'customers',
                'db'        => 'service_type',
                'dt'        => 9
            ),
            array(
                
                'dt'        => 10,
                'formatter' => function( $d, $row ) {
                   return encrypt($row['id']);
                }
            ),
        );

        $firmId = auth()->user()->firm_id;
        
        $bindings=[$firmId,0];

        $whereConditions = "customer_transaction.firm_id=? and customer_transaction.status=?";
        $totalCount = DB::table('customer_transaction')
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

        
        $data = DB::table('customer_transaction')
                ->selectRaw('customers.first_name,customers.last_name,customers.reference,customers.service_type,customers.account_number,customers.branch_code,customer_transaction.*')
                ->leftJoin('customers', function ($join) {
                    $join->on('customers.id', '=', 'customer_transaction.customer_id');
                })  
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();

        $totalFilteredCount = DB::table('customer_transaction')
                ->selectRaw('count(customer_transaction.'.$primaryKey.') totCount, customer_transaction.'.$primaryKey)
                ->leftJoin('customers', function ($join) {
                    $join->on('customers.id', '=', 'customer_transaction.customer_id');
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
    
     /**
     * Approve the transaction also create batch for approved tranasction
     *
     * @return \Illuminate\Http\Response
     */
    public function approveMultiple(Request $request){

        if($request->isMethod('post')){
            
            $i=0;
            foreach ($request->toUpdate as $key => $eachUser) {
                         
                $trans = CustomerTransaction::where(['id' => $eachUser ,'firm_id' => auth()->user()->firm_id])->first();
                if($trans){
                    $trans->status   = 1;
                    $trans->approved_by = auth()->user()->id;
                    if ($trans->save()) {
                        $i++;
                    }    
                }
            }
            Session::flash('status',$i.' Transaction Approved Successfully');
            Session::flash('class','success');
            return redirect('merchant/customers/transaction');
        }
    }

    /**
     * Approve all transactions of the firm also create batch for approved tranasction
     *
     * @return \Illuminate\Http\Response
     */
    public function approveAll(Request $request){

        if($request->isMethod('post')){
            
            $batch  = new Batch();
            $batch->batch_name  = date('Y-m-d').'_'.rand();
            $batch->type        = "collection";
            $batch->merchant_id = auth()->user()->id;
            $batch->firm_id     = auth()->user()->firm_id;
            $batch->save();
            $transactions = CustomerTransaction::where(['firm_id' => auth()->user()->firm_id , 'status' =>0])->get();
            if(count($transactions)>0){
                foreach ($transactions as $transaction) {
                        $transaction->status      = 1;
                        $transaction->approved_by = auth()->user()->id;
                        $transaction->batch_id    = $batch->id;
                        $transaction->save();
                }
                Session::flash('status','All Transaction Approved Successfully a batch is created for the transaction');
                Session::flash('class','success');
            }else{
                Session::flash('status','There is no transactions in the list');
                Session::flash('class','danger');
            }
            return redirect('merchant/customers/transaction');
        }
    }

     /**
     * Update recurring amount and onceoff amount
     *
     * @return \Illuminate\Http\Response
     */
    public function updateAmount(Request $request){
        
        $validator = \Validator::make($request->all(), [
            'once_off_amount'  => 'numeric',
            'recurring_amount' => 'numeric'
        ]);
        if ($validator->fails()){
            $requestStatus=['status'=>402,'message'=>'Some validation fails',"type"=>"danger"];
        }else{

            $id       = decrypt($request->id);
            $customer_trans = CustomerTransaction::where(['id' => $id,'firm_id' => auth()->user()->firm_id])->first();
            $customer_trans->once_off_amount  = $request->once_off_amount;
            $customer_trans->recurring_amount = $request->recurring_amount;
            $customer_trans->save();
            $errors = array();
            $requestStatus=['status'=>201,'message'=>
            'Thanku Update Done Successfully onceoff amount is '.$customer_trans->once_off_amount.' and recurring amount is '.$customer_trans->recurring_amount,"type"=>"success"];
        }
        echo json_encode($requestStatus);
    }

}
