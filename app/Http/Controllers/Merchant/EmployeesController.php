<?php

namespace App\Http\Controllers\Merchant;

use App\Employee;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use Response;
use Illuminate\Validation\Rule;
use App\Helpers\DatatableHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use App\Model\{Firm,BankDetails,Role,PublicHolidays,Employees,TempEmployees,PaymentBatches,Payments,PaymentLedgers};

class EmployeesController extends Controller
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

        $pagename  = "Employees";
        return view('merchant.employees.list',compact('pagename'));
    }

    public function tempList(){

        $pagename  = "Upload Employee List";
        $employees = TempEmployees::where('added_by',auth()->user()->id)->where('upload_type','salaried')->where('is_deleted',0)->get();
        return view('merchant.employees.temp-list',compact('employees','pagename'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        if($request->isMethod('get')){
            $pagename = "Add Employee";
            $bankDetails = BankDetails::where('is_active','yes')->get();
            return view('merchant.employees.add',compact('pagename','bankDetails'));
        }else{
            
            $user_id = auth()->user()->id;
            $firmId=Auth()->user()->firm_id;
            $pagename     = 'Create Employee';
            $additionalValidation=[
                "id_number"=> [
                                'required',
                                'without_spaces',
                                'no_special_char',
                                'max:10',
                                Rule::unique('employees','id_number')->where('firm_id',$firmId)->where('employee_type','salaried')
                               ]
                ];
            
            $validator    = $this->validation($request->all(),$additionalValidation);
            
            if ($validator->fails()){
                return redirect()->back()->withErrors($validator)->withInput();;
            }

            $employee = new Employees();
            $employee = $this->employeeSave($request,$employee);
            //$employee->status      = 0;

            if($employee->save()){
                // $data = [
                //     'template'           => 'welcome',
                //     'subject'            => "Employee account is created.",
                //     'to'                 => $employee,
                // ]; 
                // $status = Helper::sendInviteMail($data);
                // if($status===true){
                    Session::flash('status','Employee created successfully');
                    Session::flash('class','success');

                // }else{
                //     Session::flash('status','Employee Added successfully but problem in sending an email');
                //     Session::flash('class','danger');
                // }
            }else{
                 Session::flash('status','Unable to create Employee! Please try again later');
                 Session::flash('class','danger');
            }
            return redirect('merchant/employees');
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function ajaxEmployeesList(Request $request){
        
        //print_r($request['columns']);
        // Table's primary key
        $primaryKey = 'id';
         
        // Array of database columns which should be read and sent back to DataTables.
        // The `db` parameter represents the column name in the database, while the `dt`
        // parameter represents the DataTables column identifier. In this case simple
        // indexes
        $columns = array(
            array( 'db' => 'id_number',  'dt' => 0 ),
            array( 'db' => 'first_name', 'dt' => 1 ),
            array( 'db' => 'last_name',  'dt' => 2 ),
            array( 'db' => 'email',     'dt' => 3),
            array( 'db' => 'contact_number', 'dt' => 4),
            array( 'db' => 'salary',     'dt' => 5),
            array(
                'db'        => 'status',
                'dt'        => 6,
                'formatter' => function( $d, $row ) {
                    return Helper::getEmployeeStatusTitle($d);
                }
            ),
            array(
                
                'dt'        => 7,
                'formatter' => function( $d, $row ) {
                   return encrypt($row['id']);
                }
            )
        );

        $firmId = auth()->user()->firm_id;
        
        $bindings=['salaried',$firmId,1,2];

        $whereConditions="employee_type=? and firm_id=? and status in (?,?)";
        $totalCount = DB::table('employees')
                ->selectRaw('count('.$primaryKey.') totCount')
                ->whereRaw($whereConditions, $bindings)
                ->get()
                ->toArray();

        $dtWhere=DatatableHelper::filter ( $request, $columns,$bindings);
        if($dtWhere!==""){
            $whereConditions.=" and ".$dtWhere;
        }
        $orderBy=DatatableHelper::order ( $request, $columns ).", id desc";
        $limit=DatatableHelper::limit ( $request, $columns );
        
        $data = DB::table('employees')
                ->selectRaw('employees.*')
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
       
        $totalFilteredCount = DB::table('employees')
                ->selectRaw('count(employees.'.$primaryKey.') totCount, employees.'.$primaryKey)
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
     * Update the specified resource in storage.
         *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $pagename = "Update Employee";
        $employeeId=decrypt($id);
        $employeeId=decrypt($id);
        $firmId=Auth()->user()->firm_id;
        $employee = Employees::where(['firm_id'=>$firmId,'id'=>$employeeId,'employee_type'=>'salaried'])->first();
            
        if($request->isMethod('get')){      
            $pagename    = "Update Employee";
            return view('merchant.employees.employeeUpdate',compact('pagename','employee'));
        }else{
            $firmId=Auth()->user()->firm_id;
            $id_number = $employee->id_number;

            $additionalValidation=[
                "id_number"=> [
                                'required',
                                'without_spaces',
                                'no_special_char',
                                'max:10',
                                Rule::unique('employees','id_number')->where('firm_id',$firmId)->where('employee_type','salaried')->ignore($employeeId)
                               ]
                ];

            $validator    = $this->validation($request->all(),$additionalValidation);
            
            if ($validator->fails()){
                return redirect()->back()->withErrors($validator)->withInput();;
            }

            $employee = $this->employeeSave($request,$employee);
            if($employee->save()){
                Session::flash('status','Employee Updated successfully');
                Session::flash('class','success');
            }else{
                Session::flash('status','Unable to create Employee! Please try again later');
                Session::flash('class','danger');
            }
            return redirect('merchant/employees');
        }
    }

    public function viewEmployee(Request $request){
        
        $employeeId   = decrypt($request->id);
        $pagename = "Employee - View";

        $userStatus=config('constants.employeeStatus');
        $firmId=Auth()->user()->firm_id;

        if($employeeId){

            $empRes = Employees::where(['firm_id'=>$firmId,'id'=>$employeeId,'employee_type'=>'salaried'])->first();
            
            if(empty($empRes)){
                Session::flash('status','Requested record not found or you are not having permission to access that!');
                Session::flash('class','warning');
                return redirect('merchant/employees');
            }

            
        }else{
            Session::flash('status','Problem in fetching the record');
            Session::flash('class','danger');
            return redirect('merchant/employees');
        }
        
        return view('merchant.employees.employeeView',compact('pagename','userStatus','empRes'));
    }

    public function viewEmployeeTransactions(Request $request){
        
        $employeeId   = decrypt($request->id);
        $pagename = "Employee - Transactions";

        $userStatus=config('constants.employeeStatus');
        $firmId=Auth()->user()->firm_id;

        if($employeeId){

            $empRes = Employees::where(['firm_id'=>$firmId,'id'=>$employeeId,'employee_type'=>'salaried'])->first();
            if(empty($empRes)){
                Session::flash('status','Requested record not found or you are not having permission to access that!');
                Session::flash('class','warning');
                return redirect('merchant/employees');
            }

            
        }else{
            Session::flash('status','Problem in fetching the record');
            Session::flash('class','danger');
            return redirect('merchant/employees');
        }
        
        return view('merchant.employees.employeeTransactionView',compact('employeeId','pagename','userStatus','empRes'));
    }

    public function ajaxEmployeePaymentList(Request $request){
        $primaryKey = 'id';
        $employeeId   = decrypt($request->id);
        
        
        $columns = array(
            array( 'dbAlias'=>'payments','db' => 'account_holder_name',  'dt' => 0),
            array( 'dbAlias'=>'payments','db' => 'account_number',  'dt' => 1),
            array( 'dbAlias'=>'payments','db' => 'account_type',  'dt' => 2),
            array( 
                    'dbAlias'=>'payments',
                    'db' => 'payment_date',
                    'dt' => 3,
                    'formatter' => function( $d, $row ) {
                        return Helper::convertDate($row['payment_date'],'d-m-Y');
                    }
             ),
            
            array(
                'dbAlias'=>'payments',
                'number'=>true,
                'db'        => 'payment_status',
                'dt'        => 4,
                'filterfrom'=> function($searchString){
                    return Helper::getDTCompatibleFilterValueForCollectionStatus($searchString);
                    
                },
                'formatter' => function( $d, $row ) {
                    return Helper::getCollectionStatusTitle($d);
                }
            ),
            array( 'dbAlias'=>'payments','db' => 'amount',  'dt' => 5)
            
        );
        
        
        
        $bindings=[$employeeId,'salaried'];

        $whereConditions ="payments.employee_id =? and employees.employee_type=?";
        $totalCount = DB::table('payments')
                ->leftJoin('employees', function ($join) {
                    $join->on('employees.id', '=', 'payments.employee_id');
                })  
                ->selectRaw('count(payments.'.$primaryKey.') totCount')
                ->whereRaw($whereConditions, $bindings)
                ->get()
                ->toArray();

        $dtWhere=DatatableHelper::filter ( $request, $columns,$bindings);
        if($dtWhere!==""){
            $whereConditions.=" and ".$dtWhere;
        }
        $orderBy = "payments.id desc, ".DatatableHelper::order ( $request, $columns );
        $limit   = DatatableHelper::limit ( $request, $columns );

        
        $data = DB::table('payments')
                ->leftJoin('employees', function ($join) {
                    $join->on('employees.id', '=', 'payments.employee_id');
                })  
                ->selectRaw('payments.*')
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
       
        $totalFilteredCount = DB::table('payments')
                ->leftJoin('employees', function ($join) {
                    $join->on('employees.id', '=', 'payments.employee_id');
                })  
                ->selectRaw('count(payments.'.$primaryKey.') totCount, payments.'.$primaryKey)
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

    private function validation($request,$additionalValidation){
            
            $user_id = auth()->user()->id;

            $validationArr = [
                "first_name"          => 'required|no_special_char',
                "last_name"           => 'required|no_special_char',
                "salary"              => 'required|no_special_char|without_spaces|regex:/[0-9]+/|min:1', 
                "reference"           => 'required|without_spaces|no_special_char|max:20', 
                //"contact_number"      => 'required|no_special_char|digits:10', 
                "bank_id"             => 'required|exists:bank_details,id', 
                "account_type"        => [
                                           'required',
                                            Rule::in(Config('constants.paymentAccountType'))
                                           ], 
                "account_holder_name" => 'required|no_special_char',
                "account_number"      => 'required|without_spaces|no_special_char|integer|regex:/[0-9]+/',
                "branch_code"         => 'required|no_special_char'
            ];

            $validationArr=array_merge($validationArr,$additionalValidation);
        
            $validator = \Validator::make($request,$validationArr ,[
                "bank_id.required"     => "Please selet bank",
                "branch_code.required" => "Please select bank to populate branch code",
                "salary.required"=>"Amount is required",
                "salary.min"=>"Should be greater then 0",
                "id_number.required"=>"Employee Id is required",
                "id_number.unique"=>"Employee Id is already been taken"
            ]);

            $validator->sometimes(['contact_number'],'no_special_char|digits:10', function ($input) {
                
                return (strlen($input->contact_number)>0);
            });

            $validator->sometimes(['email'],'email|without_spaces', function ($input) {
                return (strlen($input->email)>0);
            });

            return $validator;
    }

    private function csvValidation($request){

            $firmId=auth()->user()->firm_id;
            $validator = \Validator::make($request, [
                "first_name"          => 'required|no_special_char',
                "last_name"           => 'required|no_special_char',
                "salary"              => 'required|no_special_char|without_spaces|regex:/[0-9]+/|min:1', 
                "contact_number"      => 'required|no_special_char|digits:10', 
                "reference"           => 'required|without_spaces|no_special_char|max:20', 
                "bank_name"           => 'required|exists:bank_details,bank_name',
                "account_type"        => [
                                           'required',
                                            Rule::in(Config('constants.paymentAccountType'))
                                           ],
                "account_holder_name" => 'required|no_special_char',
                "account_number"      => 'required|without_spaces|no_special_char|integer|regex:/[0-9]+/',
                "branch_code"         => 'required|no_special_char',
                "email" =>  'email|without_spaces',
                "id_number"=> [
                                'required',
                                'without_spaces',
                                'no_special_char',
                                'max:10',
                                Rule::unique('employees','id_number')->where('firm_id',$firmId)->where('employee_type','salaried')
                               ]
            ],[
                "salary.required"=>"Amount is required",
                "salary.min"=>"Should be greater then 0",
                "id_number.required"=>"Employee Id is required",
                "id_number.unique"=>"Employee Id is already been taken"
            ]);
            return $validator;
    }

    private function employeeSave($request,$employee){

            $employee->first_name           = $request->first_name;
            $employee->last_name            = $request->last_name; 
            $employee->email                = $request->email; 
            $employee->contact_number       = $request->contact_number;
            $employee->id_number            = $request->id_number;
            $employee->salary               = $request->salary;
            $employee->address              = $request->address;
            $employee->contact_number       = $request->contact_number;
            $employee->reference            = $request->reference;
            $employee->bank_id              = $request->bank_id;
            $employee->account_type         = $request->account_type;
            $employee->branch_code          = $request->branch_code; 
            $employee->account_holder_name  = $request->account_holder_name; 
            $employee->account_number       = $request->account_number;
            $employee->added_by             = auth()->user()->id;
            $employee->firm_id              = auth()->user()->firm_id;
            $employee->status              = 1;
            $employee->employee_type='salaried';
            return $employee;
    }

    private function saveCSVdata($importData){

            
            $bank_details = BankDetails::where('bank_name',trim($importData["bank_name"]))->first();

            $employee = new Employees();
            
            $employee->first_name           = $importData["first_name"];
            $employee->last_name            = $importData["last_name"]; 
            $employee->email                = $importData["email"];
            $employee->address              = $importData["address"]; 
            $employee->contact_number       = $importData["contact_number"];
            $employee->id_number            = $importData["id_number"];
            $employee->salary               = $importData["salary"];
            $employee->bank_id              = $bank_details->id;
            $employee->account_type         = $importData["account_type"];
            $employee->branch_code          = $importData["branch_code"]; 
            $employee->account_number       = $importData["account_number"];
            $employee->reference            = $importData["reference"];
            $employee->account_holder_name  = $importData["account_holder_name"]; 
            $employee->added_by             = auth()->user()->id;
            $employee->firm_id              = auth()->user()->firm_id;
            $employee->status=1;
            $employee->employee_type='salaried';
            return $employee;

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

              // 10MB in Bytes
            $maxFileSize = Config('constants.maxFileUploadSize'); 

              // Check file extension
            if(in_array(strtolower($extension),$valid_extension)){

                // Check file size
                if($fileSize <= $maxFileSize){

                    // File upload location
                    $location = public_path('uploads/employees');

                      // Upload file
                    $file->move($location,$filename);

                      // Import CSV to Database
                    $filepath = $location."/".$filename;

                    $dataArray = array("first_name","last_name","email","address","contact_number","id_number","salary","account_holder_name","bank_name","account_type","branch_code","account_number","reference");

                     $importData_arr=Helper::prepareCsvData($filepath,$dataArray,1);
                      Helper::deleteDir($location);
                     
                    // Insert to MySQL database
                    foreach($importData_arr as $key => $importData){
                            $importData['account_type']=Helper::strializeAccountType($importData['account_type']);
                            $validator = $this->csvValidation($importData);
                            if ($validator->fails()){
                                $tempemp            = new TempEmployees();
                                $dataset            = json_encode($importData);
                                $errorset           = json_encode($validator->errors()->keys());
                                $tempemp->dataset   = $dataset;
                                $tempemp->errorset  = $errorset;
                                $tempemp->file_name = $filename;
                                $tempemp->upload_type='salaried';
                                $tempemp->added_by  = auth()->user()->id;
                                $tempemp->save();
                            }else{

                                $employee = $this->saveCSVdata($importData);
                                $employee->save();
                            }
                    }
                    Session::flash('status','Import Successful.');
                    Session::flash('class','success');
                    return redirect('merchant/employees/temp/list');
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
        return redirect('merchant/employees/temp/list');
    }

    public function editTempEmp(Request $request){
        $errors = array();
        $request = json_decode($request->data, true);
        $validator = $this->csvValidation($request);
        if ($validator->fails()){
            $errorset           = json_encode($validator->errors());
            return \Response::json(array("errors" => $validator->getMessageBag()->toArray()));
            $errors = $validator->getMessageBag()->toArray();
        }else{
            
            $employee = $this->saveCSVdata($request);
            $employee->save();

            $id       = decrypt($request["id"]);
            $customer = TempEmployees::where(['id' => $id,'added_by' => auth()->user()->id,'upload_type'=>'salaried'])->delete();
            Session::flash('status','Employees updated Successfully.');
            Session::flash('class','success');
        }
        return \Response::json(array("errors" => $errors));
    }

    public function editMultipleTempEmployees(Request $request){

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
                $customer = TempEmployees::where(['id' => $id,'added_by' => auth()->user()->id,'upload_type'=>'salaried'])->delete();
                $status = false;
                $j++;
            }
        }
        if($i>0){
            Session::flash('error-msg',$i.' Records failed some validation.');
        }
        if($j>0){
            Session::flash('success-message', $j.' Records has been saved successfully .');
        }
        return \Response::json(array("errors" => $errors,"status" => $status));
    }

    public function deleteTempList(Request $request){

        if($request->isMethod('get')){
            $delete = TempEmployees::where('added_by',auth()->user()->id)->where('upload_type','salaried')->delete();
            if($delete){
                Session::flash('status','Employee deleted successfully');
                Session::flash('class','success');
            }else{
                Session::flash('status','Problem in deleting the record');
                Session::flash('class','dander');
            }
        }else{
            Session::flash('status','Problem in deleting the record');
            Session::flash('class','dander');
        }
        return redirect('merchant/employees/temp/list');
    }

    public function tempEmployeeDelete(Request $request,$id)
    {
        if($request->isMethod('delete')){
            $id = decrypt($id);
                      
            $employee = TempEmployees::where(['id',$id,'added_by',auth()->user()->id,'upload_type'=>'salaried'])->delete();
            if ($employee) {
                Session::flash('status','Employee deleted successfully');
                Session::flash('class','success');
            }else{
                Session::flash('status','Problem in deleting the record');
                Session::flash('class','danger');
            }
        }else{
           Session::flash('status','Sorry Your request Can not be processed');
           Session::flash('class','danger');
           
        }
        return redirect('merchant/employees/temp/list');
    }
    
    public function samplecsvDownload(){

            $file    = public_path(). "/uploads/sample_employees.csv";
            $headers = array(
                      'Content-Type: application/csv',
            );
            return Response::download($file,'sampleemployeefile.csv',$headers);
    }

    public function pendingList(){
        $pagename  = "Pending Employees";
        return view('merchant.employees.pendingList',compact('pagename'));
    }

    public function pendingAjaxUserList(Request $request){
        //print_r($request['columns']);
        // Table's primary key
        $primaryKey = 'id';
         
        
        
        $columns = $this->pendingListDtColumns();
        
        $firmId = auth()->user()->firm_id;
        
        $bindings=['salaried',0,$firmId];

        $whereConditions ="employees.employee_type=? and employees.status=? and firm_id=? ";
        $totalCount = DB::table('employees')
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

        
        $data = DB::table('employees')
                ->selectRaw('employees.*,bank_details.bank_name')
                ->leftJoin('bank_details', function ($join) {
                    $join->on('employees.bank_id', '=', 'bank_details.id');
                })  
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
       
        $totalFilteredCount = DB::table('employees')
                ->selectRaw('count(employees.'.$primaryKey.') totCount, employees.'.$primaryKey)
                ->leftJoin('bank_details', function ($join) {
                    $join->on('employees.bank_id', '=', 'bank_details.id');
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

    private function pendingListDtColumns(){
        $columns = array(
            array( 'dt' => 0,
                'formatter' => function( $d, $row ) {
                    return encrypt($row['id']);
                    //return $row['id'];
                }
            ),
            array( 'db' => 'id_number', 'dt' => 1 ),
            array( 'db' => 'first_name', 'dt' => 2 ),
            array( 'db' => 'last_name',  'dt' => 3 ),
            array( 'db' => 'email',     'dt' => 4 ),
            array(
                'dbAlias'   => 'bank_details',
                'db'        => 'bank_name',
                'dt'        => 5
            ),
            array('dbAlias'   => 'employees', 'db' => 'branch_code',     'dt' => 6 ),
            array( 'db' => 'account_number',     'dt' => 7 ),
            array( 'db' => 'salary',     'dt' => 8 ),
            array(
                'dbAlias'=>'employees',
                'number'=>true,
                'db'        => 'status',
                'dt'        => 9,
                'formatter' => function( $d, $row ) {
                    return Helper::getEmployeeStatusTitle($d);
                }
            ),
            array(
                
                'dt'        => 10,
                'formatter' => function( $d, $row ) {
                   return encrypt($row['id']);
                }
            )
        );

        return $columns;
    }

    public function updatePendingCustomer(Request $request){
        
        $employeeId   = decrypt($request->id);
        $pagename = "Payments - Update Employee";

        
        $firmId=Auth()->user()->firm_id;

        if($employeeId){
            
            $empRes = Employees::where(['firm_id'=>$firmId,'id'=>$employeeId,'status'=>0])->first();
            if(empty($empRes)){
                Session::flash('status','Requested record not found or you are not having permission to access that!');
                Session::flash('class','warning');
                return redirect('merchant/employees/pending-list');    
            }

            if($request->isMethod('post')){
                
                $firmId=Auth()->user()->firm_id;
                $id_number = $empRes->id_number;

                $additionalValidation=[
                "id_number"=> [
                                'required',
                                'without_spaces',
                                'no_special_char',
                                'max:10',
                                Rule::unique('employees','id_number')->where('firm_id',$firmId)->where('employee_type','salaried')
                               ]
                ];

                $validator    = $this->validation($request->all(),$additionalValidation);
                                        
                if ($validator->fails())
                {
                    return redirect()->back()->withErrors($validator)->withInput();
                    //return Redirect::to('merchant/employees/pendingupdate/'.encrypt($employeeId))->withErrors($validator)->withInput();
                }

                $customer = $this->employeeSave($request,$empRes);
                
                if($customer->save()){
                    Session::flash('status','Employee Updated successfully');
                    Session::flash('class','success');
                }else{
                     Session::flash('status','Unable to Update Employee! Please try again later');
                     Session::flash('class','danger');
                }
                return redirect('merchant/employees/pending-list');
            }
        }else{
            Session::flash('status','Problem in fetching the record');
            Session::flash('class','danger');
            return redirect('merchant/employees/pending-list');
        }
        

        return view('merchant.employees.pendingEmployeeUpdate',compact('pagename','empRes'));
    }

    public function viewPendingCustomer(Request $request){
        
        $employeeId   = decrypt($request->id);
        $pagename = "Employee - View";

        
        $firmId=Auth()->user()->firm_id;

        if($employeeId){
            
            $empRes = Employees::where(['firm_id'=>$firmId,'id'=>$employeeId,'status'=>0])->first();
            if(empty($empRes)){
                Session::flash('status','Requested record not found or you are not having permission to access that!');
                Session::flash('class','warning');
                return redirect('merchant/employees/pending-list');    
            }
        }else{
            Session::flash('status','Problem in fetching the record');
            Session::flash('class','danger');
            return redirect('merchant/employees/pending-list');
        }
        
        return view('merchant.employees.pendingEmployeeDetail',compact('pagename','empRes'));
    }

    public function statusUpdate(Request $request){
        $requestStatus=['status'=>402,'message'=>'Problem in updating the record',"type"=>"danger"];
        if($request->isMethod('post')){
            $employeeId=decrypt($request->employeeId);
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
            $employeeData = Employees::where('id',$employeeId)->where('employee_type','salaried')->where('firm_id',$firmId)->first();
            $empRes=json_decode(json_encode($employeeData),true);
            if($empRes){

                
                $validator=$this->statusUpdateValidation($empRes);
                if ($validator->fails() && $status==1 && $statusTitle=='approved')
                {
                    $requestStatus=['status'=>402,'message'=>'Some validation failed, update Employee info. And try again!',"type"=>"danger"];
                }else{
                    $employeeData->status = $status;
                    if ($employeeData->save()) {
                        $requestStatus=['status'=>201,'message'=>'Employee/s '.$statusTitle.' successfully',"type"=>"success"];
                    }
                }



                
            }
            
        }
        echo json_encode($requestStatus);
    }

    function statusUpdateValidation($empRes){
        
        $firmId=Auth()->user()->firm_id;
        $additionalValidation=[
                "id_number"=> [
                                'required',
                                'without_spaces',
                                'no_special_char',
                                'max:10',
                                Rule::unique('employees','id_number')->where('firm_id',$firmId)->where('employee_type','salaried')->ignore($empRes['id'])
                               ]
                ];

        $validator = $this->validation($empRes,$additionalValidation);
        return $validator;
    }

    public function mulStatusUpdate(Request $request){
        if($request->isMethod('post')){
            $i=0;
            $employeeIds=null;
            
            if($request->actionType=="approve"){
                $employeeIds=$request->toApprove;
                $statusType='Approved';
                $status=1;
            }elseif($request->actionType=="reject"){
                $employeeIds=$request->toReject;
                $statusType='Rejected';
                $status=3;
            }

            if(!is_null($employeeIds)){
                foreach ($employeeIds as $key => $eachUser) {
                    $employeeId=decrypt($eachUser);
                    $firmId = auth()->user()->firm_id;
                    $employeeData = Employees::where('id',$employeeId)->where('firm_id',$firmId)->where('employee_type','salaried')->first();

                    
                    if($employeeData){
                        $employeeData->status = $status;
                        $cusRes=json_decode(json_encode($employeeData),true);
                        $validator=$this->statusUpdateValidation($cusRes);
                        
                        if($validator->fails() && $status==1){

                        }else{
                            if ($employeeData->save()) {
                                $i++;
                            }    
                        }
                        
                    }
                }

                Session::flash('status',$i.' Employees '.$statusType.' Successfully');
                Session::flash('class','success');
            }
        }
        return redirect('merchant/employees/pending-list');
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
            $offsetDay=config('constants.sameDayPaymentOffset');
            $paymentCuttOffTime=config('constants.sameDayPaymentCutOffTime');
            if($request['service_type']=='dated'){
                $offsetDay=config('constants.oneDayPaymentOffset');
                $paymentCuttOffTime=config('constants.oneDayPaymentCutOffTime');
            }
            
            if(Helper::getSastTime()>=$paymentCuttOffTime){
                $offsetDay++;
            }
            $offsetDay=Helper::businessDayOffset($offsetDay);

            $additionalValidation=[
                "batch_name"    => 'required|no_special_char',
                "service_type"  =>[
                                        'required',
                                        Rule::in(['dated','sameday'])
                                    ],
                "payment_date"  => [
                                        'required',
                                        
                                        function ($attribute, $value, $fail) use ($request,$offsetDay){
                                            if (!empty($value) && strtotime($value)< strtotime("+".$offsetDay." day",strtotime(date('Y-m-d')))){
                                                $fail("Payment date should be after atleast ".$offsetDay."  days");
                                            }
                                        }
                                    ],
                "employee_selection"=>    [
                                        'required',
                                        Rule::in(['manual','csvupload'])
                                    ]
                
            ];
            $validator = \Validator::make($request->all(),$additionalValidation );
            
        

            if ($validator->fails()){
                return Redirect::to('merchant/employees/create-batch')->withErrors($validator)->withInput();
            }else{
                $postData=$request;
                if($request['employee_selection']=='manual'){
                    $pagename     = 'Select Employees';

                    return view('merchant.employees.select-employee',compact('pagename','postData'));
                }else{
                    $pagename     = 'Upload Batch CSV';
                    return view('merchant.employees.upload-batch',compact('pagename','postData'));
                }
                exit();
            }
        }

        return view('merchant.employees.create-batch',compact('pagename','firm','holidayDates'));
    }

    public function updateBatch(Request $request,$id){
        $user_id = auth()->user()->id;
        $firmId=Auth()->user()->firm_id;
        $batchId=decrypt($id);
        $firm = Firm::find($firmId);
        $pagename     = 'Update batch';
        $batchDetails=PaymentBatches::where(['id'=>$batchId,'firm_id'=>$firmId,'batch_status'=>'pending'])->first();
        if(is_null($batchDetails)){
            Session::flash('status','Batch not found');
            Session::flash('class','success');
            return redirect('merchant/employees/batch/pending');
        }
        $holidayData=PublicHolidays::where('holiday_date','>=',date('Y-m-d'))->get();
        $holidayDates=[];
        foreach ($holidayData as $key => $eachHoliday) {
            $holidayDates[]=Helper::convertDate($eachHoliday['holiday_date'],'m/d/Y');
        }
        if($request->isMethod('post')){
            $offsetDay=config('constants.sameDayPaymentOffset');
            $paymentCuttOffTime=config('constants.sameDayPaymentCutOffTime');
            if($request['service_type']=='dated'){
                $offsetDay=config('constants.oneDayPaymentOffset');
                $paymentCuttOffTime=config('constants.oneDayPaymentCutOffTime');
            }
            
            if(Helper::getSastTime()>=$paymentCuttOffTime){
                $offsetDay++;
            }
            $offsetDay=Helper::businessDayOffset($offsetDay);

            $additionalValidation=[
                "batch_name"    => 'required|no_special_char',
                "service_type"  =>[
                                        'required',
                                        Rule::in(['dated','sameday'])
                                    ],
                "payment_date"  => [
                                        'required',
                                        
                                        function ($attribute, $value, $fail) use ($request,$offsetDay,$batchDetails){

                                            if(strtotime($value)!=strtotime($batchDetails->payment_date) || $request->service_type!=$batchDetails->batch_service_type){
                                                if (!empty($value) && strtotime($value)< strtotime("+".$offsetDay." day",strtotime(date('Y-m-d')))){
                                                    $fail("Payment date should be after atleast ".$offsetDay."  days");
                                                }
                                            }
                                        }
                                    ]
                
            ];
            $validator = \Validator::make($request->all(),$additionalValidation );
            
        

            if ($validator->fails()){
                return Redirect::to('merchant/employees/update-batch/'.$id)->withErrors($validator)->withInput();
            }

            $batchDetails->batch_name=$request['batch_name'];
            $batchDetails->batch_service_type=$request['service_type'];
            $batchDetails->payment_date=Helper::convertDate($request['payment_date'],'Y-m-d');
            if($batchDetails->save()){
                Payments::where('batch_id', $batchDetails->id)->update(['payment_date' => $batchDetails->payment_date]);
                Session::flash('status','Batch updated successfuly!');
                Session::flash('class','success');
                return redirect('merchant/employees/batch/pending');
            }

        }

        return view('merchant.employees.update-batch',compact('pagename','firm','holidayDates','batchDetails','id'));
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
            array( 'db' => 'id_number',  'dt' => 1 ),
            array( 'db' => 'first_name', 'dt' => 2 ),
            array( 'db' => 'last_name',  'dt' => 3 ),
            array( 'db' => 'salary',     'dt' => 4),
            array( 'db' => 'reference',  'dt' => 5),
        );

        $firmId = auth()->user()->firm_id;
        
        $bindings=['salaried',$firmId,1];

        $whereConditions="employee_type=? and firm_id=? and status =?";
        $totalCount = DB::table('employees')
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
        
        $data = DB::table('employees')
                ->selectRaw('employees.*')
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
       
        $totalFilteredCount = DB::table('employees')
                ->selectRaw('count(employees.'.$primaryKey.') totCount, employees.'.$primaryKey)
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
            
            $selectedEmp=json_decode($request['employeeList']);
            $selectedAmount=json_decode($request['employeeAmount']);
            $selectedReff=json_decode($request['employeeReff']);
            $bindings=['salaried',$firmId,1];
            $whereConditions="employee_type=? and firm_id=? and status =?";
            $firmEmployees = DB::table('employees')
                ->selectRaw('id')
                ->whereRaw($whereConditions, $bindings)
                ->pluck('id')->toArray();
            
            
            
            
            $offsetDay=config('constants.sameDayPaymentOffset');
            $paymentCuttOffTime=config('constants.sameDayPaymentCutOffTime');
            if($request['service_type']=='dated'){
                $offsetDay=config('constants.oneDayPaymentOffset');
                $paymentCuttOffTime=config('constants.oneDayPaymentCutOffTime');
            }
            
            if(Helper::getSastTime()>=$paymentCuttOffTime){
                $offsetDay++;
            }
            $offsetDay=Helper::businessDayOffset($offsetDay);
            $paymentLedger=PaymentLedgers::where('firm_id',$firmId)->orderBy("id",'desc')->first();
            
            $additionalValidation=[
                "batch_name"    => 'required|no_special_char',
                "service_type"  =>[
                                        'required',
                                        Rule::in(['dated','sameday'])
                                    ],
                "payment_date"  => [
                                        'required',
                                        function ($attribute, $value, $fail) use ($request,$offsetDay){
                                            if (!empty($value) && strtotime($value)< strtotime("+".$offsetDay." day",strtotime(date('Y-m-d')))){
                                                $fail("Payment date should be after atleast ".$offsetDay."  days");
                                            }
                                        }
                                    ],
                "employee_selection"=>    [
                                        'required',
                                        Rule::in(['manual'])
                                    ],
                // "salaryBatchAmount"=>[
                //                         function ($attribute, $value, $fail) use ($paymentLedger){
                //                             if ($paymentLedger->closing_amount<$value){
                //                                 $fail("Not having enough balance to process this batch!,should be less then ".$paymentLedger->closing_amount);
                //                             }
                //                         }
                //                     ],
                
            ];
            $validator = \Validator::make($request->all(),$additionalValidation );

            if ($validator->fails()){
                return Redirect::to('merchant/employees/create-batch')->withErrors($validator)->withInput();
            }else{

                
                $paymentbatch=new PaymentBatches();
                $paymentbatch->firm_id=$firmId;
                $paymentbatch->batch_name=$request['batch_name'];
                $paymentbatch->batch_type='salary';
                $paymentbatch->batch_service_type=$request['service_type'];
                $paymentbatch->payment_date=Helper::convertDate($request['payment_date'],'Y-m-d');
                
                $paymentbatch->batch_status='pending';
                $paymentbatch->created_on=date('Y-m-d');
                $paymentbatch->created_by=$user_id;
                if($paymentbatch->save()){
                    $paymentbatchId=$paymentbatch->id;

                    foreach ($selectedEmp as $key => $eachEmployee) {
                        if(in_array($eachEmployee, $firmEmployees)){
                            $employee = Employees::find($selectedEmp[$key]);
                            $paymentRow=new Payments();
                            $paymentRow->batch_id=$paymentbatchId;
                            $paymentRow->employee_id=$selectedEmp[$key];
                            $paymentRow->firm_id=$firmId;
                            $paymentRow->payment_date=$paymentbatch->payment_date;
                            $paymentRow->amount=$selectedAmount[$key];
                            $paymentRow->bank_id=$employee->bank_id;
                            $paymentRow->account_type=$employee->account_type;
                            $paymentRow->account_holder_name=$employee->account_holder_name;
                            $paymentRow->branch_code     = $employee->branch_code; 
                            $paymentRow->account_number=$employee->account_number;
                            $paymentRow->reffrence=$selectedReff[$key];
                            $paymentRow->service_type=$request['service_type'];
                            $paymentRow->transmission_status=0;
                            $paymentRow->transaction_status=0;
                            $paymentRow->payment_status=0;
                            $paymentRow->created_at=date('Y-m-d');
                            $paymentRow->save();
                            
                        }
                    }



                    

                    Session::flash('status','Batch created successfully');
                    Session::flash('class','success');
                    
                    //return redirect('merchant/employees');
                    return redirect('merchant/employees/batch/pending');
                }
            }
        }else{
            return redirect('merchant/employees/batch/pending');
        }
    }

    public function samplebatchcsvDownload(){

            $file    = public_path(). "/uploads/sample_batch_employees.csv";
            $headers = array(
                      'Content-Type: application/csv',
            );
            return Response::download($file,'paymentbatch-sample.csv',$headers);
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
                    $location = public_path('uploads/employees');

                      // Upload file
                    $file->move($location,$filename);

                      // Import CSV to Database

                    $selectedEmployees=$this->readCsvFileData($filename);

                    if(sizeof($selectedEmployees)<=config('constants.maxRecordInCsvFile')){
                        $postData=$request;
                    
                        $pagename     = 'Confirm Batch Csv upload';
                        return view('merchant.employees.confirm-batch',compact('pagename','postData','selectedEmployees','filename'));
                        
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
        return Redirect::to('merchant/employees/create-batch')->withInput();
    }

    private function readCsvFileData($filename){
        $location = public_path('uploads/employees');
        // Import CSV to Database
        $filepath = $location."/".$filename;
        $firmId = auth()->user()->firm_id;
        // Reading file
        $file = fopen($filepath,"r");

        $importData_arr = array();
        $i = 0;

        $dataArray = array("employee_id","amount","reference");
        $selectedEmployees=[];
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

            $employeeData = Employees::where('id_number',$importedRow['employee_id'])->where('firm_id',$firmId)->where('employee_type','salaried')->where('status',1)->first();
            
            if($employeeData){
                $importedRow['emp']=$employeeData;
                $importedRow['id']=$employeeData->id;
                $selectedEmployees[]=$importedRow;
            }

            $i++;
        }
        fclose($file);
        return $selectedEmployees;
    }

    function savecsvbatch(Request $request){
        $user_id = auth()->user()->id;
        $firmId=Auth()->user()->firm_id;
        if($request->isMethod('post')){
            
            $selectedEmp=$request['employeeList'];
            $selectedAmount=$request['employeeAmount'];
            $selectedReff=$request['employeeReff'];
            $bindings=['salaried',$firmId,1];
            $whereConditions="employee_type=? and firm_id=? and status =?";
            $firmEmployees = DB::table('employees')
                ->selectRaw('id')
                ->whereRaw($whereConditions, $bindings)
                ->pluck('id')->toArray();
            
            
            $offsetDay=config('constants.sameDayPaymentOffset');
            $paymentCuttOffTime=config('constants.sameDayPaymentCutOffTime');
            if($request['service_type']=='dated'){
                $offsetDay=config('constants.oneDayPaymentOffset');
                $paymentCuttOffTime=config('constants.oneDayPaymentCutOffTime');
            }
            
            if(Helper::getSastTime()>=$paymentCuttOffTime){
                $offsetDay++;
            }
            $offsetDay=Helper::businessDayOffset($offsetDay);

            $additionalValidation=[
                "batch_name"    => 'required|no_special_char',
                "service_type"  =>[
                                        'required',
                                        Rule::in(['dated','sameday'])
                                    ],
                "payment_date"  => [
                                        'required',
                                        function ($attribute, $value, $fail) use ($request,$offsetDay){
                                            if (!empty($value) && strtotime($value)< strtotime("+".$offsetDay." day",strtotime(date('Y-m-d')))){
                                                $fail("Payment date should be after atleast ".$offsetDay."  days");
                                            }
                                        }
                                    ]
            ];
            $validator = \Validator::make($request->all(),$additionalValidation );
            
        

            if ($validator->fails()){
                return Redirect::to('merchant/employees/create-batch')->withErrors($validator)->withInput();
            }else{
                $paymentbatch=new PaymentBatches();
                $paymentbatch->firm_id=$firmId;
                $paymentbatch->batch_name=$request['batch_name'];
                $paymentbatch->batch_type='salary';
                $paymentbatch->batch_service_type=$request['service_type'];
                $paymentbatch->payment_date=$request['payment_date'];
                if($request['service_type']=='sameday'){
                    $paymentbatch->payment_date=date('Y-m-d');
                }
                
                $paymentbatch->batch_status='pending';
                $paymentbatch->created_on=date('Y-m-d');
                $paymentbatch->created_by=$user_id;
                if($paymentbatch->save()){
                    Helper::logStatusChange('payment_batch',$paymentbatch,"batch created");
                    $paymentbatchId=$paymentbatch->id;
                    $filepath=$request['file_path'];
                    $selectedEmp=$this->readCsvFileData($filepath);
                    $firm = Firm::find($firmId);
                    foreach ($selectedEmp as $key => $eachEmployee) {
                        $employee = Employees::where('id',$eachEmployee['id'])->where('firm_id',$firmId)->where('employee_type','salaried')->where('status',1)->first();
                        if($employee){
                            $paymentRow=new Payments();
                            $paymentRow->batch_id=$paymentbatchId;
                            $paymentRow->employee_id=$eachEmployee['id'];
                            $paymentRow->firm_id=$firmId;
                            $paymentRow->payment_date=$paymentbatch->payment_date;
                            $paymentRow->amount=$eachEmployee['amount'];
                            $paymentRow->bank_id=$employee->bank_id;
                            $paymentRow->account_type=$employee->account_type;
                            $paymentRow->account_holder_name=$employee->account_holder_name;
                            $paymentRow->account_number=$employee->account_number;
                            $paymentRow->reffrence=$eachEmployee['reference'];
                            $paymentRow->service_type=$request['service_type'];
                            $paymentRow->transmission_status=0;
                            $paymentRow->transaction_status=0;
                            $paymentRow->payment_status=0;
                            $paymentRow->created_at=date('Y-m-d');
                            $paymentRow->save();
                            Helper::logStatusChange('payment',$paymentRow,"Payment created");
                        }
                    }

                    unlink(public_path('uploads/employees').'/'.$filepath);
                    Session::flash('status','Batch created successfully');
                    Session::flash('class','success');
                    
                    return redirect('merchant/employees/batch/pending');
                }
            }
        }else{
            return redirect('merchant/employees/batch/pending');
        }
    }
}
