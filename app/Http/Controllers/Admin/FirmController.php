<?php

namespace App\Http\Controllers\Admin;

use App\User;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Helpers\DatatableHelper;
use App\Http\Controllers\Controller;
use App\Model\{Ledgers,Firm,BankDetails,CompanyInformation,ProfileLimits,Rates,PublicHolidays,ProfileTransactions,PaymentLedgers,PaymentBatches};
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class FirmController extends Controller
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

        $pagename = "Merchants";
        return view('admin.firms.list',compact('pagename'));
    }

    public function ajaxFirmsList(Request $request){
        //print_r($request['columns']);
        // Table's primary key
        $primaryKey = 'id';
         
        // Array of database columns which should be read and sent back to DataTables.
        // The `db` parameter represents the column name in the database, while the `dt`
        // parameter represents the DataTables column identifier. In this case simple
        // indexes
        $columns = array(
            // array( 'dt' => 0,
            //     'formatter' => function( $d, $row ) {
            //         //return encrypt($row['id']);
            //         return $row['id'];
            //     }
            // ),
            array( 'db' => 'trading_as','dt' => 0 ),
            array( 'db' => 'business_name','dt' => 1 ),
            array( 'db' => 'payment_reff_number','dt' => 2 ),
            array( 'dt' => 3,
                    'dbAlias'=>'users',
                    'db' => 'first_name'
                ),
            array( 'dt' => 4,
                    'dbAlias'=>'users',
                    'db' => 'last_name'
                ),
            array( 'dt' => 5,
                    'db' => 'monthly_limit'
                ),
            // array(
            //     'dt'        => 5,
            //     'formatter' => function( $d, $row ) {
            //         $str = '<a href="'.url('admin/firms/update/rates/'.encrypt($row['id'])).'">View/Edit</a>';
            //        return $str;
            //     }
            // ),
            array(
                'dbAlias'=>'firms',
                'number'=>true,
                'db'        => 'status',
                'dt'        => 6,
                'filterfrom'=> function($searchString){
                    return Helper::getDTCompatibleFilterValueForFirmStatus($searchString);
                    
                },
                'formatter' => function( $d, $row ) {
                    return Helper::getUserStatusTitle($d);
                }
            ),
            array(
                'dt'        => 7,
                'formatter' => function( $d, $row ) {
                   return encrypt($row['id']);
                }
            )

        );
        
        $bindings=[1,1,3];

        $whereConditions="((firms.is_deleted!=? or firms.is_deleted is null) and users.is_primary=? and users.role_id=? )";
        $totalCount = DB::table('firms')
                ->selectRaw('count(firms.'.$primaryKey.') totCount')
                ->leftJoin('users', function ($join) {
                    $join->on('firms.id', '=', 'users.firm_id');
                })
                ->whereRaw($whereConditions, $bindings)
                ->get()
                ->toArray();

        //$whereConditions.= " and users.is_primary=1";

        $dtWhere=DatatableHelper::filter ( $request, $columns,$bindings);
        if($dtWhere!==""){
            $whereConditions.=" and ".$dtWhere;
        }
        $orderBy=DatatableHelper::order ( $request, $columns );
        $limit=DatatableHelper::limit ( $request, $columns );

       
        $data = DB::table('firms')
                ->selectRaw('firms.*,users.first_name,users.last_name')
                ->leftJoin('users', function ($join) {
                    $join->on('firms.id', '=', 'users.firm_id');
                })
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
        
        $totalFilteredCount = DB::table('firms')
                ->selectRaw('count(firms.'.$primaryKey.') totCount, firms.'.$primaryKey)
                ->leftJoin('users', function ($join) {
                    $join->on('firms.id', '=', 'users.firm_id');
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        if($request->isMethod('post')){  
            $offsetDay=Config('constants.reocurTwoDayCalOffset');
            if(Helper::getSastTime()>=config('constants.bankingCutOffTime')){
                $offsetDay++;
            }
            $offsetDay=Helper::businessDayOffset($offsetDay);
            $additionalValidation=[
                                    "first_name"    => 'required', 
                                    "last_name"     => 'required', 
                                    "email"         =>  'required|email|unique:users',
                                    "contact_number"=> 'required|regex:/[0-9]+/', 
                                    "trading_as"    => [
                                                        'required',
                                                        'without_spaces',
                                                        //'regex:/^[a-zA-Z]+$/u',
                                                        'no_special_char',
                                                        'max:10',
                                                        Rule::unique('firms','trading_as')->where(static function ($query) {
                                                                return $query->where('is_deleted','!=','1');
                                                        })
                                                       ],
                                    "mandate_ref"    => [
                                                        'required',
                                                        'without_spaces',
                                                        'no_special_char',
                                                        Rule::unique('firms','mandate_ref')->where(static function ($query) {
                                                                return $query->where('is_deleted','!=','1');
                                                        })
                                                       ],
                                    "setup_collection_date"=> [
                                        Rule::requiredIf(function () use ($request) {
                                                return ($request['setup_fee']>0);
                                        }),
                                        function ($attribute, $value, $fail) use ($request,$offsetDay){

                                            if (!empty($value) && strtotime($value)< strtotime("+".$offsetDay." day",strtotime(date('Y-m-d')))){
                                                $fail("Setup collection date should be after atleast ".$offsetDay." working days");
                                            }
                                        }
                                    ],
                                    "monthly_collection_date"=>[
                                            Rule::requiredIf(function () use ($request) {
                                                return (intval($request['monthly_fee'])>0);
                                            }),
                                            function ($attribute, $value, $fail) use ($request,$offsetDay){
                                                
                                                if (!empty($value) && strtotime($value)< strtotime("+".$offsetDay." day",strtotime(date('Y-m-d')))){
                                                    $fail("Monthly Collection date should be after atleast ".$offsetDay." working days");
                                                }
                                            }
                                        ],
                                    "setup_fee"       => ['required','regex:/[0-9]+/'],

                                    "monthly_fee"      => ['required','regex:/[0-9]+/'],
                                ];
            $validator = $this->validation($request->all(),$additionalValidation);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $firm = new Firm();
            if(!empty($firm->setup_collection_date)){
                $firm->setup_collection_date       = Helper::convertDate($request->setup_collection_date,"Y-m-d");    
            }
            $firm->setup_fee              = $request->setup_fee;
            
            if(!empty($firm->monthly_collection_date)){
                $firm->monthly_collection_date       = Helper::convertDate($request->monthly_collection_date,"Y-m-d");    

                /*
                if recurring start date is set to bigger/later then next collection amount. 
                then mark next collection date same as recurring_start_date
                */
                if((strtotime($firm->monthly_collection_date)>strtotime($firm->next_collection_date)) || is_null($firm->next_collection_date)){
                    $firm->next_collection_date=$firm->monthly_collection_date;
                }

            }
            $firm->monthly_fee = $request->monthly_fee;
            $firm->payment_reff_number=Helper::generatePaymentReff(8);
        
        
            $firm = $this->firmSave($request,$firm);

            $this->createInitialLedger($firm->id);
            
            $user = new User();
            $user = $this->userSave($request,$user,$firm);
            
            $password                   = str_random(8);
            $hashed_random_password     = Hash::make($password);
            $user->password             =  $hashed_random_password;
            $user->is_primary           =  1;
            $user->role_id              =  3;
            $user->save();

            $fromEmail     = CompanyInformation::findOrFail(1)->email;
            $fromName      = CompanyInformation::findOrFail(1)->company_name;

            $data = [
                'template'           => 'password-reset',
                'to_email'           => $user->email,
                'temporary_password' => $password,
                'subject'            => "Merchant Password Reset",
                'from_email'         => $fromEmail,
                'from_name'          => $fromName
            ];

            $status = Helper::sendMail($data);
            if($status==true){
                Session::flash('status','Merchant Added successfully and sent an email');
                Session::flash('class','success');
            }else{
                Session::flash('status','Merchant Added successfully but problem in sending an email');
                Session::flash('class','danger');
            }
            return redirect('admin/firms/add/rates/'.encrypt($firm->id));
        }

        $holidayData=PublicHolidays::where('holiday_date','>=',date('Y-m-d'))->get();
        $holidayDates=[];
        foreach ($holidayData as $key => $eachHoliday) {
            $holidayDates[]=Helper::convertDate($eachHoliday['holiday_date'],'m/d/Y');
        }

        $bankDetails = BankDetails::where('is_active','yes')->get();
            $pagename = "Add Merchant";
            return view('admin.firms.add',compact('pagename','bankDetails','request','holidayDates'));
    }

    function createInitialLedger($firmId){
        $paymentLedgers=new PaymentLedgers();
        $paymentLedgers->firm_id=$firmId;
        $paymentLedgers->target_reffrence_id=null;
        $paymentLedgers->transaction_type='refill';
        $paymentLedgers->ledger_desc='Opening balance';
        $paymentLedgers->amount=0;
        $paymentLedgers->closing_amount=0;
        $paymentLedgers->entry_type='cr';
        $paymentLedgers->entry_date=date('Y-m-d');
        $paymentLedgers->save();
}
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateFirm(Request $request, $id)
    {
        $firm = Firm::find(decrypt($id));
        $bankDetails = BankDetails::where('is_active','yes')->get();
        $pagename = "Update Merchant";
        if($firm){
            $holidayData=PublicHolidays::where('holiday_date','>=',date('Y-m-d'))->get();
            $holidayDates=[];
            foreach ($holidayData as $key => $eachHoliday) {
                $holidayDates[]=Helper::convertDate($eachHoliday['holiday_date'],'m/d/Y');
            }
            //return redirect('admin/firms/add/rates/'.$id);
            if($request->isMethod('post')){
                $firm_id   = decrypt($id);
                $user      = User::where('firm_id',$firm_id)->where('is_primary',1)->first();
                
                $offsetDay=Config('constants.reocurTwoDayCalOffset');
                if(Helper::getSastTime()>=config('constants.bankingCutOffTime')){
                    $offsetDay++;
                }
                $offsetDay=Helper::businessDayOffset($offsetDay);


                $additionalValidation=[
                                        "trading_as"    => [
                                                            'required',
                                                            'without_spaces',
                                                            //'regex:/^[a-zA-Z]+$/u',
                                                            'no_special_char',
                                                            'max:10',
                                                            Rule::unique('firms','trading_as')->where(static function ($query) {
                                                                    return $query->where('is_deleted','!=','1');
                                                            })->ignore($firm_id)
                                                           ],
                                        "mandate_ref"    => [
                                                            'required',
                                                            'without_spaces',
                                                            'no_special_char',
                                                            Rule::unique('firms','mandate_ref')->where(static function ($query) {
                                                                    return $query->where('is_deleted','!=','1');
                                                            })->ignore($firm_id)
                                                           ],
                                        "setup_collection_date"=> [
                                                Rule::requiredIf(function () use ($request) {
                                                    return (intval($request['setup_fee'])>0);
                                                }),
                                            function ($attribute, $value, $fail) use ($request,$offsetDay,$firm){
                                                if(strtotime($value)!=strtotime($firm->setup_collection_date)){
                                                    if (!empty($value)  && strtotime($value)< strtotime("+$offsetDay day",strtotime(date('Y-m-d')))){
                                                        $fail("Setup collection date should be after atleast ".$offsetDay." working days");
                                                    }
                                                }
                                            }
                                        ],
                                        "monthly_collection_date"=>[
                                                Rule::requiredIf(function () use ($request) {
                                                    return (intval($request['monthly_fee'])>0);
                                                }),
                                                function ($attribute, $value, $fail) use ($request,$offsetDay,$firm){
                                                    if(strtotime($value)!=strtotime($firm->monthly_collection_date)){
                                                        
                                                        if (!empty($value) && strtotime($value)< strtotime("+$offsetDay day",strtotime(date('Y-m-d')))){
                                                            $fail("Monthly Collection date should be after atleast ".$offsetDay." working days");
                                                        }
                                                    }
                                                }
                                            ],
                                        "setup_fee"       => ['required','regex:/[0-9]+/'],

                                        "monthly_fee"      => ['required','regex:/[0-9]+/'],
                                    ];
                $validator = $this->validation($request->all(),$additionalValidation);
                
                if ($validator->fails()) {
                    return redirect()->back()->withErrors($validator)->withInput();
                }

                $firm = Firm::find($firm_id);
                
                if(is_null($firm->payment_reff_number) || $firm->payment_reff_number==''){
                    $firm->payment_reff_number=Helper::generatePaymentReff(8);
                }
                $firm = $this->firmSave($request,$firm);

                if($user->save()){
                    Session::flash('status','Merchant Updated successfully');
                    Session::flash('class','success');
                }else{
                     Session::flash('status','Problem in updating merchant');
                     Session::flash('class','danger');
                }
                //return redirect('admin/firms/update/rates/'.encrypt($firm_id));
                return redirect('admin/firms');
            }
            

            return view('admin.firms.firmUpdate',compact('pagename','bankDetails','firm','holidayDates'));
        }else{
            Session::flash('status','Your request cannot be processed!');
            Session::flash('class','danger');
            return redirect('admin/firms');
        }
        
    }

    public function viewFirmInfo($id){
        $pagename = "View Merchant";
        $firm = Firm::find(decrypt($id));
        if(!$firm){
            Session::flash('status','Your request cannot be processed!');
            Session::flash('class','danger');
            return redirect('admin/firms');
        }
        $bankDetails = BankDetails::where('is_active','yes')->get();
        $tabName="firmInfo";
        return view('admin.firms.view',compact('pagename','bankDetails','firm','tabName'));
    }

    public function viewFirmUser($id){
        $pagename = "View Merchant User";
        $firmId=decrypt($id);
        $firm = Firm::find($firmId);
        $primaryUser = User::where('firm_id',$firmId)->where('is_primary',1)->first();
        $tabName="firmUser";
        if(!$firm){
            Session::flash('status','Your request cannot be processed!');
            Session::flash('class','danger');
            return redirect('admin/firms');
        }
        return view('admin.firms.firmUser',compact('pagename','firm','tabName','primaryUser'));
    }

    public function viewFirmRates($id){
        $pagename = "View Merchant Rates";
        $firmId=decrypt($id);
        $firm = Firm::find($firmId);
        $rates          = Rates::where(['firm_id' => $firmId,"status" => "active"])->first();
        $profileLimits  = ProfileLimits::where(['firm_id' => $firmId])->first();
        if((!$rates || !$profileLimits) && $firm){
            return redirect('admin/firms/add/rates/'.encrypt($firm->id));
        }
        $tabName="firmRate";
        if(!$firm){
            Session::flash('status','Your request cannot be processed!');
            Session::flash('class','danger');
            return redirect('admin/firms');
        }
        return view('admin.firms.firmRates',compact('pagename','firm','tabName','rates','profileLimits'));
    }

    public function viewFirmCollectionProfile($id){
        $pagename = "View Merchant Collection Profile";
        $firmId=decrypt($id);
        $firmDetails=$firm = Firm::find($firmId);
        $rates          = Rates::where(['firm_id' => $firmId,"status" => "active"])->first();
        $profileLimits  = ProfileLimits::where(['firm_id' => $firmId])->first();
        $tabName="profileLimit";

        $fundLimit=ProfileTransactions::where('firm_id',$firmId)->where('product_type','collection_topup')->groupBy('product_type')->selectRaw('*, sum(amount) as tot_aval')->first();

        $transactionLimit=ProfileTransactions::where('firm_id',$firmId)->where('product_type','collection_topup')->orderBy("transmission_date",'desc')->first();
        if(is_null($transactionLimit)){
            $transactionLimit=new ProfileTransactions();
            $transactionLimit->closing_balance=0;
        }

        $transactedAmount=DB::select(DB::raw("SELECT sum(amount) as tot_amount FROM `collections` where transmission_status in (0,1,2) and collection_status=1 and DATE_FORMAT(payment_date, '%Y-%m')=:monthYear and firm_id=:firmId"),array('monthYear'=>date('Y-m'),'firmId'=>$firmId));
        $transactedAmount=$transactedAmount[0];
        
        $statements=ProfileTransactions::where('firm_id',$firmId)->orderBy('transmission_date','desc')->get();

        if(!$firm){
            Session::flash('status','Your request cannot be processed!');
            Session::flash('class','danger');
            return redirect('admin/firms');
        }
        return view('admin.firms.firmCollectionProfile',compact('pagename','firm','tabName','statements','fundLimit','transactionLimit','transactedAmount','firmDetails'));
    }

    public function viewFirmPaymentStats(Request $request,$id){
        $firmId=decrypt($id);
        $pagename = "Payment Statement";
        $firmDetails=$firm = Firm::find($firmId);
        $tabName="profileStats";
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

        if(!$firm){
            Session::flash('status','Your request cannot be processed!');
            Session::flash('class','danger');
            return redirect('admin/firms');
        }
        return view('admin.firms.firmPaymentProfile',compact('pagename','firm','tabName','firmDetails','availableFund','firstRecord','paymentStatement','dateFrom','dateUpto','request'));
    }

    public function batchPaymentList($batchId){
        $pagename = "Batch Payment List";
        $batchId=decrypt($batchId);

        $batch=PaymentBatches::where(['id'=>$batchId])->first();
        if(is_null($batch)){
            return redirect('admin/firms');
        }
        
        return view('admin.firms.batch-payment-list',compact('pagename','batchId','batch'));
    }

    public function ajaxBatchPaymentList(Request $request){
        $primaryKey = 'id';
        $batchId   = decrypt($request->batchId);
        
        
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
        
        
        
        $bindings=[$batchId];

        $whereConditions ="payments.batch_id =? ";
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

    public function viewFirmMonthlyCollection($id){
        $pagename = "View Merchant Monthly Collection";
        $firmId=decrypt($id);
        $firmDetails=$firm = Firm::find($firmId);
        if(!$firm){
            Session::flash('status','Your request cannot be processed!');
            Session::flash('class','danger');
            return redirect('admin/firms');
        }
        
        $tabName="monthlyCollection";

        $transactionLimit=ProfileTransactions::where('firm_id',$firmId)->where('product_type','collection_topup')->orderBy("transmission_date",'desc')->first();
        if(is_null($transactionLimit)){
            $transactionLimit=new ProfileTransactions();
            $transactionLimit->closing_balance=0;
        }
        $transactedAmount=DB::select(DB::raw("SELECT sum(amount) as tot_amount FROM `collections` where transmission_status in (0,1,2) and collection_status=1 and DATE_FORMAT(payment_date, '%Y-%m')=:monthYear and firm_id=:firmId"),array('monthYear'=>date('Y-m'),'firmId'=>$firmId));
        $transactedAmount=$transactedAmount[0];
        $ledgerData=Ledgers::where('firm_id',$firmId)->whereIn('transaction_type',['failed_collection','batch_collection'])->whereRaw("DATE_FORMAT(entry_date,'%Y-%m') ='".date('Y-m')."'")->orderBy('entry_date','desc')->get();
        
        


        return view('admin.firms.firmMonthlyCollection',compact('pagename','firm','tabName','transactionLimit','transactedAmount','firmDetails','ledgerData'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request,$id)
    {
        if($request->isMethod('delete')){
            $id = decrypt($id);
            $firm = Firm::where('id',$id)->first();
            if($firm){
                $firm->is_deleted = 1;
                $firm->deleted_by = auth()->user()->id;
                $firm->deleted_at = date("Y-m-d H:i:s");
                $user = User::where('firm_id',$id)->where('is_primary',1)->first();
                $user->is_deleted = 1;
                $user->deleted_by = auth()->user()->id;
                $user->deleted_at = date("Y-m-d H:i:s");
                if ($firm->save() && $user->save()) {
                    Session::flash('status','Firm deleted successfully');
                    Session::flash('class','success');
                }else{
                    Session::flash('status','Problem in deleting the record');
                    Session::flash('class','danger');
                }
            }else{
                Session::flash('status','Problem in deleting the record');
                Session::flash('class','danger');
            }
            return redirect('admin/firms');
        }
    }

    public function deleteFirm(Request $request){
        $requestStatus=['status'=>402,'message'=>'Problem in deleting the record',"type"=>"danger"];
        if($request->isMethod('delete')){
            $firmId=decrypt($request->firmId);
                      
            $firmRes = Firm::where('id',$firmId)->first();
            if($firmRes){
                
                $firmRes->is_deleted = 1;
                $firmRes->deleted_by = auth()->user()->id;
                $firmRes->deleted_at = date("Y-m-d H:i:s");
                
                if ($firmRes->save()) {

                    $user = User::where('firm_id',$firmId)->where('is_primary',1)->first();
                    $user->is_deleted = 1;
                    $user->deleted_by = auth()->user()->id;
                    $user->deleted_at = date("Y-m-d H:i:s");
                    $user->save();

                    $profileLimits = ProfileLimits::where('firm_id',$firmId)->get();
                    if(count($profileLimits)>0){
                        foreach($profileLimits as $profileLimit){
                            $profileLimit->is_deleted = 1;
                            $profileLimit->deleted_by = auth()->user()->id;
                            $profileLimit->deleted_at = date("Y-m-d H:i:s");
                            $profileLimit->save();
                        }
                    }
                    $rates = Rates::where('firm_id',$firmId)->get();
                    if(count($rates)>0){
                        foreach($rates as $rate){
                            $rate->is_deleted = 1;
                            $rate->deleted_by = auth()->user()->id;
                            $rate->deleted_at = date("Y-m-d H:i:s");
                            $rate->save();
                        }
                    }
                    $requestStatus = ['status'=>201,'message'=>'Firm Deleted Successfully',"type"=>"success"];
                }    
            }
            
        }
        echo json_encode($requestStatus);
        //return redirect('merchant/users');
    }

    public function deleteMultipleFirms(Request $request){
        if($request->isMethod('delete')){
            $i=0;
            foreach ($request->toDelete as $key => $eachUser) {
                //$FirmId=decrypt($eachUser);
                $firmId=$eachUser;
                      
                $firmRes = Firm::where('id',$firmId)->first();
                if($firmRes){
                    $firmRes->is_deleted = 1;
                    $firmRes->deleted_by = auth()->user()->id;
                    $firmRes->deleted_at = date("Y-m-d H:i:s");
                    
                    $user = User::where('firm_id',$firmId)->where('is_primary',1)->first();
                    $user->is_deleted = 1;
                    $user->deleted_by = auth()->user()->id;
                    $user->deleted_at = date("Y-m-d H:i:s");
                    $user->save();

                    if ($firmRes->save()) {
                        $i++;
                    }    
                }
            }

            Session::flash('status',$i.' Firm Deleted Successfully');
            Session::flash('class','success');
            return redirect('admin/firms');
            
        }
    }

    private function validation($request,$additionalValidation){
            $entryClassvalues=array_keys(Config('constants.entry_class'));

            $validatorArray = [
                "business_name"        => 'required', 
                "address1"             => 'required', 
                "city"                 => 'required', 
                "bank_id"              => 'required|exists:bank_details,id', 
                "account_type"         => [
                                           'required',
                                            Rule::in(Config('constants.accountType'))
                                          ],
                "entry_class"         => [
                                           'required',
                                            Rule::in($entryClassvalues)
                                          ],
                "branch_code"          => 'required|exists:bank_details,branch_code|regex:/[0-9]+/', 
                "account_holder_name"  => 'required|no_special_char', 
                "account_number"       => 'required|integer|regex:/[0-9]+/', 
                "status"               => 'required|in:0,1',
                "is_payment"           => 'required|in:0,1',
                //"is_payment"           => 'required_unless:is_payment,1',
                "is_collection"           => 'required|in:0,1',
                "is_debicheck"           => 'required|in:0,1',
                "is_avs"           => 'required|in:0,1',

            ];
            
            $paymentValidation = [];
            if(isset($request['is_payment']) && $request['is_payment']==1){
                $paymentValidation = [
                  "is_salaries"          => 'required_without:is_creditors|in:0,1',
                  "is_creditors"          => 'required_without:is_salaries|in:0,1',
                ];
            }

            if(isset($request['is_collection']) && $request['is_collection']==1){
                $paymentValidation["is_normal_collection"]= 'required_without:is_reoccur_collection|in:0,1';
                $paymentValidation["is_reoccur_collection"]= 'required_without:is_normal_collection|in:0,1';
            }

            
            
            $validationArr = array_merge($validatorArray,$paymentValidation,$additionalValidation);
            $validator     = \Validator::make($request,$validationArr ,[
                "is_payment.required"=>"Please choose any Option",
                "is_collection.required"=>"Please choose any Option",
                "is_debicheck.required"=>"Please choose any Option",
                "is_avs.required"=>"Please choose any Option",
                "address1.required"            => 'Please Add address',
                "bank_id.required"             => 'Please Select Bank',
                "is_payment.required_unless"   => "Please select at least one product",
                "branch_code.required"         => 'Please select bank first',
                "is_salaries.required_without" => "Please select at least one between salaries and creditors",
                "is_normal_collection.required_without" => "Please select at least one between Normal and reccuring Collection",
                "without_spaces"=>"Should not have any space",
                "trading_as.required"=>"Abbreviated Name is required.",
                "trading_as.unique"=>"The Abbreviated Name has already been taken."
            ]);
            return $validator;
    }

    private function firmSave($request,$firm){
            $firm->trading_as          = $request->trading_as; 
            //$firm->trading_as          = "PAYPORT";  //temporary
            $firm->mandate_ref         = $request->mandate_ref; 
            $firm->business_name       = $request->business_name; 
            $firm->address1            = $request->address1; 
            $firm->city                = $request->city; 
            $firm->subrub              = $request->subrub; 
            $firm->province            = $request->province; 
            $firm->po_box_number       = $request->po_box_number; 
            $firm->vat_no              = $request->vat_no; 
            $firm->entry_class         = $request->entry_class; 
            $firm->registration_no     = $request->registration_no; 
            $firm->bank_id             = $request->bank_id; 
            $firm->account_type        = $request->account_type; 
            $firm->branch_code         = $request->branch_code; 
            $firm->account_holder_name = $request->account_holder_name; 
            $firm->account_number      = $request->account_number; 
            $firm->status              = $request->status;
            
            $firm->setup_collection_date  = Helper::convertDate($request->setup_collection_date,"Y-m-d");
            $firm->monthly_collection_date= Helper::convertDate($request->monthly_collection_date,"Y-m-d");
            $firm->setup_fee              = $request->setup_fee; 
            $firm->monthly_fee            = $request->monthly_fee; 

            $firm->is_payment          = isset($request->is_payment) ? $request->is_payment : 0 ;

            

            if($firm->is_payment==1 || $firm->is_payment==true){
                $firm->is_salaries         = isset($request->is_salaries) ? $request->is_salaries : 0 ;
                $firm->is_creditors        = isset($request->is_creditors) ? $request->is_creditors : 0 ;
            }else{
                $firm->is_salaries         = 0;
                $firm->is_creditors        = 0 ;
            }
            

            $firm->is_collection       = isset($request->is_collection) ? $request->is_collection : 0 ;

            if($firm->is_collection==1 || $firm->is_collection==true){
                $firm->is_normal_collection         = isset($request->is_normal_collection) ? $request->is_normal_collection : 0 ;
                $firm->is_reoccur_collection        = isset($request->is_reoccur_collection) ? $request->is_reoccur_collection : 0 ;
            }else{
                $firm->is_normal_collection         = 0;
                $firm->is_reoccur_collection        = 0 ;
            }

            $firm->is_avs       = isset($request->is_avs) ? $request->is_avs : 0 ;

            if($firm->is_avs==1 || $firm->is_avs==true){
                $firm->is_avs_batch     = isset($request->is_avs_batch) ? $request->is_avs_batch : 0 ;
                $firm->is_avs_rt        = isset($request->is_avs_rt) ? $request->is_avs_rt : 0 ;
            }else{
                $firm->is_avs_batch     = 0;
                $firm->is_avs_rt        = 0 ;
            }
            
            $firm->is_debicheck        = isset($request->is_debicheck) ? $request->is_debicheck : 0 ;
            
            $firm->is_avs        = isset($request->is_avs) ? $request->is_avs : 0 ;
            $firm->save(); 
            return $firm;
    }

    private function userSave($request,$user,$firm){
            $user->first_name           =  $request->first_name;
            $user->last_name            =  $request->last_name; 
            $user->email                =  $request->email; 
            $user->contact_number       =  $request->contact_number;
            $user->status               =  $request->status;
            $user->firm_id              =  $firm->id;
            return $user;
    }

    public function addRatesAndProfileLimit(Request $request){
        
        $firmId   = decrypt($request->id);
        $firm     = Firm::find($firmId);
        if(!$firm){
            Session::flash('status','Firm not found');
            Session::flash('class','danger');
            return redirect('admin/firms');
        }
        if($request->isMethod('get')){                    
            $pagename = "Rate Table and Profile limit for ".$firm->trading_as;
            return view('admin.firms.add-rates',compact('firmId','firm','pagename'));
        }else{

            // echo "<pre>";
            // print_r($request->all());
            // die;

            $validator = $this->ratesValidation($request->all(),$firm);
            if ($validator->fails()){
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $rates         = new Rates();
            $rates         = $this->saveRates($request,$firmId,$rates);
            
            $profileLimits = new ProfileLimits();
            $profileLimits = $this->saveProfileLimit($request,$firmId,$profileLimits);
            $rates->save();
            $profileLimits->save();

            $adminFirmId=Config('constants.payportFirmId'); 
            Helper::writeProfileLimitTrax($adminFirmId,$firmId,$request->monthly_collection,'Opening Balance','cr');

            Session::flash('status','Rates and Profile Limits has been added Successfully');
            Session::flash('class','success');
            return redirect('admin/firms');
        }
    }

    public function updateRatesAndProfileLimit(Request $request,$id){
        
        $firmId         = decrypt($request->id);
        $firm           = Firm::find($firmId);
        
        $rates          = Rates::where(['firm_id' => $firmId ,"status" => "active"])->first();
        $profileLimits  = ProfileLimits::where(['firm_id' => $firmId ])->first();
        if($firm){
            if($request->isMethod('get')){                    
                $pagename = "Rate Table and Profile limit for ".$firm->trading_as;
                $past_rates = Rates::where('firm_id',$firm->id)->where('status','<>',1)->get();
                return view('admin.firms.update-rates',compact('firm','pagename','rates','profileLimits','past_rates'));
            }else{
                $validator = $this->ratesValidation($request->all(),$firm);
                if ($validator->fails()){
                    return redirect()->back()->withErrors($validator)->withInput();
                }
                if(isset($rates)){

                    $old_rate_details= [

                        "same_day_payment"      => floatval($rates->same_day_payment),  
                        "one_day_payment"       => floatval($rates->one_day_payment),
                        "two_day_payment"       => floatval($rates->two_day_payment),
                        "batch_fee_payment"     => floatval($rates->batch_fee_payment),
                        "failed_payment"        => floatval($rates->failed_payment),
                        "same_day_collection"   => floatval($rates->same_day_collection),  
                        "one_day_collection"   => floatval($rates->one_day_collection),  
                        "two_day_collection"    => floatval($rates->two_day_collection),
                        "batch_fee_collection"  => floatval($rates->batch_fee_collection),
                        "failed_collection"     => floatval($rates->failed_collection),
                        "avs_batch"     => floatval($rates->avs_batch),
                        "avs_rt"     => floatval($rates->avs_rt),
                    ];
                    

                    $new_rate_details= [

                        "same_day_payment"      => floatval($request->same_day_payment),  
                        "one_day_payment"       => floatval($request->one_day_payment),
                        "two_day_payment"       => floatval($request->two_day_payment),
                        "batch_fee_payment"     => floatval($request->batch_fee_payment),
                        "failed_payment"        => floatval($request->failed_payment),
                        "same_day_collection"   => floatval($request->same_day_collection),  
                        "one_day_collection"    => floatval($request->one_day_collection),
                        "two_day_collection"    => floatval($request->two_day_collection),
                        "batch_fee_collection"  => floatval($request->batch_fee_collection),
                        "failed_collection"     => floatval($request->failed_collection),
                        "avs_batch"     => floatval($request->avs_batch),
                        "avs_rt"     => floatval($request->avs_rt),
                    ];
                    
                    $new_rate_details = json_encode($new_rate_details);
                    $old_rate_details = json_encode($old_rate_details);
                    
                    if(md5($new_rate_details)!=md5($old_rate_details)){
                        $rates->status = 0;
                        $rates->save();
                        $rates     = new Rates();
                        $rates     = $this->saveRates($request,$firmId,$rates);
                    }
                }else{
                    $rates = new Rates();
                    $rates->firm_id = $firmId;
                    $rates    = $this->saveRates($request,$firmId,$rates);
                }
                if(isset($profileLimits)){
                    $profileLimits = $this->saveProfileLimit($request,$firmId,$profileLimits);
                }else{
                    $profileLimits = new ProfileLimits();
                    $profileLimits->firm_id = $firmId;
                    $profileLimits = $this->saveProfileLimit($request,$firmId,$profileLimits);
                }
                
                $rates->save();
                $profileLimits->save();

                Session::flash('status','Rates and Profile Limits has been Updated Successfully');
                Session::flash('class','success');
            }
        }else{
            Session::flash('status','Problem in finding the records');
            Session::flash('class','danger');
            return redirect('admin/firms');
        }  
         return redirect('admin/firms/rates/'.encrypt($firm->id));  
    }

    private function ratesValidation($request,$firm){
            $paymentValidation = [];
            $collectionValidation = [];
            if(isset($firm->is_payment) && $firm->is_payment==1){
                $paymentValidation = [ 
                    "same_day_payment" => 'required|numeric', 
                    "one_day_payment" => 'required|numeric', 
                    "two_day_payment" => 'required|numeric', 
                    "batch_fee_payment" => 'required|numeric', 
                    "failed_payment" => 'required|numeric', 
                    "line_payment" => 'required|numeric', 
                    "batch_payment" => 'required|numeric', 
                    "monthly_payment" => 'required|numeric', 
                ]; 
            }

            if(isset($firm->is_collection) && $firm->is_collection==1){
                $collectionValidation = [ 
                    "same_day_collection" => 'required|numeric', 
                    "one_day_collection" => 'required|numeric', 
                    "two_day_collection" => 'required|numeric', 
                    "failed_collection" => 'required|numeric', 
                    "line_collection" => 'required|numeric',
                    'surety_amount'  => 'required|numeric|min:0|max:100',
                    "monthly_collection"       => [
                                                Rule::requiredIf(function () use ($request) {
                                                    if(array_key_exists('monthly_collection', $request)){
                                                        return true;
                                                    }
                                                    return false;
                                                }),
                                            ],
                ]; 
            }
            $otherValidation = [
                'surety_amount'  => 'required|numeric',
                'reserve_amount' => 'required|numeric',
                'setup_fee'      => 'required|numeric',
                'monthly_fee'    => 'required|numeric',

            ];

            $payportFirmId=Config('constants.payportFirmId');
        
            $payPortInfo=ProfileTransactions::where('firm_id',$payportFirmId)->orderBy('transmission_date','desc')->first();

            $validationArr = array_merge($paymentValidation,$collectionValidation);
            

            $validator = \Validator::make($request,$validationArr,[
                'monthly_collection.required' => 'Monthly limit is required',
                'monthly_collection.numeric' => 'Monthly limit must be a number',
                'monthly_collection.max' => 'Monthly limit can not be greater then '.$payPortInfo->closing_balance,
                'monthly_collection.min' => 'Monthly limit can not be less then 1',
                'surety_amount.required' => 'Surety Percentage is required',
                'surety_amount.numeric' => 'Surety Percentage must be a number',
                'surety_amount.max' => 'Surety Percentage can not be greater then 100',
                'surety_amount.min' => 'Surety Percentage can not be less then 0',
            ]);

            $validator->sometimes(['monthly_collection'], 'numeric', function ($input) {
                return !empty($input->monthly_collection);
            });

            
            
            $validator->sometimes(['monthly_collection'], 'max:'.$payPortInfo->closing_balance, function ($input) {
                return !empty($input->monthly_collection);
            });

            $validator->sometimes(['monthly_collection'], 'min:1', function ($input) {
                return !empty($input->monthly_collection);
            });
            
            return $validator;
    }

    private function saveRates($request,$firmId,$rates){

        $rates->same_day_payment      = $request->same_day_payment;  
        $rates->one_day_payment       = $request->one_day_payment;
        $rates->two_day_payment       = $request->two_day_payment;
        $rates->batch_fee_payment     = $request->batch_fee_payment;
        $rates->failed_payment        = $request->failed_payment;
        $rates->same_day_collection   = $request->same_day_collection;  
        $rates->one_day_collection    = $request->one_day_collection;
        $rates->two_day_collection    = $request->two_day_collection;
        //$rates->batch_fee_collection  = $request->batch_fee_collection;
        $rates->failed_collection     = $request->failed_collection;
        $rates->firm_id               = $firmId;
        $rates->created_by            = auth()->user()->id;
        $rates->status                = 1;
        $rates->avs_batch             = $request->avs_batch;
        $rates->avs_rt                = $request->avs_rt;
        return $rates;
    }
    private function saveProfileLimit($request,$firmId ,$profileLimits){
        
       $profileLimits->line_payment = $request->line_payment;
       $profileLimits->batch_payment = $request->batch_payment;
       //$profileLimits->daily_payment = $request->daily_payment;
       $profileLimits->monthly_payment = $request->monthly_payment;
       $profileLimits->line_collection = $request->line_collection;
       //$profileLimits->batch_collection = $request->batch_collection;
       //$profileLimits->daily_collection = $request->line_collection;
       if(property_exists($request, 'monthly_collection')){
            $profileLimits->monthly_collection = $request->monthly_collection;
       }
       
       $profileLimits->surety_amount = $request->surety_amount;
       //$profileLimits->reserve_amount = $request->reserve_amount;
       //$profileLimits->setup_fee = $request->setup_fee;
       //$profileLimits->monthly_fee = $request->monthly_fee;
       $profileLimits->created_by = auth()->user()->id;
       $profileLimits->firm_id = $firmId;
       $profileLimits->status = 1;
       return $profileLimits;
    }

}
