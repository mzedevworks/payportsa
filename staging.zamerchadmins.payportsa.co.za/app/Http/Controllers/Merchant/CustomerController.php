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
use App\Model\{Firm,BankDetails,Role,CompanyInformation,Employees,Customer,TempCustomers,PublicHolidays};
//use Maatwebsite\Excel\Facades\Excel;
use Response;

class CustomerController extends Controller
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
        $pagename  = "My Customer List";
        if(auth()->user()->role_id==3){ 
         return view('merchant.customers.list-for-merchant',compact('pagename'));
        }
        if(auth()->user()->role_id==4){ 
         return view('merchant.customers.list-for-capturer',compact('pagename'));
        }
        
    }

    private function merchantDtColumns(){
        $columns = array(
            array( 'dt' => 0,
                'formatter' => function( $d, $row ) {
                    //return encrypt($row['id']);
                    return $row['id'];
                }
            ),
            array( 'db' => 'mandate_id', 'dt' => 1 ),
            array( 'db' => 'first_name', 'dt' => 2 ),
            array( 'db' => 'last_name',  'dt' => 3 ),
            array( 'db' => 'contact_number',     'dt' => 4 ),
            array( 'db' => 'once_off_amount',     'dt' => 5 ),
            array( 
                'db' => 'collection_date', 
                'dt' => 6,
                'formatter' => function($d,$row){
                    return Helper::convertDate($row['collection_date'],'Y-m-d');
                }
            ),
            array(
                'dbAlias'   => 'bank_details',
                'db'        => 'bank_name',
                'dt'        => 7
            ),
            array( 
                'dbAlias'   => 'customers',
                'db' => 'branch_code', 
                'dt' => 8, 
            ),
            array( 'db' => 'account_number', 'dt' => 9 ),
            //array( 'db' => 'reference',  'dt' => 10 ),
            array( 'db' => 'service_type',  'dt' => 10 ),
            array(
                'db'        => 'status',
                'dt'        => 11,
                'formatter' => function( $d, $row ) {
                    if($row['status']==1){
                         $status  = '<span>Approved</span>';
                         $status .= '<button type="button" onclick="updateStatus('.$row['id'].',2);" class="btn btn-common" style="margin-left: 5px;">Cancel</button>';
                    }
                    if($row['status']==2){
                         $status = "Canceled";
                    }
                    if($row['status']==0){
                        $status = "Pending";
                        $status .= '<button type="button" onclick="updateStatus('.$row['id'].',1);" class="btn btn-common" style="margin-left: 5px;">Approve</button>';
                    } 
                    return $status;
                }
            ),
            array(
                
                'dt'        => 12,
                'formatter' => function( $d, $row ) {
                    return encrypt($row['id']);
                }
            ),
            array(
                
                'dt'        => 12,
                'formatter' => function( $d, $row ) {
                   return encrypt($row['id']);
                }
            ),
        );

        return $columns;
    }

    private function capturerDtColumns(){
        $columns = array(
            array( 'dt' => 0,
                'formatter' => function( $d, $row ) {
                    //return encrypt($row['id']);
                    return $row['id'];
                }
            ),
            array( 'db' => 'mandate_id', 'dt' => 1 ),
            array( 'db' => 'first_name', 'dt' => 2 ),
            array( 'db' => 'last_name',  'dt' => 3 ),
            array( 'db' => 'contact_number',     'dt' => 4 ),
            array( 'db' => 'once_off_amount',     'dt' => 5 ),
            array( 'db' => 'collection_date',     'dt' => 6 ),
            array(
                'dbAlias'   => 'bank_details',
                'db'        => 'bank_name',
                'dt'        => 7
            ),
            array('dbAlias'   => 'customers', 'db' => 'branch_code',     'dt' => 8 ),
            array( 'db' => 'account_number',     'dt' => 9 ),
            //array( 'db' => 'reference',     'dt' => 10 ),
            array(
                
                'dt'        => 10,
                'formatter' => function( $d, $row ) {
                   return encrypt($row['id']);
                }
            )
        );

        return $columns;
    }


    public function ajaxUsersList(Request $request){
        //print_r($request['columns']);
        // Table's primary key
        $primaryKey = 'id';
         
        if(auth()->user()->role_id==3){ 
         $columns = $this->merchantDtColumns();
        }
        if(auth()->user()->role_id==4){ 
         $columns = $this->capturerDtColumns();
        }
        $firmId = auth()->user()->firm_id;
        
        $bindings=[$firmId,1];

        $whereConditions ="firm_id=? and (is_deleted!=? or is_deleted is null)";
        $totalCount = DB::table('customers')
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

        
        $data = DB::table('customers')
                ->selectRaw('customers.*,bank_details.bank_name')
                ->leftJoin('bank_details', function ($join) {
                    $join->on('customers.bank_id', '=', 'bank_details.id');
                })  
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
       
        $totalFilteredCount = DB::table('customers')
                ->selectRaw('count(customers.'.$primaryKey.') totCount, customers.'.$primaryKey)
                ->leftJoin('bank_details', function ($join) {
                    $join->on('customers.bank_id', '=', 'bank_details.id');
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
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
       
        // echo Config('constants.bankingCutOffTime');
        // echo "<hr>";
        // echo Helper::getSastTime();
        // if(Config('constants.bankingCutOffTime')>=Helper::getSastTime()){
        //     echo "true";
        // }else{
        //     echo "false";
        // }
        // die();
        $firmId=Auth()->user()->firm_id;
        $pagename = "Collections - New Customer";
        if($request->isMethod('post')){

            $additionalValidation=[
                "email"      =>   [
                                        'required',
                                        'email'
                                   ],
                "mandate_id" =>    [
                                        'required',
                                        'without_spaces',
                                        'no_special_char',
                                        // Rule::unique('customers')->where(function ($query) {
                                        //     return $query->where('firm_id', Auth()->user()->firm_id);
                                        // })
                                    ],
                "collection_date"=> [
                            //'required',
                                        Rule::requiredIf(function () use ($request) {
                                                return (empty($request['recurring_start_date']) || $request['once_off_amount']>0);
                                        }),
                                        function ($attribute, $value, $fail) use ($request){
                                            if (!empty($value) && $request->service_type=="Same Day" && strtotime($value)!=strtotime(date('Y-m-d'))){
                                                    $fail('Should be todays date');
                                            }
                                            if (!empty($value) && $request->service_type=="Same Day" && Config('constants.bankingCutOffTime')<Helper::getSastTime()){
                                                    $fail('Cut-Off time for same day transmission is Over!');
                                            }
                                             
                                            if (!empty($value) && $request->service_type=="1 Day" && strtotime($value)< strtotime("+1 day",strtotime(date('Y-m-d')))){
                                                $fail("Should select 1 date after today's date if service type is 1 day .");
                                            }
                                            if (!empty($value) && $request->service_type=="2 Day" && strtotime($value)< strtotime("+2 day",strtotime(date('Y-m-d')))){
                                                $fail("Should select 2 date after today's date if service type is 2 day .");
                                            }
                                        }
                                    ],
                "recurring_start_date"=>[
                                            Rule::requiredIf(function () use ($request) {
                                                return (empty($request['collection_date']) || intval($request['recurring_amount'])>0);
                                            }),
                                            function ($attribute, $value, $fail) use ($request){
                                                // if (!empty($value) && $request->service_type=="Same Day" && strtotime($value)==strtotime(date('Y-m-d'))){
                                                //         $fail('Should be future date.');
                                                // }

                                                if (!empty($value) && $request->service_type=="Same Day"){
                                                        $fail('You can not use reccuring collection with Same Day service');
                                                }
                                                if (!empty($value) && $request->service_type=="1 Day" && strtotime($value)< strtotime("+1 day",strtotime(date('Y-m-d')))){
                                                    $fail("Should select 1 date after today's date if service type is 1 day .");
                                                }

                                                if (!empty($value) && $request->service_type=="2 Day" && strtotime($value)< strtotime("+2 day",strtotime(date('Y-m-d')))){
                                                    $fail("Should select 2 date after today's date if service type is 2 day .");
                                                }
                                            }
                                        ],
                // "reference"   =>  [
                //                            'required',
                //                             // Rule::unique('customers','reference')->where(function ($query) use($firmId){
                //                             //     return $query->where('firm_id', $firmId);
                //                             // })
                //                         ],
            ];
            $validator = $this->validation($request->all(),$additionalValidation);
        

            if ($validator->fails()){
                return Redirect::to('merchant/customer/create')->withErrors($validator)->withInput();
            }

            $customer = new Customer();
            $customer = $this->customerSave($request,$customer);
            
            if($request->duration!=''){
                $customer->collection_end_date   = date('Y-m-d',strtotime("+".$customer->duration." months",strtotime($customer->collection_date)));
            }
            
            $data = [
                'template'  => 'payportUserInvite',
                'subject'   => "Your account is created.",
                'to'         => $customer
            ];

            if($customer->save()){
                $status = Helper::sendInviteMail($data);
                $status = 1;
                if($status===true){
                    Session::flash('status','User created successfully');
                    Session::flash('class','success');

                }else{
                    Session::flash('status','User Added successfully but problem in sending an email');
                    Session::flash('class','danger');
                }
            }else{
                 Session::flash('status','Unable to create User! Please try again later');
                 Session::flash('class','danger');
            }
            return redirect('merchant/customers');
        }
        
        $holidayData=PublicHolidays::where('holiday_date','>=',date('Y-m-d'))->get();
        $holidayDates=[];
        foreach ($holidayData as $key => $eachHoliday) {
            $holidayDates[]=Helper::convertDate($eachHoliday['holiday_date'],'m/d/Y');
        }
        return view('merchant.customers.create',compact('pagename','holidayDates'));
    }

    public function updateCustomer(Request $request){
        
        $customerId   = decrypt($request->id);
        $pagename = "Collections - Update Customer";

        $userStatus=config('constants.userStatus');
        $firmId=Auth()->user()->firm_id;

        if($customerId){

            $cusRes = Customer::where(['firm_id'=>$firmId,'id'=>$customerId])->first();
            if(empty($cusRes)){
                Session::flash('status','Requested record not found or you are not having permission to access that!');
                Session::flash('class','warning');
                return redirect('merchant/customers');    
            }

            if($request->isMethod('post')){
                // ,
                //             Rule::unique('customers')->where(function ($query) use ($firmId) {
                //                return $query->where('firm_id',$firmId);
                //             })->ignore($cusRes->id)
                $additionalValidation=[
                    "email"=> [
                            'required',
                            'email'
                        ],
                    "mandate_id"=>  [
                        'required',
                        'without_spaces',
                        'no_special_char',
                        Rule::unique('customers')->where(function ($query) use ($firmId) {
                            return $query->where('firm_id',$firmId);
                        })->ignore($cusRes->id)
                    ],
                    
                    "collection_date"=> [
                            //'required',
                                        Rule::requiredIf(function () use ($request) {
                                                return (empty($request['recurring_start_date']) || $request['once_off_amount']>0);
                                        }),
                                        function ($attribute, $value, $fail) use ($request,$cusRes){
                                            if(strtotime($value)!=strtotime($cusRes->collection_date)){
                                                if (!empty($value) && $request->service_type=="Same Day" && strtotime($value)!=strtotime(date('Y-m-d'))){
                                                    $fail('Should be todays date.');
                                                }
                                                if (!empty($value) && $request->service_type=="Same Day" && Config('constants.bankingCutOffTime')<Helper::getSastTime()){
                                                    $fail('Cut-Off time for same day transmission is Over!');
                                                }
                                                if (!empty($value) && $request->service_type=="1 Day" && strtotime($value)< strtotime("+1 day",strtotime(date('Y-m-d')))){
                                                    $fail("Should select 1 date after today's date if service type is 1 day .");
                                                }
                                                if (!empty($value) && $request->service_type=="2 Day" && strtotime($value)< strtotime("+2 day",strtotime(date('Y-m-d')))){
                                                    $fail("Should select 2 date after today's date if service type is 2 day .");
                                                }
                                            }
                                            
                                        }
                                    ],
                    "recurring_start_date"=>[
                                            Rule::requiredIf(function () use ($request) {
                                                return (empty($request['collection_date']) || intval($request['recurring_amount'])>0);
                                            }),
                                            function ($attribute, $value, $fail) use ($request,$cusRes){
                                                if(strtotime($value)!=strtotime($cusRes->recurring_start_date)){

                                                    /*if (!empty($value) && $request->service_type=="Same Day" && strtotime($value)==strtotime(date('Y-m-d'))){
                                                            $fail('Should be today date.');
                                                    }*/
                                                    if (!empty($value) && $request->service_type=="Same Day"){
                                                        $fail('You can not use reccuring collection with Same Day service');
                                                    }

                                                    if (!empty($value) && $request->service_type=="1 Day" && strtotime($value)< strtotime("+1 day",strtotime(date('Y-m-d')))){
                                                        $fail("Should select 1 date after today's date if service type is 1 day .");
                                                    }

                                                    if (!empty($value) && $request->service_type=="2 Day" && strtotime($value)< strtotime("+2 day",strtotime(date('Y-m-d')))){
                                                        $fail("Should select 2 date after today's date if service type is 2 day .");
                                                    }
                                                }
                                            }
                                        ],                    
                    // "reference"  =>  [
                    //                     'required',
                    //                     Rule::unique('customers','reference')->where(function ($query) use($firmId){
                    //                         return $query->where('firm_id', $firmId);
                    //                     })->ignore($cusRes->id)
                    //                 ],
                ];

                $validator = $this->validation($request->all(),$additionalValidation);
                                        
                if ($validator->fails())
                {
                    return Redirect::to('merchant/customers/update/'.encrypt($customerId))->withErrors($validator)->withInput();;
                }

                $customer = $this->customerSave($request,$cusRes);
                
                if($customer->save()){
                    Session::flash('status','Customer Updated successfully');
                    Session::flash('class','success');
                }else{
                     Session::flash('status','Unable to Update Customer! Please try again later');
                     Session::flash('class','danger');
                }
                return redirect('merchant/customers');
            }
        }else{
            Session::flash('status','Problem in fetching the record');
            Session::flash('class','danger');
            return redirect('merchant/customers');
        }
        $holidayData=PublicHolidays::where('holiday_date','>=',date('Y-m-d'))->get();
        $holidayDates=[];
        foreach ($holidayData as $key => $eachHoliday) {
            $holidayDates[]=Helper::convertDate($eachHoliday['holiday_date'],'m/d/Y');
        }
        return view('merchant.customers.customerUpdate',compact('pagename','userStatus','cusRes','holidayDates'));
    }

    public function deleteCustomer(Request $request){
        $requestStatus=['status'=>402,'message'=>'Problem in deleting the record',"type"=>"danger"];
        if($request->isMethod('delete')){
            $customerId=decrypt($request->customerId);
                      
            $cusRes = Customer::where('id',$customerId)->first();
            if($cusRes){
                $cusRes->is_deleted = 1;
                $cusRes->deleted_by = auth()->user()->id;
                $cusRes->deleted_at = date("Y-m-d H:i:s");
                
                if ($cusRes->save()) {
                    $requestStatus=['status'=>201,'message'=>'User Deleted Successfully',"type"=>"success"];
                    
                }    
            }
            
        }
        echo json_encode($requestStatus);
        //return redirect('merchant/users');
    }

    public function deleteMultipleCustomers(Request $request){
        if($request->isMethod('delete')){
            $i=0;
            foreach ($request->toDelete as $key => $eachUser) {
                //$customerId=decrypt($eachUser);
                $customerId=$eachUser;
                      
                $cusRes = Customer::where('id',$customerId)->first();
                if($cusRes){
                    $cusRes->is_deleted = 1;
                    $cusRes->deleted_by = auth()->user()->id;
                    $cusRes->deleted_at = date("Y-m-d H:i:s");
                    
                    if ($cusRes->save()) {
                        $i++;
                    }    
                }
            }

            Session::flash('status',$i.' Users Deleted Successfully');
            Session::flash('class','success');
            return redirect('merchant/customers');
            
        }
    }
    
    private function validation($request,$additionalValidation=[]){

        $firmId = auth()->user()->firm_id;
        $validationArr = [
                
                "first_name"            => 'required|no_special_char', 
                "last_name"             => 'required|no_special_char', 
                "contact_number"        => 'required|digits:10', 
                "id_number"             => 'required|digits:13',
                "service_type"          => [
                                           'required',
                                            Rule::in(Config('constants.serviceType'))
                                        ], 
                
                "bank_id"               => 'required|exists:bank_details,id',  
                "branch_code"           => 'required|no_special_char', 
                "account_type"          => [
                                           'required',
                                            Rule::in(Config('constants.collectionAccountType'))
                                           ], 
                
                "account_holder_name"   => 'required|no_special_char', 
                "account_number"        => 'required|no_special_char|integer|regex:/[0-9]+/', 
                "once_off_amount"       => [
                                                Rule::requiredIf(function () use ($request) {
                                                    if(empty($request['recurring_start_date']) && empty($request['recurring_amount']) && intval($request['recurring_amount'])==0){
                                                        return true;
                                                    }

                                                    if(!empty($request['collection_date'])){
                                                        return true;
                                                    }
                                                    return false;
                                                }),
                                            ],

                "recurring_amount"      => [
                                            Rule::requiredIf(function () use ($request) {
                                                        if(empty($request['collection_date']) && empty($request['once_off_amount']) && $request['once_off_amount']==0){
                                                            return true;
                                                        }

                                                        if(!empty($request['recurring_start_date'])){
                                                            return true;
                                                        }
                                                        return false;
                                                    }),
                                            ],

                "duration"              => [
                                            Rule::requiredIf(function () use ($request) {
                                                        return (intval($request['recurring_amount'])>0);
                                                    }),

                                            ],
                "debit_frequency"       => [
                                            Rule::requiredIf(function () use ($request) {
                                                return (intval($request['recurring_amount'])>0);
                                            }),
                                            
                                        ],  
        ];
        
        $validationArr=array_merge($validationArr,$additionalValidation);
        
        $validator = \Validator::make($request,$validationArr ,[
            'bank_id.required' => 'Please select bank'
        ]);

        $validator->sometimes(['collection_date'], 'date', function ($input) {
            return !empty($input->collection_date);
        });
        $validator->sometimes(['collection_date'], 'date_format:Y-m-d', function ($input){
            return !empty($input->collection_date);
        });

        $validator->sometimes(['duration'], 'regex:/[0-9]+/', function ($input) {
            return !empty($input->duration);
        });

        $validator->sometimes(['once_off_amount'], 'regex:/[0-9]+/', function ($input) {
            return !empty($input->once_off_amount);
        });
        if(isset($request->once_off_amount) && is_numeric($request->once_off_amount)){
            if(!empty($request['collection_date'])){
                $validator->sometimes(['once_off_amount'], 'gt:0', function ($input) {
                    return ($input->once_off_amount!="" || !is_null($input->once_off_amount));
                });
            }
        }

        
        
        if(isset($request->recurring_amount) && is_numeric($request->recurring_amount)){
            $validator->sometimes(['recurring_amount'], 'regex:/[0-9]+/', function ($input) {
                return !empty($input->recurring_amount);
            });
            
            $validator->sometimes(['recurring_amount'],'gt:0', function ($input) {
                return ($input->recurring_amount!="" || !is_null($input->recurring_amount));
            });  

            if(!empty($request['recurring_start_date'])){
                $validator->sometimes(['recurring_amount'],'gt:0', function ($input) {
                    return ($input->recurring_amount!="" || !is_null($input->recurring_amount));
                });
            }  
        }
        

        $validator->sometimes(['debit_frequency'],Rule::in(Config('constants.debitFrequency')), function ($input) {
            return !empty($input->recurring_amount);
        });
        
        
        
        
        return $validator;
    }

    private function customerSave($request,$customer){

        $customer->mandate_id            = $request->mandate_id;
        $customer->first_name            = $request->first_name; 
        $customer->last_name             = $request->last_name; 
        $customer->email                 = $request->email; 
        $customer->contact_number        = $request->contact_number; 
        $customer->id_number             = $request->id_number; 
        $customer->address_line_one      = $request->address_one; 
        $customer->address_line_two      = $request->address_two; 
        $customer->suburb                = $request->suburb; 
        $customer->city                  = $request->city; 
        $customer->province              = $request->province; 
        //$customer->reference             = $request->reference; 
        $customer->service_type          = $request->service_type;
        $customer->debit_frequency       = $request->debit_frequency;
        $customer->bank_id               = $request->bank_id; 
        $customer->branch_code           = $request->branch_code; 
        $customer->account_type          = $request->account_type;
        $customer->account_holder_name   = $request->account_holder_name; 
        $customer->account_number        = $request->account_number;
        $customer->once_off_amount       = $request->once_off_amount;
        $customer->status=1;

        if(auth()->user()->role_id==4){
            $customer->status = 0;
        }

        if(!empty($request->collection_date)){
            $customer->collection_date       = Helper::convertDate($request->collection_date,"Y-m-d");    
        }
        
        // recurring start date is not empty
        if(!empty($request->recurring_start_date)){
            $customer->recurring_start_date  = Helper::convertDate($request->recurring_start_date,"Y-m-d");

            /*
                if recurring start date is set to bigger/later then next collection amount. 
                then mark next collection date same as recurring_start_date
            */
            if((strtotime($customer->recurring_start_date)>strtotime($customer->next_collection_date)) || is_null($customer->next_collection_date)){
                $customer->next_collection_date=$customer->recurring_start_date;
            }
        }
        
        $customer->recurring_amount      = $request->recurring_amount;
        $customer->duration              = $request->duration;
        $customer->firm_id               = auth()->user()->firm_id;
        $customer->created_by            = auth()->user()->id;
        return $customer; 
    }

    public function tempList(){
        
        $pagename  = "Upload Customer List";
        $customers = TempCustomers::where('added_by',auth()->user()->id)->where('is_deleted',0)->get();
        $existingCustomers = Customer::select('mandate_id','reference')->get();
        $mandateArray = array();
        $referenceArray = array();
        foreach ($existingCustomers as $key => $customer) {
            array_push($mandateArray, $customer->mandate_id);
            array_push($referenceArray, $customer->reference);
        }
        return view('merchant.customers.temp-list',compact('customers','pagename','mandateArray','referenceArray'));
    }

    public function import(Request $request){
        if($request->file('file_name')!=''){
            $file = $request->file('file_name');             
            // File Details 
            $filename  = rand().'_'.$file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
              
            $tempPath = $file->getRealPath();
            $fileSize = $file->getSize();
            $mimeType = $file->getMimeType();

              // Valid File Extensions
            $valid_extension = array("csv","xls","xlsx");

              // 2MB in Bytes
            $maxFileSize = Config('constants.maxFileUploadSize'); 

              // Check file extension
            if(in_array(strtolower($extension),$valid_extension)){

                // Check file size
                if($fileSize <= $maxFileSize){

                    // File upload location
                    $location = public_path('uploads/customers');

                      // Upload file
                    $file->move($location,$filename);

                      // Import CSV to Database
                    $filepath = $location."/".$filename;

                      // Reading file
                    $file = fopen($filepath,"r");

                    $importData_arr = array();
                    $i = 0;

                    $dataArray = array("mandate_id","first_name","last_name","email","contact_number","id_number","address_one","address_line_two","suburb","city","province","reference",'service_type',"debit_frequency",'bank_name','account_type','branch_code','account_holder_name','account_number','once_off_amount','recurring_amount','collection_date','collection_end_date');

                    while (($filedata = fgetcsv($file, 10000, Helper::getCsvDelimiter($filepath))) !== FALSE) {

                        $dataRow=trim(implode('', $filedata));
                         if($dataRow==''){
                            continue; 
                         }
                         $num = count($filedata );
                         if($num!=sizeof($dataArray)){
                            break;
                         }
                         //Skip first row (Remove below comment if you want to skip the first row)
                         if($i == 0){
                            $i++;
                            continue; 
                         }
                         
                         
                         for ($c=0; $c < $num; $c++) {
                            if($dataArray[$c]=='service_type'){
                                $columnVal=strtolower($filedata[$c]);
                                if($columnVal=="same day"){
                                    $importData_arr[$i][$dataArray[$c]] = "Same Day";
                                }elseif($columnVal=="1 day"){
                                    $importData_arr[$i][$dataArray[$c]] = "1 Day";
                                }elseif($columnVal=="2 day"){
                                    $importData_arr[$i][$dataArray[$c]] = "2 Day";
                                }else{
                                    $importData_arr[$i][$dataArray[$c]] = $filedata [$c];
                                }
                            }else{
                                $importData_arr[$i][$dataArray[$c]] = $filedata [$c];
                            }
                            
                        }
                        $i++;
                    }
                    fclose($file); 
                    Helper::deleteDir($location);


                    // Insert to MySQL database
                    foreach($importData_arr as $key => $importData){
                            $importData['account_type']=Helper::strializeAccountType($importData['account_type']);
                            $validator = $this->csvValidation($importData);
                            if ($validator->fails()){

                                $tempemp           = new TempCustomers();
                                $dataset           = json_encode($importData);
                            
                                $errorset           = json_encode($validator->errors()->keys());
                                $tempemp->dataset   = $dataset;
                                $tempemp->errorset  = $errorset;
                                $tempemp->file_name = $filename;
                                $tempemp->added_by  = auth()->user()->id;
                                $tempemp->save();
                            }else{
                                $customer = $this->saveCSVdata($importData);
                                $customer->save();
                            }
                    }
                    
                    Session::flash('status','Import Successful.');
                    Session::flash('class','success');
                    return redirect('merchant/customers/temp/list');
                }else{
                  Session::flash('status','File too large. File must be less than 2MB.');
                }
            }else{
              Session::flash('status','Invalid File Extension.');
            }
        }else{
            Session::flash('status','File must be selected.');
            
        } 
        Session::flash('class','danger');
        return redirect('merchant/customers/temp/list');
    }

    private function csvValidation($request){
            
           
            $firmId = auth()->user()->firm_id;
            $request["account_type"] = strtolower($request["account_type"]);
            $validator = \Validator::make($request, [
                'mandate_id'         => [
                                           'required',
                                            Rule::unique('customers','mandate_id')->where(function ($query) use($firmId){
                                                return $query->where('firm_id', $firmId);
                                            })
                                        ],
                "service_type"       => [
                                           'required',
                                            Rule::in(Config('constants.serviceType'))
                                        ], 
                
                "first_name"          => 'required', 
                "last_name"           => 'required', 
                "email"               => 'required|email', 
                "id_number"           =>  [
                                           'required',
                                           'digits:13',
                                            Rule::unique('customers','id_number')->where(function ($query) use($firmId) {
                                               return $query->where('firm_id', $firmId);
                                            })
                                        ],
                "contact_number"    =>  'required','digits:10', 
                // "reference"         =>  [
                //                            'required',
                //                             Rule::unique('customers','reference')->where(function ($query) use($firmId){
                //                                 return $query->where('firm_id', $firmId);
                //                             })
                //                         ],
                "bank_name"           => 'required|exists:bank_details,bank_name',
                "account_type"        => [  'required',
                                                 Rule::in(Config('constants.collectionAccountType'))
                                             ],//'required|in:saving,cheque', 
                "account_number"      => 'required|integer|regex:/[0-9]+/', 
                "branch_code"         => 'required',
                //"collection_date"     => 'required|date|after:' . date('Y-m-d') . '',
                "collection_date"     => [
                            //'required',
                                        Rule::requiredIf(function () use ($request) {
                                                return (empty($request['recurring_start_date']) || $request['once_off_amount']>0);
                                        }),
                                        function ($attribute, $value, $fail) use ($request){
                                            if (!empty($value) && $request['service_type']=="Same Day" && strtotime($value)!=strtotime(date('Y-m-d'))){
                                                    $fail('Should be todays date');
                                            }
                                            if (!empty($value) && $request['service_type']=="1 Day" && strtotime($value)< strtotime("+1 day",strtotime(date('Y-m-d')))){
                                                $fail("Should select 1 date after today's date if service type is 1 day .");
                                            }
                                            if (!empty($value) && $request['service_type']=="2 Day" && strtotime($value)< strtotime("+2 day",strtotime(date('Y-m-d')))){
                                                $fail("Should select 2 date after today's date if service type is 2 day .");
                                            }
                                        }
                                    ],
                "once_off_amount"     => [
                                            Rule::requiredIf(function () use ($request) {
                                                        return (empty($request['recurring_amount']) && intval($request['recurring_amount'])==0);
                                                    }),
                                        ],

                "recurring_amount"    => [
                                            Rule::requiredIf(function () use ($request) {
                                                        return (empty($request['once_off_amount']) && intval($request['once_off_amount'])==0);
                                                    }),
                                            ],
                
                "collection_end_date"     => [
                            //'required',
                                        Rule::requiredIf(function () use ($request) {
                                                return ($request['recurring_amount']>0);
                                        }),
                                        function ($attribute, $value, $fail) use ($request){
                                            
                                            // if (!empty($value)  && strtotime($value)< strtotime("+1 day",strtotime(date('Y-m-d')))){
                                            //     $fail("Should greater then todays date");
                                            // }
                                            
                                        }
                                    ], 
                 //"collection_end_date"     => 'required|date|after:' . date('Y-m-d') . '',

            ]);
            return $validator;
    }

    private function saveCSVdata($request){

          
        $collection_start_date = strtotime($request["collection_date"]);

        if($request["collection_end_date"]!=''){
              $collection_end_date   = strtotime($request["collection_end_date"]);  

              $year1 = date('Y', $collection_start_date);
              $year2 = date('Y', $collection_end_date);

              $month1 = date('m', $collection_start_date);
              $month2 = date('m', $collection_end_date);
              $diff = (($year2 - $year1) * 12) + ($month2 - $month1);
        }else{
            $diff = 0;
        } 

       
          $bank_details = BankDetails::where('bank_name',trim($request['bank_name']))->first();
          $customer                         =   new Customer();
          $customer->mandate_id             =   $request['mandate_id'];
          $customer->first_name             =   $request['first_name'];
          $customer->last_name              =   $request['last_name'];
          $customer->email                  =   $request['email'];
          $customer->contact_number         =   $request['contact_number'];
          $customer->id_number              =   $request['id_number'];
          $customer->address_line_one       =   $request['address_one'];
          $customer->address_line_two       =   $request['address_line_two']; 
          $customer->suburb                 =   $request['suburb'];
          $customer->city                   =   $request['city'];
          $customer->province               =   $request['province'];
          //$customer->reference              =   $request['reference'];
          $customer->service_type           =   $request['service_type'];
          $customer->debit_frequency        =   $request['debit_frequency'];
          $customer->bank_id                =   $bank_details->id;
          $customer->account_type           =   $request['account_type'];
          $customer->branch_code            =   $request['branch_code'];
          $customer->account_holder_name    =   $request['account_holder_name'];
          $customer->account_number         =   $request['account_number'];
          $customer->once_off_amount        =   $request['once_off_amount'];
          $customer->recurring_amount       =   intval($request['recurring_amount']);
         
          $customer->collection_date        =   date('Y-m-d',strtotime($request['collection_date']));
          if(empty($request['collection_end_date']) || is_null($request['collection_end_date'])){
            $customer->collection_end_date    = null;
          }else{
            $customer->collection_end_date    =   date('Y-m-d',strtotime($request['collection_end_date']));
          }
          
          $customer->duration               =   $diff;
          $customer->firm_id                =   auth()->user()->firm_id;
          $customer->created_by             =   auth()->user()->id;
          if(is_null($customer->collection_end_date)){
            $customer->next_collection_date   =   null;
          }else{
            $customer->next_collection_date   =   date('Y-m-d',strtotime($request['collection_date']));
            }
          return $customer;
    }

    public function editTempCustomer(Request $request){

        $errors = array();
        $request = json_decode($request->data, true);
        $validator = $this->csvValidation($request);
        if ($validator->fails()){
            $errorset           = json_encode($validator->errors());
            return \Response::json(array("errors" => $validator->getMessageBag()->toArray()));
            $errors = $validator->getMessageBag()->toArray();
        }else{
            
            $request['id_number'] = (float) $request['id_number'];
            $customer = $this->saveCSVdata($request);
            $customer->save();

            $id       = decrypt($request["id"]);
            $customer = TempCustomers::where(['id' => $id,'added_by' => auth()->user()->id])->delete();
            Session::flash('status','Customer updated Successfully.');
            Session::flash('class','success');
        }
        return \Response::json(array("errors" => $errors));
    }

    public function editMultipleTempCustomer(Request $request){

        $status = true;
        $errors = array();
        $dataArray = json_decode($request->data, true);
        $i = $j = 0;
        foreach($dataArray as $request){
            
            $validator = $this->csvValidation($request);
            if ($validator->fails()){
                $errorset           = json_encode($validator->errors());
                $status = false;
                $errors[] = $validator->getMessageBag()->toArray();
                $i++;
            }else{
                
                $customer = $this->saveCSVdata($request);
                $customer->save();
                $id       = decrypt($request["id"]);
                $customer = TempCustomers::where(['id' => $id,'added_by' => auth()->user()->id])->delete();
                $status = false;
                $j++;
            }
        }
        if($i>0){
            Session::flash('error-msg',$i.' Records failed some validation.');
        }
        if($j>0){
            Session::flash('success-message', $j.' Import Successful.');
        }
        return \Response::json(array("errors" => $errors,"status" => $status));
    }

    public function samplecsvDownload(){

        $file    = public_path(). "/uploads/Collections_template.csv";
        $headers = array(
                'Content-Type: application/csv',
        );
        return Response::download($file,'samplecustomerfile.csv',$headers);
    }

    public function deleteTempList(Request $request){

        if($request->isMethod('get')){
            $delete = TempCustomers::where('added_by',auth()->user()->id)->delete();
            if($delete){
                Session::flash('status','Customer deleted successfully');
                Session::flash('class','success');
            }else{
                Session::flash('status','Problem in deleting the record');
                Session::flash('class','dander');
            }
        }else{
            Session::flash('status','Problem in deleting the record');
            Session::flash('class','dander');
        }
        return redirect('merchant/customers/temp/list');
    }

    public function tempCustomerDelete(Request $request,$id){

        if($request->isMethod('delete')){
            
            $id = decrypt($id);               
            $customer = TempCustomers::where('id',$id)->delete();
            if ($customer) {
                Session::flash('status','Customer deleted successfully');
                Session::flash('class','success');
            }else{
                Session::flash('status','Problem in deleting the record');
                Session::flash('class','danger');
            }
        }else{
           Session::flash('status','Sorry Your request Can not be processed');
           Session::flash('class','danger');
           
        }
        return redirect('merchant/customers/temp/list');
    }

    public function updateStatus(Request $request){
        
        $requestStatus=['status'=>402,'message'=>'Problem in deleting the record',"type"=>"danger"];
        if($request->isMethod('post')){
            $customerId = $request->customerId;          
            $cusRes = Customer::where(['id' => $customerId,'firm_id' => auth()->user()->firm_id])->first();

            if($cusRes){
                $cusRes->status                 = $request->status;

                
                //if customer has to be approved and it of Same day
                if ($cusRes->status==1 && $cusRes->service_type=="Same Day"){
                    //check if collection is menat for today only, and bankingcutoff time should be greater then currenttime
                    if($cusRes->collection_date!=date('Y-m-d') || Config('constants.bankingCutOffTime')<Helper::getSastTime()){
                        $requestStatus=['status'=>402,'message'=>'Cut-Off is reached for Same Day Service, try another Service!',"type"=>"danger"];
                    }
                }else{
                    $cusRes->approved_or_caceled_by = auth()->user()->id;
                    if ($cusRes->save()) {
                        $requestStatus=['status'=>201,'message'=>'Status Updated Successfully',"type"=>"success"];
                    }        
                }
                
                
            }
        }
        echo json_encode($requestStatus);
    }

    public function approveAll(Request $request){

        if($request->isMethod('post')){
           
            $i=0;
            foreach ($request->toUpdate as $key => $eachUser) {
                //$customerId=decrypt($eachUser);
                $customerId=$eachUser;
                      
                $cusRes = Customer::where('id',$customerId)->first();
                if($cusRes){
                    $cusRes->status = 1;
                    if ($cusRes->save()) {
                        $i++;
                    }    
                }
            }

            Session::flash('status',$i.' Customers Approved Successfully');
            Session::flash('class','success');
            return redirect('merchant/customers');
        }
    }

}
