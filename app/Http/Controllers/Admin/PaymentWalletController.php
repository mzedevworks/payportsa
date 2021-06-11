<?php

namespace App\Http\Controllers\Admin;

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
use App\Model\{Firm,UntrackedTopup,PaymentLedgers};

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
    public function nonAllocatedFundList(){
        $payportFirmId=Config('constants.payportFirmId');
        $pagename = "Un-allocated Fund List";
        return view('admin.payment-wallet.unallocated-fund-list',compact('pagename'));
    }

    
    public function ajaxNonAllocatedFundList(Request $request){
        
        // Table's primary key
        $primaryKey = 'id';
         
        // Array of database columns which should be read and sent back to DataTables.
        // The `db` parameter represents the column name in the database, while the `dt`
        // parameter represents the DataTables column identifier. In this case simple
        // indexes
        $columns = array(
            array( 'db' => 'reffrence_number','dt' => 0 ),
            array( 'db' => 'amount','dt' => 1 ),
            array(
                // 'number'=>true,
                'db'        => 'allocation_status',
                'dt'        => 2,
                'filterfrom'=> function($searchString){
                    return Helper::getDTCompatibleFilterValueForUntrackedFundStatus($searchString);
                    
                },
                'formatter' => function( $d, $row ) {
                    return Helper::getUnTrackedStatusTitle($d);
                }
            ),
            array( 
                    'db' => 'created_at',
                    'dt' => 3,
                    'formatter' => function( $d, $row ) {
                        return Helper::convertDate($row['created_at'],'d-m-Y');
                    }
             ),
            array(
                'dt'        => 4,
                'formatter' => function( $d, $row ) {
                   return encrypt($row['id']);
                }
            )

        );
        
        $bindings=['pending'];

        $whereConditions="(untracked_topup.allocation_status=?)";
        $totalCount = DB::table('untracked_topup')
                ->selectRaw('count(untracked_topup.'.$primaryKey.') totCount')
                ->whereRaw($whereConditions, $bindings)
                ->get()
                ->toArray();

        //$whereConditions.= " and users.is_primary=1";

        $dtWhere=DatatableHelper::filter ( $request, $columns,$bindings);
        if($dtWhere!==""){
            $whereConditions.=" and ".$dtWhere;
        }
        //$orderBy=DatatableHelper::order ( $request, $columns );
        $orderBy="id desc";
        $limit=DatatableHelper::limit ( $request, $columns );

       
        $data = DB::table('untracked_topup')
                ->selectRaw('untracked_topup.*')
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
        
        $totalFilteredCount = DB::table('untracked_topup')
                ->selectRaw('count(untracked_topup.'.$primaryKey.') totCount, untracked_topup.'.$primaryKey)
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
    private function isFundable($fundToAllocate){
        if(is_null($fundToAllocate)){
            Session::flash('status','Your request cannot be processed!');
            Session::flash('class','danger');
            return redirect('admin/payment-wallet/non-allocated');
        }
    }
    public function allocatedFund(Request $request,$id){
        $fundId=decrypt($id);

        $payportFirmId=Config('constants.payportFirmId');
        
        $fundToAllocate = UntrackedTopup::where(['allocation_status'=>'pending','id'=>$fundId])->first();
        $this->isFundable($fundToAllocate);
        

        $eligibleFirms=Firm::select('id','business_name')->where(['status'=>1,'is_payment'=>1])->where('id','!=',$payportFirmId)->orderBy("business_name",'asc')->get();

        if($request->isMethod('post')){
            
            $merchantId=$request->merchantId??null;
            $firm=Firm::where(['status'=>1,'is_payment'=>1])->where('id',$merchantId)->first();
            if (is_null($firm)){
                return redirect()->back()->withErrors($validator)->withInput();;
            }

            try {
                DB::beginTransaction();
                PaymentLedgers::lockForUpdate()->get();
                $fundToAllocate = UntrackedTopup::where(['allocation_status'=>'pending','id'=>$fundId])->lockForUpdate()->first();
                $this->isFundable($fundToAllocate);
                $this->accountTopUp($firm->id,$fundToAllocate);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Session::flash('status','Please try again!');
                Session::flash('class','danger');
            }
            return redirect('admin/payment-wallet/non-allocated');
        }
        $pagename = "Allocate Fund";
        
        return view('admin.payment-wallet.allocate',compact('pagename','fundToAllocate','eligibleFirms','fundId'));
    }

    function accountTopUp($firmId,$fundToAllocate){
        $paymentLedger=new PaymentLedgers();

        $paymentLedger->firm_id=$firmId;
        $paymentLedger->transaction_type='refill';

        $paymentLedger->amount=trim($fundToAllocate['amount']);
        $paymentLedger->ledger_desc='Account Topup';
        $paymentLedger->entry_type='cr';
        $paymentLedger->entry_date=date('Y-m-d');

        $lastPaymentLedger=PaymentLedgers::where('firm_id',$firmId)->orderBy("id",'desc')->first();

        $paymentLedger->closing_amount=$lastPaymentLedger->closing_amount+trim($fundToAllocate['amount']);
        $fundToAllocate->allocation_status='allocated';
        $fundToAllocate->allocated_to=$firmId;
        $fundToAllocate->allocated_on=date('Y-m-d H:i:s');
        $fundToAllocate->allocated_by=auth()->user()->id;
        $fundToAllocate->save();
        $paymentLedger->save();
    }

    private function validation($request,$additionalValidation){
            $entryClassvalues=array_keys(Config('constants.entry_class'));

            $validatorArray = [
                                "firm_id"=> [
                                            'required',
                                            'regex:/[0-9]+/',
                                            Rule::exists('firms','id')->where(function ($query) {
                                                return $query->where('is_deleted', 0)->where('status',1)->where('id','!=',1);
                                            })
                                        ],
                                "transmission_type"     => 'required|in:cr,dr' , 
                                "remark"=> 'required|no_special_char'
                            ];
            $validationArr = array_merge($validatorArray,$additionalValidation);
            $validator     = \Validator::make($request,$validationArr ,[
                "no_special_char"=>"Should not have any special character"
            ]);
            return $validator;
    }
}
