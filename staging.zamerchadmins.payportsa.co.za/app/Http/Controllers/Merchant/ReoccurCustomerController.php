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
use App\Model\{Firm,BankDetails,Role,CompanyInformation,Employees,Customer,TempCustomers,PublicHolidays,ProfileLimits};
//use Maatwebsite\Excel\Facades\Excel;
use Response;

class ReoccurCustomerController extends Controller
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
        return view('merchant.reoccur-customer.customerList',compact('pagename'));
        
        
    }

    private function capturerDtColumns(){
        $columns = array(
            array( 'db' => 'mandate_id', 'dt' => 0 ),
            array( 'db' => 'first_name', 'dt' => 1 ),
            array( 'db' => 'last_name',  'dt' => 2 ),
            array( 'db' => 'contact_number',     'dt' => 3 ),
            
            array(
                'dbAlias'   => 'bank_details',
                'db'        => 'bank_name',
                'dt'        => 4
            ),
            array('dbAlias'   => 'customers', 'db' => 'branch_code',     'dt' => 5 ),
            array( 'db' => 'account_number',     'dt' => 6 ),
            array( 'db' => 'once_off_amount',     'dt' => 7 ),
            array( 'dbAlias'=>'customers',
                    'db' => 'collection_date',
                    'dt' => 8,
                    'formatter' => function( $d, $row ) {
                        return Helper::convertDate($row['collection_date'],'d-m-Y');
                    }
                ),
            array( 'db' => 'recurring_amount',     'dt' => 9 ),
            array( 'dbAlias'=>'customers',
                    'db' => 'next_collection_date',
                    'dt' => 10,
                    'formatter' => function( $d, $row ) {
                        return Helper::convertDate($row['next_collection_date'],'d-m-Y');
                    }
                ),
            array(
                'dbAlias'=>'customers',
                'number'=>true,
                'db'        => 'status',
                'dt'        => 11,
                'formatter' => function( $d, $row ) {
                    return Helper::getCustomerStatusTitle($d);
                }
            ),
            array(
                
                'dt'        => 12,
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
        
        $columns = $this->capturerDtColumns();
        
        $firmId = auth()->user()->firm_id;
        
        $bindings=['reoccur',1,2,$firmId,1];

        $whereConditions ="customers.cust_type=? and customers.status in (?,?) and firm_id=? and (is_deleted!=? or is_deleted is null)";
        $totalCount = DB::table('customers')
                ->selectRaw('count('.$primaryKey.') totCount')
                ->whereRaw($whereConditions, $bindings)
                ->get()
                ->toArray();

        $dtWhere=DatatableHelper::filter ( $request, $columns,$bindings);
        if($dtWhere!==""){
            $whereConditions.=" and ".$dtWhere;
        }
        //$orderBy ='customers.created_at DESC, '. DatatableHelper::order ( $request, $columns );
        //$orderBy ='customers.created_at DESC, '. DatatableHelper::order ( $request, $columns );
        $orderBy="";
        if(!empty(DatatableHelper::order ( $request, $columns ))){
            $orderBy=DatatableHelper::order ( $request, $columns ).",";
        }

        $orderBy .=' customers.created_at DESC';

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
        $pagename = "Collections - New Reccur Customer";
        if($request->isMethod('post')){
            $offsetDay=Config('constants.reocurTwoDayCalOffset');
            if(Helper::getSastTime()>=config('constants.bankingCutOffTime')){
                $offsetDay++;
            }
            $offsetDay=Helper::businessDayOffset($offsetDay);
            //echo $request->recurring_start_date;
            
            
            $additionalValidation=[
                
                "mandate_id" =>    [
                                    'required',
                                    'without_spaces',
                                    'no_special_char',
                                    Rule::unique('customers')->where(function ($query) use ($firmId) {
                                        return $query->where('firm_id',$firmId)->where('cust_type','reoccur');
                                    })
                                    ],
                "collection_date"=> [
                            //'required',
                                        Rule::requiredIf(function () use ($request) {
                                                return (empty($request['recurring_start_date']) || $request['once_off_amount']>0);
                                        }),
                                        function ($attribute, $value, $fail) use ($request,$offsetDay){

                                            if (!empty($value) && strtotime($value)< strtotime("+".$offsetDay." day",strtotime(date('Y-m-d')))){
                                                $fail("Collection date should be after atleast ".$offsetDay." working days");
                                            }
                                        }
                                    ],
                "recurring_start_date"=>[
                                            Rule::requiredIf(function () use ($request) {
                                                return (empty($request['collection_date']) || intval($request['recurring_amount'])>0);
                                            }),
                                            function ($attribute, $value, $fail) use ($request,$offsetDay){
                                                
                                                if (!empty($value) && strtotime($value)< strtotime("+".$offsetDay." day",strtotime(date('Y-m-d')))){
                                                    $fail("Reccuring Collection date should be after atleast ".$offsetDay." working days");
                                                }
                                            }
                                        ]
                
            ];
            $validator = $this->validation($request->all(),$additionalValidation);
        

            if ($validator->fails()){
                return Redirect::to('merchant/collection/reoccur/customer/create')->withErrors($validator)->withInput();
            }

            $customer = new Customer();
            $customer = $this->customerSave($request,$customer);
            $customer->status=0;
            if($request->duration!=''){

                $customer->collection_end_date   = Helper::getCollectionEndDate($customer->recurring_start_date,$customer->debit_frequency,$customer->duration);
            }
            
            $data = [
                'template'  => 'payportUserInvite',
                'subject'   => "Your account is created.",
                'to'         => $customer
            ];

            if($customer->save()){
                //$status = Helper::sendInviteMail($data);
                Session::flash('status','Customer details submitted, please Approve');
                Session::flash('class','success');
            }else{
                 Session::flash('status','Unable to create Customer! Please try again later');
                 Session::flash('class','danger');
            }
            return redirect('merchant/collection/reoccur/customers');
        }
        
        $holidayData=PublicHolidays::where('holiday_date','>=',date('Y-m-d'))->get();
        $holidayDates=[];
        foreach ($holidayData as $key => $eachHoliday) {
            $holidayDates[]=Helper::convertDate($eachHoliday['holiday_date'],'m/d/Y');
        }
        return view('merchant.reoccur-customer.create',compact('pagename','holidayDates'));
    }

    public function updateCustomer(Request $request){
        
        $customerId   = decrypt($request->id);
        $pagename = "Collections - Update Reccur Customer";

        $userStatus=config('constants.userStatus');
        $firmId=Auth()->user()->firm_id;

        if($customerId){

            $cusRes = Customer::where(['firm_id'=>$firmId,'id'=>$customerId])->first();
            if(empty($cusRes)){
                Session::flash('status','Requested record not found or you are not having permission to access that!');
                Session::flash('class','warning');
                return redirect('merchant/collection/reoccur/customers');    
            }

            if($request->isMethod('post')){
                // ,
                //             Rule::unique('customers')->where(function ($query) use ($firmId) {
                //                return $query->where('firm_id',$firmId);
                //             })->ignore($cusRes->id)
                $offsetDay=Config('constants.reocurTwoDayCalOffset');
                if(Helper::getSastTime()>=config('constants.bankingCutOffTime')){
                    $offsetDay++;
                }
                $offsetDay=Helper::businessDayOffset($offsetDay);

                $additionalValidation=[
                    
                    "mandate_id"=>  [
                        'required',
                        'without_spaces',
                        'no_special_char',
                        Rule::unique('customers')->where(function ($query) use ($firmId) {
                            return $query->where('firm_id',$firmId)->where('cust_type','reoccur');
                        })->ignore($cusRes->id)
                    ],
                    "status"=>  [
                        'required',
                        'without_spaces',
                        'no_special_char',
                        Rule::in([1,2])
                    ],
                    "collection_date"=> [
                            //'required',
                                            Rule::requiredIf(function () use ($request) {
                                                    return (empty($request['recurring_start_date']) || $request['once_off_amount']>0);
                                            }),
                                            function ($attribute, $value, $fail) use ($request,$cusRes,$offsetDay){
                                                if(strtotime($value)!=strtotime($cusRes->collection_date)){
                                                    
                                                    if (!empty($value)  && strtotime($value)< strtotime("+$offsetDay day",strtotime(date('Y-m-d')))){
                                                        //$fail("Should select 2 date after today's date if service type is 2 day .");
                                                        $fail("Collection date should be after atleast ".$offsetDay." working days");
                                                    }
                                                }
                                                
                                            }
                                        ],
                    "recurring_start_date"=>[
                                                Rule::requiredIf(function () use ($request) {
                                                    return (empty($request['collection_date']) || intval($request['recurring_amount'])>0);
                                                }),
                                                function ($attribute, $value, $fail) use ($request,$cusRes,$offsetDay){
                                                    if(strtotime($value)!=strtotime($cusRes->recurring_start_date)){
                                                        
                                                        if (!empty($value) && strtotime($value)< strtotime("+$offsetDay day",strtotime(date('Y-m-d')))){
                                                            $fail("Reoccring Collection date should be after atleast ".$offsetDay." working days");
                                                        }
                                                    }
                                                }
                                            ]
                ];

                $validator = $this->validation($request->all(),$additionalValidation);
                                        
                if ($validator->fails())
                {

                    return Redirect::to('merchant/collection/reoccur/customer/update/'.encrypt($customerId))->withErrors($validator)->withInput();;
                }

                $customer = $this->customerSave($request,$cusRes);
                if($request->duration!=''){
                    $customer->collection_end_date   = Helper::getCollectionEndDate($customer->recurring_start_date,$customer->debit_frequency,$customer->duration);
                }
                if($customer->save()){
                    Session::flash('status','Customer Updated successfully');
                    Session::flash('class','success');
                }else{
                     Session::flash('status','Unable to Update Customer! Please try again later');
                     Session::flash('class','danger');
                }
                return redirect('merchant/collection/reoccur/customers');
            }
        }else{
            Session::flash('status','Problem in fetching the record');
            Session::flash('class','danger');
            return redirect('merchant/collection/reoccur/customers');
        }
        $holidayData=PublicHolidays::where('holiday_date','>=',date('Y-m-d'))->get();
        $holidayDates=[];
        foreach ($holidayData as $key => $eachHoliday) {
            $holidayDates[]=Helper::convertDate($eachHoliday['holiday_date'],'m/d/Y');
        }
        return view('merchant.reoccur-customer.customerUpdate',compact('pagename','userStatus','cusRes','holidayDates'));
    }
    public function viewCustomer(Request $request){
        
        $customerId   = decrypt($request->id);
        $pagename = "Collections - Update Reccur Customer";

        $userStatus=config('constants.userStatus');
        $firmId=Auth()->user()->firm_id;

        if($customerId){

            $cusRes = Customer::where(['firm_id'=>$firmId,'id'=>$customerId])->first();
            if(empty($cusRes)){
                Session::flash('status','Requested record not found or you are not having permission to access that!');
                Session::flash('class','warning');
                return redirect('merchant/collection/reoccur/customers');    
            }

            
        }else{
            Session::flash('status','Problem in fetching the record');
            Session::flash('class','danger');
            return redirect('merchant/collection/reoccur/customers');
        }
        
        return view('merchant.reoccur-customer.customerView',compact('pagename','userStatus','cusRes'));
    }
    private function validation($request,$additionalValidation=[]){

        $firmId = auth()->user()->firm_id;
        $validationArr = [
                
                "first_name"            => 'required|no_special_char', 
                "last_name"             => 'required|no_special_char', 
                
                //"id_number"             => 'required|digits:13',
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
                // "action_date_choice"          => [
                //                            'required',
                //                             Rule::in(['pre','post'])
                //                            ], 
        ];
        $firmId=auth()->user()->firm_id;
        $profileLimits  = ProfileLimits::where(['firm_id' => $firmId])->first();


        $validationArr=array_merge($validationArr,$additionalValidation);
        
        $validator = \Validator::make($request,$validationArr ,[
            'bank_id.required' => 'Please select bank'
        ]);

        $validator->sometimes(['email'], 'email', function ($input) {
            return !empty($input->email);
        });

        $validator->sometimes(['contact_number'], 'digits:10', function ($input) {
            return !empty($input->contact_number);
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
        if(isset($request['once_off_amount']) && is_numeric($request['once_off_amount'])){
            if(!empty($request['collection_date'])){
                $validator->sometimes(['once_off_amount'], 'gt:0', function ($input) {
                    return ($input->once_off_amount!="" || !is_null($input->once_off_amount));
                });
            }
            
            
            $validator->sometimes(['once_off_amount'], 'max:'.$profileLimits->line_collection, function ($input) {
                return ($input->once_off_amount!="" || !is_null($input->once_off_amount));
            });
        }

        $validator->sometimes(['recurring_amount'], 'regex:/[0-9]+/', function ($input) {
            return !empty($input->recurring_amount);
        });
        
        if(isset($request['recurring_amount']) && is_numeric($request['recurring_amount'])){

            if(!empty($request['recurring_start_date'])){
                $validator->sometimes(['recurring_amount'],'gt:0', function ($input) {
                    return ($input->recurring_amount!="" || !is_null($input->recurring_amount));
                });
            }

                
            $validator->sometimes(['recurring_amount'],'max:'.$profileLimits->line_collection, function ($input) {
                return ($input->recurring_amount!="" || !is_null($input->recurring_amount));
            }); 
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
        //$customer->service_type          = $request->service_type;
        $customer->service_type          = '2 Day';
        $customer->cust_type          = 'reoccur';
        
        $customer->debit_frequency       = $request->debit_frequency;
        $customer->bank_id               = $request->bank_id; 
        $customer->branch_code           = $request->branch_code; 
        $customer->account_type          = $request->account_type;
        $customer->account_holder_name   = $request->account_holder_name; 
        $customer->account_number        = $request->account_number;
        $customer->once_off_amount       = $request->once_off_amount;
        //$customer->action_date_choice       = $request->action_date_choice;
        

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
            //if((strtotime($customer->recurring_start_date)>strtotime($customer->next_collection_date)) || is_null($customer->next_collection_date)){
            if((strtotime($customer->recurring_start_date)>strtotime(date('Y-m-d'))) || is_null($customer->next_collection_date)){
                
                
                //$customer->next_collection_date=$customer->recurring_start_date;
                $customer->next_collection_date=Helper::getPaymentDate(["SAME DAY"],$request->recurring_start_date);
            }
        }
        
        $customer->recurring_amount      = $request->recurring_amount;
        $customer->duration              = $request->duration;
        $customer->firm_id               = auth()->user()->firm_id;
        $customer->created_by            = auth()->user()->id;
        return $customer; 
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
            return redirect('merchant/collection/reoccur/customers');
            
        }
    }

    public function tempList(){
        
        $pagename  = "Upload Customer List";
        $customers = TempCustomers::where('added_by',auth()->user()->id)->where('is_deleted',0)->where('cust_type','reoccur')->get();
        $existingCustomers = Customer::select('mandate_id','reference')->get();
        $mandateArray = array();
        $referenceArray = array();
        foreach ($existingCustomers as $key => $customer) {
            array_push($mandateArray, $customer->mandate_id);
            array_push($referenceArray, $customer->reference);
        }
        return view('merchant.reoccur-customer.temp-list',compact('customers','pagename','mandateArray','referenceArray'));
    }

    public function sampleCsvDownload(){

        $file    = public_path(). "/uploads/recurring-collections-template.csv";
        $headers = array(
                'Content-Type: application/csv',
        );
        return Response::download($file,'recurring-collections-template.csv',$headers);
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

                    $dataArray = array("mandate_id","first_name","last_name","email","contact_number","id_number","address_one","address_line_two","suburb","city","province",'bank_name','account_type','branch_code','account_holder_name','account_number','once_off_amount','collection_date','recurring_amount','recurring_start_date',"debit_frequency",'duration');

                    $importData_arr=Helper::prepareCsvData($filepath,$dataArray,1); 
                    Helper::deleteDir($location);
                    // Insert to MySQL database
                    foreach($importData_arr as $key => $importData){
                            $importData['account_type']=Helper::strializeAccountType($importData['account_type']);
                            $validator = $this->csvValidation($importData);
                            
                            if ($validator->fails()){

                                $tempemp           = new TempCustomers();
                                $dataset           = json_encode($importData);
                                $errorset           = json_encode($validator->errors()->keys());
                                $tempemp->cust_type = 'reoccur';
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
                    return redirect('merchant/collection/reoccur/customer/upload');
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
        return redirect('merchant/collection/reoccur/customer/upload');
    }
    private function csvValidation($request){
            
           
            $firmId = auth()->user()->firm_id;
            //$request["account_type"] = $request["account_type"];
            $offsetDay=Config('constants.reocurTwoDayCalOffset');
            if(Helper::getSastTime()>=config('constants.bankingCutOffTime')){
                $offsetDay++;
            }
            $offsetDay=Helper::businessDayOffset($offsetDay);

            $validator = \Validator::make($request, [
                'mandate_id'         => [
                                           'required',
                                            Rule::unique('customers','mandate_id')->where(function ($query) use($firmId){
                                                return $query->where('firm_id', $firmId)->where('cust_type','reoccur');
                                            }),
                                            'without_spaces',
                                            'no_special_char',
                                        ],
                "first_name"          => 'required|no_special_char', 
                "last_name"           => 'required|no_special_char', 
                
                // "id_number"           =>  [
                //                            'required',
                //                            'digits:13'
                //                         ],
                "bank_name"           => 'required|exists:bank_details,bank_name',
                "branch_code"           => 'required|no_special_char',
                "account_type"          => [
                                           'required',
                                            Rule::in(Config('constants.collectionAccountType'))
                                           ],  
                "account_holder_name"   => 'required|no_special_char', 
                "account_number"        => 'required|no_special_char|integer|regex:/[0-9]+/', 
                "collection_date"     => [
                                        Rule::requiredIf(function () use ($request) {
                                                return (empty($request['recurring_start_date']) || $request['once_off_amount']>0);
                                        }),
                                        function ($attribute, $value, $fail) use ($request,$offsetDay){
                                            $value=Helper::convertDate($value);
                                            if (!empty($value) && strtotime($value)< strtotime("+".$offsetDay." day",strtotime(date('Y-m-d')))){
                                                $fail("Collection date should be after atleast ".$offsetDay." working days");
                                            }
                                        }
                                    ],
                "once_off_amount"     => [
                                            Rule::requiredIf(function () use ($request) {
                                                if(empty($request['recurring_start_date']) && empty($request['recurring_amount']) && intval($request['recurring_amount'])==0){
                                                    return true;
                                                }

                                                if(!empty($request['collection_date'])){
                                                    return true;
                                                }
                                                return false;
                                            })
                                        ],

                "recurring_amount"    => [
                                            Rule::requiredIf(function () use ($request) {
                                                if(empty($request['collection_date']) && empty($request['once_off_amount']) && $request['once_off_amount']==0){
                                                    return true;
                                                }

                                                if(!empty($request['recurring_start_date'])){
                                                    return true;
                                                }
                                                return false;
                                            })
                                        ],
                "recurring_start_date"=>[
                                            Rule::requiredIf(function () use ($request) {
                                                return (empty($request['collection_date']) || intval($request['recurring_amount'])>0);
                                            }),
                                            function ($attribute, $value, $fail) use ($request,$offsetDay){
                                                
                                                $value=Helper::convertDate($value);
                                                if (!empty($value) && strtotime($value)< strtotime("+".$offsetDay." day",strtotime(date('Y-m-d')))){
                                                    
                                                    $fail("Reocuring Collection date should be after atleast ".$offsetDay." working days");
                                                }
                                            }
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
                
                
            ]);
            $validator->sometimes(['email'], 'email', function ($input) {
                return !empty($input->email);
            });
            $validator->sometimes(['contact_number'], 'digits:10', function ($input) {
                return !empty($input->contact_number);
            });

            $validator->sometimes(['duration'], 'regex:/[0-9]+/', function ($input) {
                return (!empty($input->duration));
            });

            $validator->sometimes(['once_off_amount'], 'regex:/[0-9]+/', function ($input) {
                return !empty($input->once_off_amount);
            });
            
            $firmId=auth()->user()->firm_id;
            $profileLimits  = ProfileLimits::where(['firm_id' => $firmId])->first();
            
            if(isset($request['once_off_amount']) && is_numeric($request['once_off_amount'])){
                $validator->sometimes(['once_off_amount'], 'gt:0', function ($input) {
                    return ($input->once_off_amount!="" || !is_null($input->once_off_amount));
                });


                $validator->sometimes(['once_off_amount'], 'max:'.$profileLimits->line_collection, function ($input) {
                    return ($input->once_off_amount!="" || !is_null($input->once_off_amount));
                });
            }

            $validator->sometimes(['recurring_amount'], 'regex:/[0-9]+/', function ($input) {
                return !empty($input->recurring_amount);
            });
            
            if(isset($request['recurring_amount']) && is_numeric($request['recurring_amount'])){
                $validator->sometimes(['recurring_amount'],'gt:0', function ($input) {
                    return ($input->recurring_amount!="" || !is_null($input->recurring_amount));
                });    

                $validator->sometimes(['recurring_amount'],'max:'.$profileLimits->line_collection, function ($input) {
                    return ($input->recurring_amount!="" || !is_null($input->recurring_amount));
                });    
            }
            

            
            return $validator;
    }
    public function tempCustomerDelete(Request $request,$id){

        if($request->isMethod('delete')){
            
            $id = decrypt($id);               
            $customer = TempCustomers::where('id',$id)->where('added_by',auth()->user()->id)->delete();
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
        return redirect('merchant/collection/reoccur/customer/upload');
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
        return redirect('merchant/collection/reoccur/customer/upload');
    }

    public function editTempCustomer(Request $request){
        //die('vv');
        $errors = array();
        
        $request = json_decode($request->data,true);
        //echo $request['account_type'];
        $validator = $this->csvValidation($request);
        if ($validator->fails()){
            $errorset           = json_encode($validator->errors());
            return \Response::json(array("errors" => $validator->getMessageBag()->toArray()));
            $errors = $validator->getMessageBag()->toArray();
        }else{
            
            $request['id_number'] = (float) $request['id_number'];
            // $customer = $this->saveCSVdata($request);
            // $customer->save();
            
            $customer = $this->saveCSVdata($request);
            $customer->save();
            $id       = decrypt($request["id"]);
            TempCustomers::where(['id' => $id,'added_by' => auth()->user()->id])->delete();
            Session::flash('status','Customer updated Successfully.');
            Session::flash('class','success');
        }
        return \Response::json(array("errors" => $errors));
    }

    private function saveCSVdata($request){
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
          $customer->service_type          = '2 Day';
          $customer->cust_type          = 'reoccur';
          $customer->debit_frequency        =   $request['debit_frequency'];
          $customer->bank_id                =   $bank_details->id;
          $customer->account_type           =   $request['account_type'];
          $customer->branch_code            =   $request['branch_code'];
          $customer->account_holder_name    =   $request['account_holder_name'];
          $customer->account_number         =   $request['account_number'];
          $customer->once_off_amount        =   $request['once_off_amount'];
          $customer->recurring_amount       =   intval($request['recurring_amount']);

        if(!empty($request['collection_date'])){
            $customer->collection_date       = Helper::convertDate($request['collection_date'],"Y-m-d");    
        }
        // recurring start date is not empty
        if(!empty($request['recurring_start_date'])){
            $customer->recurring_start_date  = Helper::convertDate($request['recurring_start_date'],"Y-m-d");
            //$customer->next_collection_date=$customer->recurring_start_date;
            $customer->next_collection_date=Helper::getPaymentDate(["SAME DAY"],$customer->recurring_start_date);
        }
        



        $customer->duration              = $request['duration'];
        if(!empty($customer->duration)){
            $customer->collection_end_date   = Helper::getCollectionEndDate($customer->recurring_start_date,$customer->debit_frequency,$customer->duration);
        }
        
        $customer->firm_id               = auth()->user()->firm_id;
        $customer->created_by            = auth()->user()->id;
        return $customer; 
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

    public function pendingList()
    {     
        $pagename  = "Pending Customer List";
        return view('merchant.reoccur-customer.pendingCustomerList',compact('pagename'));
        
        
    }

    private function pendingListDtColumns(){
        $columns = array(
            array( 'dt' => 0,
                'formatter' => function( $d, $row ) {
                    return encrypt($row['id']);
                    //return $row['id'];
                }
            ),
            array( 'db' => 'mandate_id', 'dt' => 1 ),
            array( 'db' => 'first_name', 'dt' => 2 ),
            array( 'db' => 'last_name',  'dt' => 3 ),
            array( 'db' => 'contact_number',     'dt' => 4 ),
            
            array(
                'dbAlias'   => 'bank_details',
                'db'        => 'bank_name',
                'dt'        => 5
            ),
            array('dbAlias'   => 'customers', 'db' => 'branch_code',     'dt' => 6 ),
            array( 'db' => 'account_number',     'dt' => 7 ),
            array( 'db' => 'once_off_amount',     'dt' => 8 ),
            array( 'dbAlias'=>'customers',
                    'db' => 'collection_date',
                    'dt' => 9,
                    'formatter' => function( $d, $row ) {
                        return Helper::convertDate($row['collection_date'],'d-m-Y');
                    }
                ),
            array( 'db' => 'recurring_amount',     'dt' => 10 ),
            array( 'dbAlias'=>'customers',
                    'db' => 'next_collection_date',
                    'dt' => 11,
                    'formatter' => function( $d, $row ) {
                        return Helper::convertDate($row['next_collection_date'],'d-m-Y');
                    }
                ),
            array(
                'dbAlias'=>'customers',
                'number'=>true,
                'db'        => 'status',
                'dt'        => 12,
                'formatter' => function( $d, $row ) {
                    return Helper::getCustomerStatusTitle($d);
                }
            ),
            array(
                
                'dt'        => 13,
                'formatter' => function( $d, $row ) {
                   return encrypt($row['id']);
                }
            )
        );

        return $columns;
    }


    public function pendingAjaxUserList(Request $request){
        //print_r($request['columns']);
        // Table's primary key
        $primaryKey = 'id';
         
        
        
        $columns = $this->pendingListDtColumns();
        
        $firmId = auth()->user()->firm_id;
        
        $bindings=['reoccur',0,$firmId,1];

        $whereConditions ="customers.cust_type=? and customers.status=? and firm_id=? and (is_deleted!=? or is_deleted is null)";
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
    public function mulStatusUpdate(Request $request){
        if($request->isMethod('post')){
            $i=0;
            $customerIds=null;
            
            if($request->actionType=="approve"){
                $customerIds=$request->toApprove;
                $statusType='Approved';
                $status=1;
            }elseif($request->actionType=="reject"){
                $customerIds=$request->toReject;
                $statusType='Rejected';
                $status=3;
            }

            if(!is_null($customerIds)){
                foreach ($customerIds as $key => $eachUser) {
                    $customerId=decrypt($eachUser);
                    $firmId = auth()->user()->firm_id;
                    $customerData = Customer::where('id',$customerId)->where('firm_id',$firmId)->first();
                    $cusRes=json_decode(json_encode($customerData),true);
                    $validator=$this->statusUpdateValidation($cusRes);
                    if($customerData){
                        $customerData->status = $status;
                        if($validator->fails() && $status==1){
                            
                        }else{
                            if ($customerData->save()) {
                                $i++;
                            }    
                        }
                        
                    }
                }

                Session::flash('status',$i.' Customers '.$statusType.' Successfully');
                Session::flash('class','success');
            }
            
            return redirect('merchant/collection/reoccur/customer/pending-list');
            
        }
    }

    public function statusUpdate(Request $request){
        $requestStatus=['status'=>402,'message'=>'Problem in updating the record',"type"=>"danger"];
        if($request->isMethod('post')){
            $customerId=decrypt($request->customerId);
            $statusTitle=$request->action;

            $status=0;
            if($statusTitle=='approve' || $statusTitle=='active'){
                $status=1;
                $statusTitle="approved";
            }elseif($statusTitle=='reject'){
                $status=3;
                $statusTitle="rejected";
            }elseif($statusTitle=='in-active'){
                $status=2;
                $statusTitle="de-activated";
            }

                      
            $firmId = auth()->user()->firm_id;
            $customerData = Customer::where('id',$customerId)->where('firm_id',$firmId)->first();
            $cusRes=json_decode(json_encode($customerData),true);
            if($cusRes){

                
                $validator=$this->statusUpdateValidation($cusRes);
                if ($validator->fails() && $status==1 && $statusTitle=='approve')
                {
                    $requestStatus=['status'=>402,'message'=>'Some validation failed, update Cutomer info. And try again!',"type"=>"danger"];
                }else{
                    $customerData->status = $status;
                    if ($customerData->save()) {
                        $requestStatus=['status'=>201,'message'=>'Customer/s '.$statusTitle.' successfully',"type"=>"success"];
                    }
                }



                
            }
            
        }
        echo json_encode($requestStatus);
    }

    function statusUpdateValidation($cusRes){
        $offsetDay=Config('constants.reocurTwoDayCalOffset');
        if(Helper::getSastTime()>=config('constants.bankingCutOffTime')){
            $offsetDay++;
        }
        $offsetDay=Helper::businessDayOffset($offsetDay);
        $firmId=Auth()->user()->firm_id;
        $additionalValidation=[
            
            "mandate_id"=>  [
                'required',
                'without_spaces',
                'no_special_char',
                Rule::unique('customers')->where(function ($query) use ($firmId) {
                    return $query->where('firm_id',$firmId)->where('cust_type','reoccur');
                })->ignore($cusRes['id'])
            ],
            "collection_date"=> [
                    //'required',
                                    Rule::requiredIf(function () use ($cusRes) {
                                            return (empty($cusRes['recurring_start_date']) || $cusRes['once_off_amount']>0);
                                    }),
                                    function ($attribute, $value, $fail) use ($cusRes,$offsetDay){
                                        
                                            
                                            if (!empty($value)  && strtotime($value)< strtotime("+$offsetDay day",strtotime(date('Y-m-d')))){
                                                $fail("Collection date should be after atleast ".$offsetDay." working days");
                                            }
                                        
                                        
                                    }
                                ],
            "recurring_start_date"=>[
                                        Rule::requiredIf(function () use ($cusRes) {
                                            return (empty($cusRes['collection_date']) || intval($cusRes['recurring_amount'])>0);
                                        }),
                                        function ($attribute, $value, $fail) use ($cusRes,$offsetDay){
                                            
                                                
                                                if (!empty($value) && strtotime($value)< strtotime("+$offsetDay day",strtotime(date('Y-m-d')))){
                                                    $fail("Reocurring collection date should be after atleast ".$offsetDay." working days");
                                                }
                                            
                                        }
                                    ]
        ];

        $validator = $this->validation($cusRes,$additionalValidation);
        return $validator;
    }
    public function updatePendingCustomer(Request $request){
        
        $customerId   = decrypt($request->id);
        $pagename = "Collections - Update Reccur Customer";

        
        $firmId=Auth()->user()->firm_id;

        if($customerId){
            
            $cusRes = Customer::where(['firm_id'=>$firmId,'id'=>$customerId,'status'=>0])->first();
            if(empty($cusRes)){
                Session::flash('status','Requested record not found or you are not having permission to access that!');
                Session::flash('class','warning');
                return redirect('merchant/collection/reoccur/customer/pending-list');    
            }

            if($request->isMethod('post')){
                $offsetDay=Config('constants.reocurTwoDayCalOffset');
                if(Helper::getSastTime()>=config('constants.bankingCutOffTime')){
                    $offsetDay++;
                }
                $offsetDay=Helper::businessDayOffset($offsetDay);
                $additionalValidation=[
                    
                    "mandate_id"=>  [
                        'required',
                        'without_spaces',
                        'no_special_char',
                        Rule::unique('customers')->where(function ($query) use ($firmId) {
                            return $query->where('firm_id',$firmId)->where('cust_type','reoccur');
                        })->ignore($cusRes->id)
                    ],
                    "collection_date"=> [
                            //'required',
                                            Rule::requiredIf(function () use ($request) {
                                                    return (empty($request['recurring_start_date']) || $request['once_off_amount']>0);
                                            }),
                                            function ($attribute, $value, $fail) use ($request,$cusRes,$offsetDay){
                                                
                                                    
                                                    if (!empty($value)  && strtotime($value)< strtotime("+$offsetDay day",strtotime(date('Y-m-d')))){
                                                        $fail("Collection date should be after atleast ".$offsetDay." working days");
                                                    }
                                                
                                                
                                            }
                                        ],
                    "recurring_start_date"=>[
                                                Rule::requiredIf(function () use ($request) {
                                                    return (empty($request['collection_date']) || intval($request['recurring_amount'])>0);
                                                }),
                                                function ($attribute, $value, $fail) use ($request,$cusRes,$offsetDay){
                                                    
                                                        
                                                        if (!empty($value) && strtotime($value)< strtotime("+$offsetDay day",strtotime(date('Y-m-d')))){
                                                            $fail("Reocurring collection date should be after atleast ".$offsetDay." working days");
                                                        }
                                                    
                                                }
                                            ]
                ];

                $validator = $this->validation($request->all(),$additionalValidation);
                                        
                if ($validator->fails())
                {

                    return Redirect::to('merchant/collection/reoccur/customer/pendingupdate/'.encrypt($customerId))->withErrors($validator)->withInput();;
                }

                $customer = $this->customerSave($request,$cusRes);
                
                if($customer->save()){
                    Session::flash('status','Customer Updated successfully');
                    Session::flash('class','success');
                }else{
                     Session::flash('status','Unable to Update Customer! Please try again later');
                     Session::flash('class','danger');
                }
                return redirect('merchant/collection/reoccur/customer/pending-list');
            }
        }else{
            Session::flash('status','Problem in fetching the record');
            Session::flash('class','danger');
            return redirect('merchant/collection/reoccur/customer/pending-list');
        }
        $holidayData=PublicHolidays::where('holiday_date','>=',date('Y-m-d'))->get();
        $holidayDates=[];
        foreach ($holidayData as $key => $eachHoliday) {
            $holidayDates[]=Helper::convertDate($eachHoliday['holiday_date'],'m/d/Y');
        }
        return view('merchant.reoccur-customer.pendingCustomerUpdate',compact('pagename','cusRes','holidayDates'));
    }

    public function viewPendingCustomer(Request $request){
        
        $customerId   = decrypt($request->id);
        $pagename = "Collections - View Reoccur Customer";

        
        $firmId=Auth()->user()->firm_id;

        if($customerId){
            
            $cusRes = Customer::where(['firm_id'=>$firmId,'id'=>$customerId,'status'=>0])->first();
            if(empty($cusRes)){
                Session::flash('status','Requested record not found or you are not having permission to access that!');
                Session::flash('class','warning');
                return redirect('merchant/collection/reoccur/customer/pending-list');    
            }
        }else{
            Session::flash('status','Problem in fetching the record');
            Session::flash('class','danger');
            return redirect('merchant/collection/reoccur/customer/pending-list');
        }
        
        return view('merchant.reoccur-customer.pendingCustomerDetail',compact('pagename','cusRes'));
    }


    public function transactions(Request $request){
        $pagename  = "Transaction List";
        $customerId   = decrypt($request->id);
        $firmId=Auth()->user()->firm_id;

        if($customerId){
            $cusRes = Customer::where(['firm_id'=>$firmId,'id'=>$customerId])->where('status','!=',0)->first();
            if(empty($cusRes)){
                Session::flash('status','Requested record not found or you are not having permission to access that!');
                Session::flash('class','warning');
                return redirect('merchant/collection/reoccur/customers');    
            }
        }else{
            Session::flash('status','Problem in fetching the record');
            Session::flash('class','danger');
            return redirect('merchant/collection/reoccur/customers');
        }

        return view('merchant.reoccur-customer.customerTransactionList',compact('pagename','customerId','cusRes'));
    }

    private function dtColumnForCustTranxList(){
        $columns = array(
            
            
           
            array( 
                    'dbAlias'=>'collections',
                    'db' => 'payment_date',
                    'dt' => 0,
                    'formatter' => function( $d, $row ) {
                        return Helper::convertDate($row['payment_date'],'d-m-Y');
                    }
             ),
            array( 'dbAlias'=>'collections','db' => 'payment_type',  'dt' => 1),
            array(
                'dbAlias'=>'collections',
                'number'=>true,
                'db'        => 'transaction_status',
                'dt'        => 2,
                'filterfrom'=> function($searchString){
                    return Helper::getDTCompatibleFilterValueForCollectionTransactionStatus($searchString);
                    
                },
                'formatter' => function( $d, $row ) {
                    return Helper::getCollectionTransactionTitle($d);
                }
            ),
            array(
                
                'db'        => 'description',
                'dt'        => 3,
                
            ),
            array( 'dbAlias'=>'collections','db' => 'amount',  'dt' => 4)
            
        );

        return $columns;
    }

    public function ajaxTransactions(Request $request){
        //print_r($request['columns']);
        // Table's primary key
        $primaryKey = 'id';
        $customerId   = decrypt($request->id);
        
        
        $columns = $this->dtColumnForCustTranxList();
        
        $firmId = auth()->user()->firm_id;
        
        $bindings=[$customerId,$firmId];

        $whereConditions ="collections.customer_id =? and collections.firm_id=?";
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
                ->selectRaw('collections.*,customers.first_name,customers.last_name,transaction_error_codes.description')
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
