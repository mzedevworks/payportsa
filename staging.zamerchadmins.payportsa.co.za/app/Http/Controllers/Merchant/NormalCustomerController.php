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
use App\Model\{Firm,BankDetails,Role,CompanyInformation,Employees,Customer,TempCustomers,PublicHolidays,ProfileLimits,Collections,Batch};
//use Maatwebsite\Excel\Facades\Excel;
use Response;

class NormalCustomerController extends Controller
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
        return view('merchant.normal-customer.customerList',compact('pagename'));
        
        
    }

    private function capturerDtColumns(){
        $columns = array(
            array( 'db' => 'mandate_id', 'dt' => 0 ),
            array( 'db' => 'first_name', 'dt' => 1 ),
            array( 'db' => 'last_name',  'dt' => 2 ),
            array( 'db' => 'contact_number','dt' => 3 ),
            array(
                'dbAlias'   => 'bank_details',
                'db'        => 'bank_name',
                'dt'        => 4
            ),
            array('dbAlias'   => 'customers', 'db' => 'branch_code',     'dt' => 5 ),
            array( 'db' => 'account_number',     'dt' => 6 ),
           
           array(
                'dbAlias'=>'customers',
                'number'=>true,
                'db'=> 'status',
                'dt'=> 7,
                'formatter' => function( $d, $row ) {
                    return Helper::getCustomerStatusTitle($d);
                }
            ),
            array(
                
                'dt'        => 8,
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
        
        $bindings=['normal',1,2,$firmId,1];

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
        $orderBy ='customers.created_at DESC, '. DatatableHelper::order ( $request, $columns );
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
            $offsetDay=Config('constants.reocurTwoDayCalOffset');
            if(Helper::getSastTime()>=config('constants.bankingCutOffTime')){
                $offsetDay++;
            }
            $offsetDay=Helper::businessDayOffset($offsetDay);
            $additionalValidation=[
                
                "mandate_id" =>    [
                                        'required',
                                        'without_spaces',
                                        'no_special_char',
                                        Rule::unique('customers')->where(function ($query) use ($firmId) {
                                            return $query->where('firm_id',$firmId)->where('cust_type','normal');
                                        })
                                    ],
                // "collection_date"=> [
                //             //'required',
                //                         Rule::requiredIf(function () use ($request) {
                //                                 return true;
                //                         }),
                //                         function ($attribute, $value, $fail) use ($request,$offsetDay){

                //                             if (!empty($value) && strtotime($value)< strtotime("+".$offsetDay." day",strtotime(date('Y-m-d')))){
                //                                 $fail("Collection date should be after atleast ".$offsetDay." working days");
                //                             }
                //                         }
                //                     ],
                
            ];
            $validator = $this->validation($request->all(),$additionalValidation);
        

            if ($validator->fails()){
                return Redirect::to('merchant/collection/normal/customer/create')->withErrors($validator)->withInput();
            }

            $customer = new Customer();
            $customer = $this->customerSave($request,$customer);
            $customer->status=1;
            
            
            $data = [
                'template'  => 'payportUserInvite',
                'subject'   => "Your account is created.",
                'to'         => $customer
            ];

            if($customer->save()){
                //$status = Helper::sendInviteMail($data);
                Session::flash('status','Customer details submitted');
                Session::flash('class','success');
            }else{
                 Session::flash('status','Unable to create Customer! Please try again later');
                 Session::flash('class','danger');
            }
            return redirect('merchant/collection/normal/customers');
        }
        
        $holidayData=PublicHolidays::where('holiday_date','>=',date('Y-m-d'))->get();
        $holidayDates=[];
        foreach ($holidayData as $key => $eachHoliday) {
            $holidayDates[]=Helper::convertDate($eachHoliday['holiday_date'],'m/d/Y');
        }
        return view('merchant.normal-customer.create',compact('pagename','holidayDates'));
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
                return redirect('merchant/collection/normal/customers');    
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
                            return $query->where('firm_id',$firmId)->where('cust_type','normal');
                        })->ignore($cusRes->id)
                    ],
                    "status"=>  [
                        'required',
                        'without_spaces',
                        'no_special_char',
                        Rule::in([1,2])
                    ],
                    // "collection_date"=> [
                    //         //'required',
                    //                        'required',
                    //                         function ($attribute, $value, $fail) use ($request,$cusRes,$offsetDay){
                    //                             if(strtotime($value)!=strtotime($cusRes->collection_date)){
                                                    
                    //                                 if (!empty($value)  && strtotime($value)< strtotime("+$offsetDay day",strtotime(date('Y-m-d')))){
                    //                                     //$fail("Should select 2 date after today's date if service type is 2 day .");
                    //                                     $fail("Collection date should be after atleast ".$offsetDay." working days");
                    //                                 }
                    //                             }
                                                
                    //                         }
                    //                     ]
                    
                ];

                $validator = $this->validation($request->all(),$additionalValidation);
                                        
                if ($validator->fails())
                {

                    return Redirect::to('merchant/collection/normal/customer/update/'.encrypt($customerId))->withErrors($validator)->withInput();;
                }

                $customer = $this->customerSave($request,$cusRes);
                $customer->status=$request->status;
                if($customer->save()){
                    Session::flash('status','Customer Updated successfully');
                    Session::flash('class','success');
                }else{
                     Session::flash('status','Unable to Update Customer! Please try again later');
                     Session::flash('class','danger');
                }
                return redirect('merchant/collection/normal/customers');
            }
        }else{
            Session::flash('status','Problem in fetching the record');
            Session::flash('class','danger');
            return redirect('merchant/collection/normal/customers');
        }
        $holidayData=PublicHolidays::where('holiday_date','>=',date('Y-m-d'))->get();
        $holidayDates=[];
        foreach ($holidayData as $key => $eachHoliday) {
            $holidayDates[]=Helper::convertDate($eachHoliday['holiday_date'],'m/d/Y');
        }
        return view('merchant.normal-customer.customerUpdate',compact('pagename','userStatus','cusRes','holidayDates'));
    }
    public function viewCustomer(Request $request){
        
        $customerId   = decrypt($request->id);
        $pagename = "Collections - Update Customer";

        $userStatus=config('constants.userStatus');
        $firmId=Auth()->user()->firm_id;

        if($customerId){

            $cusRes = Customer::where(['firm_id'=>$firmId,'id'=>$customerId])->first();
            if(empty($cusRes)){
                Session::flash('status','Requested record not found or you are not having permission to access that!');
                Session::flash('class','warning');
                return redirect('merchant/collection/normal/customers');    
            }

            
        }else{
            Session::flash('status','Problem in fetching the record');
            Session::flash('class','danger');
            return redirect('merchant/collection/normal/customers');
        }
        
        return view('merchant.normal-customer.customerView',compact('pagename','userStatus','cusRes'));
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
                "account_number"        => 'required|no_special_char|numeric|integer|regex:/[0-9]+/', 
                //"once_off_amount"       => 'required|no_special_char'
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
        /*$validator->sometimes(['collection_date'], 'date_format:Y-m-d', function ($input){
            return !empty($input->collection_date);
        });

        

        $validator->sometimes(['once_off_amount'], 'integer', function ($input) {
            return !empty($input->once_off_amount);
        });
        if(isset($request['once_off_amount']) && is_numeric($request['once_off_amount'])){
            $validator->sometimes(['once_off_amount'], 'gt:0', function ($input) {
                return ($input->once_off_amount!="" || !is_null($input->once_off_amount));
            });
            
            $validator->sometimes(['once_off_amount'], 'max:'.$profileLimits->line_collection, function ($input) {
                return ($input->once_off_amount!="" || !is_null($input->once_off_amount));
            });
        }*/

        
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
        $customer->cust_type          = 'normal';
        
        $customer->debit_frequency       = $request->debit_frequency;
        $customer->bank_id               = $request->bank_id; 
        $customer->branch_code           = $request->branch_code; 
        $customer->account_type          = $request->account_type;
        $customer->account_holder_name   = $request->account_holder_name; 
        $customer->account_number        = $request->account_number;
        //$customer->once_off_amount       = $request->once_off_amount;
        

        /*if(!empty($request->collection_date)){
            $customer->collection_date       = Helper::convertDate($request->collection_date,"Y-m-d");    
        }*/
        
        
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
            return redirect('merchant/collection/normal/customers');
            
        }
    }

    public function tempList(){
        
        $pagename  = "Upload Customer List";
        $customers = TempCustomers::where('added_by',auth()->user()->id)->where('is_deleted',0)->where('cust_type','normal')->get();
        $existingCustomers = Customer::select('mandate_id','reference')->get();
        $mandateArray = array();
        $referenceArray = array();
        foreach ($existingCustomers as $key => $customer) {
            array_push($mandateArray, $customer->mandate_id);
            array_push($referenceArray, $customer->reference);
        }
        return view('merchant.normal-customer.temp-list',compact('customers','pagename','mandateArray','referenceArray'));
    }

    public function sampleCsvDownload(){

        $file    = public_path(). "/uploads/normal-collections-template.csv";
        $headers = array(
                'Content-Type: application/csv',
        );
        return Response::download($file,'normal-collections-template.csv',$headers);
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

                    $dataArray = array("mandate_id","first_name","last_name","email","contact_number","id_number","address_one","address_line_two","suburb","city","province",'bank_name','account_type','branch_code','account_holder_name','account_number');

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
                                $tempemp->cust_type = 'normal';
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
                    return redirect('merchant/collection/normal/customer/upload');
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
        return redirect('merchant/collection/normal/customer/upload');
    }
    private function csvValidation($request){
            
           
            $firmId = auth()->user()->firm_id;
            $request["account_type"] = strtolower($request["account_type"]);
            $offsetDay=Config('constants.reocurTwoDayCalOffset');
            if(Helper::getSastTime()>=config('constants.bankingCutOffTime')){
                $offsetDay++;
            }
            $offsetDay=Helper::businessDayOffset($offsetDay);

            $validator = \Validator::make($request, [
                'mandate_id'         => [
                                           'required',
                                            Rule::unique('customers','mandate_id')->where(function ($query) use($firmId){
                                                return $query->where('firm_id', $firmId)->where('cust_type','normal');
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
                /*"collection_date"     => [
                                        'required',
                                        function ($attribute, $value, $fail) use ($request,$offsetDay){
                                            
                                            if (!empty($value) && strtotime($value)< strtotime("+".$offsetDay." day",strtotime(date('Y-m-d')))){
                                                $fail("Collection date should be after atleast ".$offsetDay." working days");
                                            }
                                        }
                                    ],
                "once_off_amount"     => 'required|no_special_char'*/
                
                
                
            ]);
            $validator->sometimes(['email'], 'email', function ($input) {
                return !empty($input->email);
            });
            $validator->sometimes(['contact_number'], 'digits:10', function ($input) {
                return !empty($input->contact_number);
            });

            

            // $validator->sometimes(['once_off_amount'], 'integer', function ($input) {
            //     return !empty($input->once_off_amount);
            // });
            
            $firmId=auth()->user()->firm_id;
            $profileLimits  = ProfileLimits::where(['firm_id' => $firmId])->first();
            
            /*if(isset($request['once_off_amount']) && is_numeric($request['once_off_amount'])){
                $validator->sometimes(['once_off_amount'], 'gt:0', function ($input) {
                    return ($input->once_off_amount!="" || !is_null($input->once_off_amount));
                });


                $validator->sometimes(['once_off_amount'], 'max:'.$profileLimits->line_collection, function ($input) {
                    return ($input->once_off_amount!="" || !is_null($input->once_off_amount));
                });
            }*/

            
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
        return redirect('merchant/collection/normal/customer/upload');
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
        return redirect('merchant/collection/normal/customer/upload');
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
          $customer->cust_type              = 'normal';
          $customer->bank_id                =   $bank_details->id;
          $customer->account_type           =   $request['account_type'];
          $customer->branch_code            =   $request['branch_code'];
          $customer->account_holder_name    =   $request['account_holder_name'];
          $customer->account_number         =   $request['account_number'];
          $customer->status         =   1;
          //$customer->once_off_amount        =   $request['once_off_amount'];
          

        // if(!empty($request['collection_date'])){
        //     $customer->collection_date       = Helper::convertDate($request['collection_date'],"Y-m-d");    
        // }
        
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
        return view('merchant.normal-customer.pendingCustomerList',compact('pagename'));
        
        
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
            
            array(
                'dbAlias'=>'customers',
                'number'=>true,
                'db'        => 'status',
                'dt'        => 10,
                'formatter' => function( $d, $row ) {
                    return Helper::getCustomerStatusTitle($d);
                }
            ),
            array(
                
                'dt'        => 11,
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
        
        $bindings=['normal',0,$firmId,1];

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
            
            return redirect('merchant/collection/normal/customer/pending-list');
            
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
                    return $query->where('firm_id',$firmId)->where('cust_type','normal');
                })->ignore($cusRes['id'])
            ],
            "collection_date"=> [
                                'required',
                                function ($attribute, $value, $fail) use ($cusRes,$offsetDay){
                                    if (!empty($value)  && strtotime($value)< strtotime("+$offsetDay day",strtotime(date('Y-m-d')))){
                                        $fail("Collection date should be after atleast ".$offsetDay." working days");
                                    }
                                }
                                ],
            
        ];

        $validator = $this->validation($cusRes,$additionalValidation);
        return $validator;
    }
    public function updatePendingCustomer(Request $request){
        
        $customerId   = decrypt($request->id);
        $pagename = "Collections - Update Customer";

        
        $firmId=Auth()->user()->firm_id;

        if($customerId){
            
            $cusRes = Customer::where(['firm_id'=>$firmId,'id'=>$customerId,'status'=>0])->first();
            if(empty($cusRes)){
                Session::flash('status','Requested record not found or you are not having permission to access that!');
                Session::flash('class','warning');
                return redirect('merchant/collection/normal/customer/pending-list');    
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
                            return $query->where('firm_id',$firmId)->where('cust_type','normal');
                        })->ignore($cusRes->id)
                    ],
                    "collection_date"=> [
                            //'required',
                                            Rule::requiredIf(function () use ($request) {
                                                    return true;
                                            }),
                                            function ($attribute, $value, $fail) use ($request,$cusRes,$offsetDay){
                                                
                                                    
                                                    if (!empty($value)  && strtotime($value)< strtotime("+$offsetDay day",strtotime(date('Y-m-d')))){
                                                        $fail("Collection date should be after atleast ".$offsetDay." working days");
                                                    }
                                                
                                                
                                            }
                                        ],
                    
                ];

                $validator = $this->validation($request->all(),$additionalValidation);
                                        
                if ($validator->fails())
                {

                    return Redirect::to('merchant/collection/normal/customer/pendingupdate/'.encrypt($customerId))->withErrors($validator)->withInput();;
                }

                $customer = $this->customerSave($request,$cusRes);
                
                if($customer->save()){
                    Session::flash('status','Customer Updated successfully');
                    Session::flash('class','success');
                }else{
                     Session::flash('status','Unable to Update Customer! Please try again later');
                     Session::flash('class','danger');
                }
                return redirect('merchant/collection/normal/customer/pending-list');
            }
        }else{
            Session::flash('status','Problem in fetching the record');
            Session::flash('class','danger');
            return redirect('merchant/collection/normal/customer/pending-list');
        }
        $holidayData=PublicHolidays::where('holiday_date','>=',date('Y-m-d'))->get();
        $holidayDates=[];
        foreach ($holidayData as $key => $eachHoliday) {
            $holidayDates[]=Helper::convertDate($eachHoliday['holiday_date'],'m/d/Y');
        }
        return view('merchant.normal-customer.pendingCustomerUpdate',compact('pagename','cusRes','holidayDates'));
    }

    public function viewPendingCustomer(Request $request){
        
        $customerId   = decrypt($request->id);
        $pagename = "Collections - View Customer";

        
        $firmId=Auth()->user()->firm_id;

        if($customerId){
            
            $cusRes = Customer::where(['firm_id'=>$firmId,'id'=>$customerId,'status'=>0])->first();
            if(empty($cusRes)){
                Session::flash('status','Requested record not found or you are not having permission to access that!');
                Session::flash('class','warning');
                return redirect('merchant/collection/normal/customer/pending-list');    
            }
        }else{
            Session::flash('status','Problem in fetching the record');
            Session::flash('class','danger');
            return redirect('merchant/collection/normal/customer/pending-list');
        }
        
        return view('merchant.normal-customer.pendingCustomerDetail',compact('pagename','cusRes'));
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
                return redirect('merchant/collection/normal/customers');    
            }
        }else{
            Session::flash('status','Problem in fetching the record');
            Session::flash('class','danger');
            return redirect('merchant/collection/normal/customers');
        }

        return view('merchant.normal-customer.customerTransactionList',compact('pagename','customerId','cusRes'));
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

    public function createBatch(Request $request){
        

        
        $user_id = auth()->user()->id;
        $firmId=Auth()->user()->firm_id;
        $firm = Firm::find($firmId);
        $pagename     = 'Create batch';

        $holidayData=PublicHolidays::where('holiday_date','>=',date('Y-m-d'))->get();
        $holidayDates=[];
        foreach ($holidayData as $key => $eachHoliday) {
            $holidayDates[]=Helper::convertDate($eachHoliday['holiday_date'],'m/d/Y');
        }
        if($request->isMethod('post')){
            
            $paymentCuttOffTime=config('constants.bankingCutOffTime');
            $offsetDay=config('constants.normalTwoDayCalOffset');

            if(strtoupper($request['service_type'])=='1 DAY'){
                $offsetDay=config('constants.normalOneDayCalOffset');
            }
            
            if(Helper::getSastTime()>=$paymentCuttOffTime){
                $offsetDay++;
            }
            $offsetDay=Helper::businessDayOffset($offsetDay);

            $additionalValidation=[
                "batch_name"    => 'required|no_special_char',
                "service_type"  =>[
                                        'required',
                                        Rule::in(['1 Day','2 Day'])
                                    ],
                "collection_date"  => [
                                        'required',
                                        
                                        function ($attribute, $value, $fail) use ($request,$offsetDay){
                                            if (!empty($value) && strtotime($value)< strtotime("+".$offsetDay." day",strtotime(date('Y-m-d')))){
                                                $fail("Collection date should be after atleast ".$offsetDay."  days");
                                            }
                                        }
                                    ],
                "customer_selection"=>    [
                                        'required',
                                        Rule::in(['manual','csvupload'])
                                    ]
                
            ];
            $validator = \Validator::make($request->all(),$additionalValidation );
            
        

            if ($validator->fails()){
                return Redirect::to('merchant/collection/normal/create-batch')->withErrors($validator)->withInput();
            }else{
                $postData=$request;
                if($request['customer_selection']=='manual'){
                    $pagename     = 'Select Customer';

                    return view('merchant.normal-customer.select-customer',compact('pagename','postData'));
                }else{
                    $pagename     = 'Upload Batch CSV';
                    return view('merchant.normal-customer.upload-batch',compact('pagename','postData'));
                }
                exit();
            }
        }

        return view('merchant.normal-customer.create-batch',compact('pagename','firm','holidayDates'));
    }

    public function ajaxlistforbatch(Request $request){
        
        //print_r($request['columns']);
        // Table's primary key
        $primaryKey = 'id';
         
        // Array of database columns which should be read and sent back to DataTables.
        // The `db` parameter represents the column name in the database, while the `dt`
        // parameter represents the DataTables column identifier. In this case simple
        // indexes
        $columns = array(
            array( 'dt' => 0,
                'formatter' => function( $d, $row ) {
                    //return encrypt($row['id']);
                    return $row['id'];
                }
            ),
            array( 'db' => 'mandate_id',  'dt' => 1 ),
            array( 'db' => 'first_name', 'dt' => 2 ),
            array( 'db' => 'last_name',  'dt' => 3 ),
            array( 'db' => 'once_off_amount',     'dt' => 4),
            array( 'db' => 'reference',  'dt' => 5),
        );

        $firmId = auth()->user()->firm_id;
        
        $bindings=['normal',$firmId,1];

        $whereConditions="cust_type=? and firm_id=? and status =?";
        $totalCount = DB::table('customers')
                ->selectRaw('count('.$primaryKey.') totCount')
                ->whereRaw($whereConditions, $bindings)
                ->get()
                ->toArray();

        $dtWhere=DatatableHelper::filter ( $request, $columns,$bindings);
        if($dtWhere!==""){
            $whereConditions.=" and ".$dtWhere;
        }
        $orderBy=DatatableHelper::order ( $request, $columns );
        $limit=DatatableHelper::limit ( $request, $columns );
        
        $data = DB::table('customers')
                ->selectRaw('customers.*')
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
       
        $totalFilteredCount = DB::table('customers')
                ->selectRaw('count(customers.'.$primaryKey.') totCount, customers.'.$primaryKey)
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

    public function savebatch(Request $request){
        $user_id = auth()->user()->id;
        $firmId=Auth()->user()->firm_id;

        if($request->isMethod('post')){
            
            $selectedCustomer=json_decode($request['customerList']);
            $selectedAmount=json_decode($request['customerAmount']);
            $selectedReff=json_decode($request['customerReff']);
            $bindings=['normal',$firmId,1];
            $whereConditions="cust_type=? and firm_id=? and status =?";
            $firmCustomers = DB::table('customers')
                ->selectRaw('id')
                ->whereRaw($whereConditions, $bindings)
                ->pluck('id')->toArray();
            
            
            
            
            $paymentCuttOffTime=config('constants.bankingCutOffTime');
            $offsetDay=config('constants.normalTwoDayCalOffset');
            if($request['service_type']=='1 Day'){
                $offsetDay=config('constants.normalOneDayCalOffset');
            }
            
            if(Helper::getSastTime()>=$paymentCuttOffTime){
                $offsetDay++;
            }
            $offsetDay=Helper::businessDayOffset($offsetDay);

            $additionalValidation=[
                "batch_name"    => 'required|no_special_char',
                "service_type"  =>[
                                        'required',
                                        Rule::in(['1 Day','2 Day'])
                                    ],
                "collection_date"  => [
                                        'required',
                                        function ($attribute, $value, $fail) use ($request,$offsetDay){
                                            if (!empty($value) && strtotime($value)< strtotime("+".$offsetDay." day",strtotime(date('Y-m-d')))){
                                                $fail("Collection date should be after atleast ".$offsetDay."  days");
                                            }
                                        }
                                    ],
                "customer_selection"=>    [
                                        'required',
                                        Rule::in(['manual'])
                                    ],
            ];
            $validator = \Validator::make($request->all(),$additionalValidation );

            if ($validator->fails()){
                return Redirect::to('merchant/collection/normal/create-batch')->withErrors($validator)->withInput();
            }else{
 // echo $size = (int) $_SERVER['CONTENT_LENGTH'];
 // $bytesInPostRequestBody = strlen(file_get_contents('php://input'));

                
                $collectionBatch=new Batch();
                $collectionBatch->firm_id=$firmId;
                $collectionBatch->batch_name=$request['batch_name'];
                $collectionBatch->batch_type='normal-collection';
                $collectionBatch->batch_service_type=$request['service_type'];
                $collectionBatch->action_date=Helper::convertDate($request['collection_date'],'Y-m-d');
                
                $collectionBatch->batch_status='pending';
                $collectionBatch->created_at=date('Y-m-d H:i:s');
                //$paymentbatch->created_by=$user_id;
                if($collectionBatch->save()){
                    Helper::logStatusChange('collection_batch',$collectionBatch,"batch created");
                    $collectionBatchId=$collectionBatch->id;

                    foreach ($selectedCustomer as $key => $eachCustomer) {
                        if(in_array($eachCustomer, $firmCustomers)){
                            $customer = Customer::find($selectedCustomer[$key]);
                            if($customer->cust_type=='normal'){
                                $collectionRow=new Collections();
                                $collectionRow->batch_id=$collectionBatchId;
                                $collectionRow->customer_id=$selectedCustomer[$key];
                                $collectionRow->firm_id=$firmId;
                                $collectionRow->collection_for=1;
                                $collectionRow->payment_date=$collectionBatch->action_date;
                                $collectionRow->amount=$selectedAmount[$key];
                                $collectionRow->bank_id=$customer->bank_id;
                                $collectionRow->account_type=$customer->account_type;
                                $collectionRow->account_holder_name=$customer->account_holder_name;
                                $collectionRow->branch_code     = $customer->branch_code; 
                                $collectionRow->account_number=$customer->account_number;
                                $userAbbrivatedCode = $customer->firm->trading_as;
                                $fillerLen = 10-strlen($userAbbrivatedCode);
                                $userAbbrivatedCode=$userAbbrivatedCode.str_repeat(' ',$fillerLen);

                                $customStrg=$selectedReff[$key];
                                if(strlen($customStrg)>20){
                                    $customStrg=substr($customStrg, 0, 20);
                                }
                                $collectionRow->reffrence=$userAbbrivatedCode.$customStrg;
                                $collectionRow->service_type=$request['service_type'];
                                $collectionRow->entry_class=$customer->firm->entry_class;
                                $collectionRow->transmission_status=0;
                                $collectionRow->transaction_status=0;
                                $collectionRow->collection_status=0;
                                $collectionRow->created_at=date('Y-m-d H:i:s');
                                $collectionRow->payment_type='onceoff';
                                $collectionRow->save();
                                Helper::logStatusChange('collection',$collectionRow,"Collection created");
                            }
                            
                            
                        }
                    }



                    

                    Session::flash('status','Batch created successfully');
                    Session::flash('class','success');
                    
                    //return redirect('merchant/employees');
                    return redirect('merchant/collection/normalbatch/pending');
                }
            }
        }else{
            return redirect('merchant/collection/normalbatch/pending');
        }
    }

    public function samplebatchcsvDownload(){

            $file    = public_path(). "/uploads/sample_normal_collection_batch.csv";
            $headers = array(
                      'Content-Type: application/csv',
            );
            return Response::download($file,'collectionbatch-sample.csv',$headers);
    }

    public function batchimport(Request $request){


        if($request->file('file_name')!=''){
            $file = $request->file('file_name');
            $firmId = auth()->user()->firm_id;
            
              // File Details 
            $filename  = rand().'_'.$file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
              
            $tempPath = $file->getRealPath();
            $fileSize = $file->getSize();
            $mimeType = $file->getMimeType();

              // Valid File Extensions
            $valid_extension = array("csv","xls","xlsx");

              // 10MB in Bytes
            $maxFileSize = Config('constants.maxFileUploadSize'); 

              // Check file extension
            if(in_array(strtolower($extension),$valid_extension)){

                // Check file size
                if($fileSize <= $maxFileSize){

                    // File upload location
                    $location = public_path('uploads/collection');

                      // Upload file
                    $file->move($location,$filename);

                      // Import CSV to Database

                    $selectedCustomer=$this->readCsvFileData($filename);
                    if(sizeof($selectedCustomer)<=config('constants.maxRecordInCsvFile')){
                        $postData=$request;
                    
                        $pagename     = 'Confirm Batch Csv upload';
                        return view('merchant.normal-customer.confirm-batch',compact('pagename','postData','selectedCustomer','filename'));
                        
                        exit();
                    }else{
                        Session::flash('status','File should not contain more then '.config('constants.maxRecordInCsvFile').' records.');
                    }
                }else{
                  Session::flash('status','File too large. File must be less than 10MB.');
                }
                    
            }else{
              Session::flash('status','Invalid File Extension.');
            }
        }else{
            Session::flash('status','File must be selected.');
            
        } 
        Session::flash('class','danger');
        return Redirect::to('merchant/collection/normal/create-batch')->withInput();
    }

    private function readCsvFileData($filename){
        $location = public_path('uploads/collection');
        // Import CSV to Database
        $filepath = $location."/".$filename;
        $firmId = auth()->user()->firm_id;
        // Reading file
        $file = fopen($filepath,"r");

        $importData_arr = array();
        $i = 0;

        $dataArray = array("mandate_id","amount","reference");
        $selectedCustomer=[];
        while (($filedata = fgetcsv($file, 100000, Helper::getCsvDelimiter($filepath))) !== FALSE) {
            
            $num = count($filedata );
             
            if($num!=sizeof($dataArray)){
               break;
            }
             // Skip first row (Remove below comment if you want to skip the first row)
            if($i == 0){
               $i++;
               continue; 
            }
            $importedRow=[];
            for ($c=0; $c < $num; $c++) {
                $replaceVal='none';
                if($dataArray[$c]=='amount'){
                    $replaceVal=0;
                }
                $importedRow[$dataArray[$c]] = (!empty($filedata [$c]))?$filedata [$c]:$replaceVal;
            }

            $customerData = Customer::select('mandate_id', 'first_name','last_name','id')->where('mandate_id',$importedRow['mandate_id'])->where('firm_id',$firmId)->where('cust_type','normal')->where('status',1)->first();
            
            if($customerData){
                $importedRow['cust']=$customerData;
                $importedRow['id']=$customerData->id;
                $selectedCustomer[]=$importedRow;
            }

            $i++;
        }
          fclose($file);
          return $selectedCustomer;
    }

    function savecsvbatch(Request $request){
        $user_id = auth()->user()->id;
        $firmId=Auth()->user()->firm_id;
        if($request->isMethod('post')){
            
            $selectedCust=json_decode($request['customerList']);
            $selectedAmount=json_decode($request['customerAmount']);
            $selectedReff=json_decode($request['customerReff']);
            $bindings=['normal',$firmId,1];
            $whereConditions="cust_type=? and firm_id=? and status =?";
            $firmEmployees = DB::table('customers')
                ->selectRaw('id')
                ->whereRaw($whereConditions, $bindings)
                ->pluck('id')->toArray();
            
            $paymentCuttOffTime=config('constants.bankingCutOffTime');
            $offsetDay=config('constants.normalTwoDayCalOffset');

            if(strtoupper($request['service_type'])=='1 DAY'){
                $offsetDay=config('constants.normalOneDayCalOffset');
            }
            
            
            if(Helper::getSastTime()>=$paymentCuttOffTime){
                $offsetDay++;
            }
            $offsetDay=Helper::businessDayOffset($offsetDay);

            $additionalValidation=[
                "batch_name"    => 'required|no_special_char',
                "service_type"  =>[
                                        'required',
                                        Rule::in(['1 Day','2 Day'])
                                    ],
                "collection_date"  => [
                                        'required',
                                        function ($attribute, $value, $fail) use ($request,$offsetDay){
                                            if (!empty($value) && strtotime($value)< strtotime("+".$offsetDay." day",strtotime(date('Y-m-d')))){
                                                $fail("Collection date should be after atleast ".$offsetDay."  days");
                                            }
                                        }
                                    ]
            ];
            $validator = \Validator::make($request->all(),$additionalValidation );
            
        

            if ($validator->fails()){
                return Redirect::to('merchant/collection/normal/create-batch')->withErrors($validator)->withInput();
            }else{
                // echo $size = (int) $_SERVER['CONTENT_LENGTH'].'<br/>';
                // echo $bytesInPostRequestBody = strlen(file_get_contents('php://input')).'<br/>';
                // dd($selectedCust[0]);
                $collectionBatch=new Batch();
                $collectionBatch->firm_id=$firmId;
                $collectionBatch->batch_name=$request['batch_name'];
                $collectionBatch->batch_type='normal-collection';
                $collectionBatch->batch_service_type=$request['service_type'];
                $collectionBatch->action_date=$request['collection_date'];
                if($request['service_type']=='sameday'){
                    $collectionBatch->action_date=date('Y-m-d');
                }
                
                $collectionBatch->batch_status='pending';
                $collectionBatch->created_at=date('Y-m-d');
                $collectionBatch->created_by=$user_id;
                if($collectionBatch->save()){
                    Helper::logStatusChange('collection_batch',$collectionBatch,"batch created");
                    $collectionBatchId=$collectionBatch->id;
                    $filepath=$request['file_path'];
                    $selectedCust=$this->readCsvFileData($filepath);
                    $firm = Firm::find($firmId);
                    foreach ($selectedCust as $key => $eachCustomer) {
                        $customer = Customer::where('id',$eachCustomer['id'])->where('firm_id',$firmId)->where('cust_type','normal')->where('status',1)->first();
                        if($customer){
                            $collectionRow=new Collections();
                            $collectionRow->batch_id=$collectionBatchId;
                            $collectionRow->customer_id=$eachCustomer['id'];
                            $collectionRow->collection_for=1;
                            $collectionRow->firm_id=$firmId;
                            $collectionRow->payment_date=$collectionBatch->action_date;
                            $collectionRow->amount=$eachCustomer['amount'];
                            $collectionRow->bank_id=$customer->bank_id;
                            $collectionRow->account_type=$customer->account_type;
                            $collectionRow->account_holder_name=$customer->account_holder_name;
                            $collectionRow->account_number=$customer->account_number;
                            $collectionRow->branch_code     = $customer->branch_code; 
                            
                            $userAbbrivatedCode = $firm->trading_as;
                            $collectionRow->entry_class=$firm->entry_class;

                            $fillerLen = 10-strlen($userAbbrivatedCode);
                            $userAbbrivatedCode=$userAbbrivatedCode.str_repeat(' ',$fillerLen);

                            $customStrg=$eachCustomer['reference'];
                            if(strlen($customStrg)>20){
                                $customStrg=substr($customStrg, 0, 20);
                            }
                                
                            $collectionRow->reffrence=$userAbbrivatedCode.$customStrg;
                            $collectionRow->service_type=$request['service_type'];
                            
                            $collectionRow->transmission_status=0;
                            $collectionRow->transaction_status=0;
                            $collectionRow->collection_status=0;
                            $collectionRow->payment_type='onceoff';
                            $collectionRow->created_at=date('Y-m-d H:i:s');
                            $collectionRow->save();
                            Helper::logStatusChange('collection',$collectionRow,"Collection created");
                            
                        }
                    }

                    unlink(public_path('uploads/collection').'/'.$filepath);

                    Session::flash('status','Batch created successfully');
                    Session::flash('class','success');
                    
                    return redirect('merchant/collection/normalbatch/pending');
                }
            }
        }else{
            return redirect('merchant/collection/normal/customers');
        }
    }
}
