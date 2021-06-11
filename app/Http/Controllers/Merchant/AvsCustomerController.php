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
use App\Model\{Firm,Customer,TempCustomers,PublicHolidays,ProfileLimits,TempAvs,AvsEnquiry,AvsBatch,BankDetails};
//use Maatwebsite\Excel\Facades\Excel;
use Response;

class AvsCustomerController extends Controller
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
        $pagename  = "Realtime AVS";
        return view('merchant.avs-customer.customerList',compact('pagename'));
    }

    private function avsDtColumns(){
        $columns = array(
            array( 'db' => 'avs_type', 'dt' => 0 ),
            array( 'db' => 'beneficiary_id_number', 'dt' => 1 ),
            array( 'db' => 'beneficiary_initial',  'dt' => 2 ),
            array( 'db' => 'beneficiary_last_name',     'dt' => 3 ),
            array( 'db' => 'bank_name','dt'        => 4),
            array( 'db' => 'branch_code',     'dt' => 5 ),
            array( 'db' => 'bank_account_number',     'dt' => 6 ),
            array( 'db' => 'avs_status',     'dt' => 7 ),
            array( 'db' => 'created_on',
                    'dt' => 8,
                    'formatter' => function( $d, $row ) {
                        return Helper::convertDate($row['created_on'],'d-m-Y');
                    }
                ),
            array(
                
                'dt'        => 9,
                'formatter' => function( $d, $row ) {
                   return encrypt($row['id']);
                }
            )
        );

        return $columns;
    }


    public function ajaxRealtimeAvsList(Request $request){
        //print_r($request['columns']);
        // Table's primary key
        $primaryKey = 'id';
        
        $columns = $this->avsDtColumns();
        
        $firmId = auth()->user()->firm_id;
        
        $bindings=['single',$firmId];

        $whereConditions ="creation_type=? and firm_id=?";
        $totalCount = DB::table('avs_enquiries')
                ->selectRaw('count('.$primaryKey.') totCount')
                ->whereRaw($whereConditions, $bindings)
                ->get()
                ->toArray();

        $dtWhere=DatatableHelper::filter ( $request, $columns,$bindings);                                                   
        if($dtWhere!==""){
            $whereConditions.=" and ".$dtWhere;
        }
        $orderBy ='avs_enquiries.created_on DESC, '. DatatableHelper::order ( $request, $columns );
        $limit   = DatatableHelper::limit ( $request, $columns );

        
        $data = DB::table('avs_enquiries')
                ->selectRaw('avs_enquiries.*')
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
       
        $totalFilteredCount = DB::table('avs_enquiries')
                ->selectRaw('count(avs_enquiries.'.$primaryKey.') totCount, avs_enquiries.'.$primaryKey)
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
    

    public function batch()
    {     
        $pagename  = "Batch AVS";
        return view('merchant.avs-customer.batchList',compact('pagename'));
    }

    private function avsBatchDtColumns(){
        $columns = array(
            array( 'db' => 'batch_type', 'dt' => 0 ),
            array( 'db' => 'batch_name',     'dt' => 1 ),
            array( 'db' => 'status',     'dt' => 2 ),
            array( 'db' => 'created_at',
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

        return $columns;
    }

    public function ajaxAvsBatchList(Request $request){
        //print_r($request['columns']);
        // Table's primary key
        $primaryKey = 'id';
        
        $columns = $this->avsBatchDtColumns();
        
        $firmId = auth()->user()->firm_id;
        
        $bindings=[$firmId];

        $whereConditions ="firm_id=?";
        $totalCount = DB::table('avs_batches')
                ->selectRaw('count('.$primaryKey.') totCount')
                ->whereRaw($whereConditions, $bindings)
                ->get()
                ->toArray();

        $dtWhere=DatatableHelper::filter ( $request, $columns,$bindings);                                                   
        if($dtWhere!==""){
            $whereConditions.=" and ".$dtWhere;
        }
        $orderBy ='avs_batches.created_at DESC, '. DatatableHelper::order ( $request, $columns );
        $limit   = DatatableHelper::limit ( $request, $columns );

        
        $data = DB::table('avs_batches')
                ->selectRaw('avs_batches.*')
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
       
        $totalFilteredCount = DB::table('avs_batches')
                ->selectRaw('count(avs_batches.'.$primaryKey.') totCount, avs_batches.'.$primaryKey)
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

    public function batchCustomerList(Request $request)
    {     
        $pagename  = "AVS in Batch";
        $batchId   = decrypt($request->batchId);
        $firmId=Auth()->user()->firm_id;

        $batch=AvsBatch::where(['id'=>$batchId,'firm_id'=>$firmId])->first();
        if(is_null($batch)){

            return redirect('merchant/avs/history/batch');
        }
        return view('merchant.avs-customer.batchCustomerList',compact('pagename','batchId','batch'));
    }

    public function ajaxAvsBatchCustomerList(Request $request){
        //print_r($request['columns']);
        // Table's primary key
        $batchId   = decrypt($request->batchId);
        $firmId=Auth()->user()->firm_id;

        $batch=AvsBatch::where(['id'=>$batchId,'firm_id'=>$firmId])->first();
        //dd($batch);
        if(is_null($batch)){

            echo json_encode(
                array(
                        "draw" => isset ( $request['draw'] ) ?
                            intval( $request['draw'] ) :
                            0,
                        "recordsTotal"=> intval(0),
                        "recordsFiltered" => intval( 0 ),
                        "data" => []
                    )
            );
            die();
        }

        $primaryKey = 'id';
        
        $columns = $this->avsDtColumns();
        
        $firmId = auth()->user()->firm_id;
        
        $bindings=['batch',$firmId,$batchId];

        $whereConditions ="creation_type=? and firm_id=? and avs_batch_id=?";
        $totalCount = DB::table('avs_enquiries')
                ->selectRaw('count('.$primaryKey.') totCount')
                ->whereRaw($whereConditions, $bindings)
                ->get()
                ->toArray();

        $dtWhere=DatatableHelper::filter ( $request, $columns,$bindings);                                                   
        if($dtWhere!==""){
            $whereConditions.=" and ".$dtWhere;
        }
        $orderBy ='avs_enquiries.created_on DESC, '. DatatableHelper::order ( $request, $columns );
        $limit   = DatatableHelper::limit ( $request, $columns );

        
        $data = DB::table('avs_enquiries')
                ->selectRaw('avs_enquiries.*')
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
       
        $totalFilteredCount = DB::table('avs_enquiries')
                ->selectRaw('count(avs_enquiries.'.$primaryKey.') totCount, avs_enquiries.'.$primaryKey)
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

    public function showResult(Request $request){
        $pagename  = "AVS Result";
        $avsId   = decrypt($request->avsId);
        $firmId=Auth()->user()->firm_id;

        $avsRecord=AvsEnquiry::where(['id'=>$avsId,'firm_id'=>$firmId])->first();
        if(is_null($avsRecord)){
            Session::flash('status','Not a valid Avs record');
            Session::flash('class','danger');
            return redirect('merchant/avs/history/realtime');
        }
        $resultSet=[];
        if(!is_null($avsRecord->avs_json_result)){
            $resultSet=json_decode($avsRecord->avs_json_result,true);
        }
        return view('merchant.avs-customer.avsResults',compact('pagename','avsId','avsRecord','resultSet'));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function createAvs(Request $request)
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
        // Session::put('apiSessionKey',null);
        // Helper::sendRealTimeAvsEnquiry();

        
        $firmId=Auth()->user()->firm_id;
        $pagename = "Avs - New Request";
        
        return view('merchant.avs-customer.create',compact('pagename'));
    }

    public function saveAvsRequest(Request $request)
    {
        $firmId=Auth()->user()->firm_id;
        
        if($request->isMethod('post')){
            $data=['avs_type'=>$request->avsType,
                   'beneficiary_id_number'=>$request->identityNumber,
                    'beneficiary_initial'=>$request->initials,
                    'beneficiary_last_name'=>$request->lastName,
                    'bank_name'=>$request->bankName,
                    'branch_code'=>$request->branchCode,
                    'bank_account_number'=>$request->accountNumber,
                    'bank_account_type'=>$request->accountType,
                    'consent'=>$request->consent,
                ];
                    
            $validator = \Validator::make($data, [
                "avs_type"=> [
                                'required',
                                Rule::in(Config('constants.avsTypes'))
                           ],
                "beneficiary_id_number"     => 'required|without_spaces|no_special_char',
                "beneficiary_last_name"=> 'required|no_special_char',
                "bank_name"    => [
                                    'required',
                                    'no_special_char',
                                    Rule::exists('bank_details','id')->where(['is_realtime_avs'=>'yes','is_active'=>'yes'])
                                    ], 
                "branch_code"        => 'required|without_spaces|no_special_char',
                "bank_account_number"    => 'required|without_spaces|no_special_char|regex:/[0-9]+/', 
                "bank_account_type"    => 'required|without_spaces|no_special_char', 
                "consent"    => ['required',Rule::in(['yes','Yes','YES'])],
            ]);
            $validator->sometimes(['beneficiary_initial'],'required|no_special_char', function ($input) {
                return ($input->avs_type=="individual");
            });
            if ($validator->fails()){
                $errors = $validator->getMessageBag()->toArray();
                return Response::json(array("msg" => "validation failed","errors"=>$errors), 400);
            }else{
                
                $bankDetail=BankDetails::find($request->bankName);

                $avsEnquiry=new AvsEnquiry();
                $avsEnquiry->avs_type=$request->avsType;
                $avsEnquiry->beneficiary_id_number=$request->identityNumber;
                $avsEnquiry->beneficiary_initial=$request->initials;
                $avsEnquiry->beneficiary_last_name=$request->lastName;
                $avsEnquiry->bank_name=$bankDetail->bank_name;
                $avsEnquiry->branch_code=$request->branchCode;
                $avsEnquiry->bank_account_number=$request->accountNumber;
                $avsEnquiry->bank_account_type=$request->accountType;
                $avsEnquiry->creation_type='single';
                $avsEnquiry->firm_id=$firmId;
                $avsEnquiry->created_by=Auth()->user()->id;
                $avsEnquiry->created_on=date('Y-m-d H:i:s');
                $avsEnquiry->avs_status='pending';
                if($avsEnquiry->save()){
                    return Response::json(array("msg" => "Record created successfully",'status'=>'sucessful',"data"=>$avsEnquiry), 200);
                    
                    $avsResult=Helper::sendRealTimeAvsEnquiry($avsEnquiry);
                    if($avsResult==false){
                        //failed in authentication
                        $avsEnquiry->avs_status='failed';
                        $avsEnquiry->save();
                        return Response::json(array("msg" => "Unable to reach bank server. try Again later",'status'=>'failed',"data"=>$avsEnquiry), 200);
                    }else{
                        $avsEnquiry->avs_status='rejected';
                        $avsEnquiry->save();

                        //IF SUCCESSFUL
                        if(in_array($avsResult["Status"],[0,"000",2,3,5])){
                            $avsEnquiry->avs_status="sucessful";
                            $avsEnquiry->avs_json_result=json_encode($avsResult["ValueList"]);
                            $avsEnquiry->avs_reffrence=$avsResult["ReferenceNumber"];
                            $avsEnquiry->save();
                            $avsEnquiry->encryptedId=Helper::encryptVar($avsEnquiry->id);
                            return Response::json(array("msg" => "Record created successfully",'status'=>'sucessful',"data"=>$avsEnquiry), 200);

                        //IF PENDING
                        }elseif(in_array($avsResult["Status"],[1,"001"])){
                            
                            $avsEnquiry->avs_status="pending";
                            $avsEnquiry->avs_json_result=json_encode($avsResult["ValueList"]);
                            $avsEnquiry->avs_reffrence=$avsResult["ReferenceNumber"];
                            $avsEnquiry->save();
                            $avsEnquiry->encryptedId=Helper::encryptVar($avsEnquiry->id);
                            return Response::json(array("msg" => "Waiting for banks response",'status'=>'pending',"data"=>$avsEnquiry), 200);
                        //IF FAILED
                        }elseif(in_array($avsResult["Status"],[33,99,"033","099"])){
                            $avsEnquiry->avs_status="failed";
                            $avsEnquiry->save();
                            $avsEnquiry->encryptedId=Helper::encryptVar($avsEnquiry->id);
                            return Response::json(array("msg" => "something went wrong , please try again later!",'status'=>'failed',"data"=>$avsEnquiry), 200);
                        }
                    }
                    
                }else{
                    return Response::json(array("msg" => "Some error occured","errors"=>[]), 400);
                }
                
                    
                
            }
        }
        
    }

    public function recheckAjaxAvs(Request $request){
        if($request->ajax()) {
            $avsEnquiryId=$request->avsEnquiryId;
            $avsEnquiry=AvsEnquiry::find($avsEnquiryId);
            if(!is_null($avsEnquiry)){
                $avsResult=Helper::executeAvsReEnquiry($avsEnquiry);
                    if($avsResult==false){
                        //failed in authentication
                        $avsEnquiry->avs_status='failed';
                        $avsEnquiry->save();
                        return Response::json(array("msg" => "Unable to reach bank server. try Again later",'status'=>'failed',"data"=>$avsEnquiry), 200);
                    }else{
                        $avsEnquiry->avs_status='rejected';
                        $avsEnquiry->save();

                        //IF SUCCESSFUL
                        if(in_array($avsResult["Status"],[0,"000",2,3,5])){
                            $avsEnquiry->avs_status="sucessful";
                            $avsEnquiry->avs_json_result=json_encode($avsResult["ValueList"]);
                            $avsEnquiry->avs_reffrence=$avsResult["ReferenceNumber"];
                            $avsEnquiry->save();
                            $avsEnquiry->encryptedId=Helper::encryptVar($avsEnquiry->id);
                            return Response::json(array("msg" => "Record created successfully",'status'=>'sucessful',"data"=>$avsEnquiry), 200);

                        //IF PENDING
                        }elseif(in_array($avsResult["Status"],[1,"001"])){
                            
                            $avsEnquiry->avs_status="pending";
                            $avsEnquiry->avs_json_result=json_encode($avsResult["ValueList"]);
                            $avsEnquiry->avs_reffrence=$avsResult["ReferenceNumber"];
                            $avsEnquiry->save();
                            $avsEnquiry->encryptedId=Helper::encryptVar($avsEnquiry->id);
                            return Response::json(array("msg" => "Waiting for banks response",'status'=>'pending',"data"=>$avsEnquiry), 200);
                        //IF FAILED
                        }elseif(in_array($avsResult["Status"],[33,99,"033","099"])){
                            $avsEnquiry->avs_status="failed";
                            $avsEnquiry->save();
                            $avsEnquiry->encryptedId=Helper::encryptVar($avsEnquiry->id);
                            return Response::json(array("msg" => "something went wrong , please try again later!",'status'=>'failed',"data"=>$avsEnquiry), 200);
                        }
                        $avsEnquiry->encryptedId=Helper::encryptVar($avsEnquiry->id);
                        return Response::json(array("msg" => "Waiting for banks response",'status'=>'pending',"data"=>$avsEnquiry), 200);
                    }
            }else{
                return Response::json(array("msg" => "Some error occured","errors"=>[]), 400);
            }
        }else{
            return redirect('merchant/avs/create-batch');
        }
    }
    public function tempList(){
        
        $pagename  = "Upload AVS Request";
        $avsEnquiries = TempAvs::where('added_by',auth()->user()->id)->where('is_deleted',0)->get();
        
        return view('merchant.avs-customer.temp-list',compact('avsEnquiries','pagename'));
    }

    public function sampleCsvDownload(){

        $file    = public_path(). "/uploads/sample-avs-template.csv";
        $headers = array(
                'Content-Type: application/csv',
        );
        return Response::download($file,'avs-template.csv',$headers);
    }

    public function import(Request $request){
        if($request->isMethod('post')){
            $validator = \Validator::make($request->all(), [
                "avs_type"=> [
                                'required',
                                Rule::in(Config('constants.avsTypes'))
                           ],
                "concent_check"    => ['required',Rule::in(['yes','Yes','YES'])],
                "file_name"=>[
                                'required',
                                function ($attribute, $value, $fail) use ($request){
                                        $file = $request->file('file_name');
                                        $fileSize = $file->getSize();
                                        $extension = $file->getClientOriginalExtension();
                                        $mimeType = $file->getMimeType();
                                        $valid_extension = array("csv","xls","xlsx");
                                        $maxFileSize = Config('constants.maxFileUploadSize'); 
                                        if(!in_array(strtolower($extension),$valid_extension)){
                                            $fail("Please upload valid file type. allowed file formats are csv , xls and xlsx");
                                        }

                                        if($fileSize > $maxFileSize){
                                            $sizeInMb=(($maxFileSize/1024)/1024);
                                            $fail("File too large. File must be less than ".$sizeInMb."MB.");
                                        }
                                        
                                    }
                                ]
            ],['file_name.required'=>'Please upload file!']);
            if ($validator->fails()){
                return redirect()->back()->withErrors($validator)->withInput();
            }
            $avsType=$request->avs_type;
            if($request->file('file_name')!=''){
                $file = $request->file('file_name');             
                // File Details 
                $filename  = rand().'_'.$file->getClientOriginalName();
               
                  
                $tempPath = $file->getRealPath();
                // File upload location
                $location = public_path('uploads/avs');

                  // Upload file
                $file->move($location,$filename);

                  // Import CSV to Database
                $filepath = $location."/".$filename;

                $dataArray = array("beneficiary_id_number","beneficiary_initial","beneficiary_last_name","bank_name","branch_code","bank_account_number","bank_account_type");
                $importData_arr=Helper::prepareCsvData($filepath,$dataArray,1);
                
                Helper::deleteDir($location);
                // Insert to MySQL database
                $avsBatchId=null;
                $firmId = auth()->user()->firm_id;
                $fails=false;
                foreach($importData_arr as $key => $importData){
                                
                    $validator = $this->csvValidation($importData,$avsType);
                                
                    if ($validator->fails()){

                        $tempAvs           = new TempAvs();
                        $dataset           = json_encode($importData);
                        $errorset           = json_encode($validator->errors()->keys());
                        $tempAvs->avs_type = $request->avs_type;
                        $tempAvs->dataset   = $dataset;
                        $tempAvs->errorset  = $errorset;
                        $tempAvs->file_name = $filename;
                        $tempAvs->added_by  = auth()->user()->id;
                        $tempAvs->save();
                        $fails=true;
                    }else{
                        
                        if(is_null($avsBatchId)){
                            $avsbatch=new AvsBatch();
                            $avsbatch->firm_id=$firmId;
                            $avsbatch->batch_name="Avs Btach of ".date('d-m-Y H:i');
                            $avsbatch->batch_type=$avsType;
                            $avsbatch->status="pending";
                            $avsbatch->save();
                            $avsBatchId=$avsbatch->id;
                        }
                        $avsEnquiry = $this->saveCSVdata($importData);
                        $avsEnquiry->avs_batch_id=$avsBatchId;
                        $avsEnquiry->firm_id=$firmId;
                        $avsEnquiry->avs_type=$request->avs_type;
                        $avsEnquiry->save();
                    }
                }
                        
                        Session::flash('status','Import Successful.');
                        Session::flash('class','success');
                        if($fails==true){
                            return redirect('merchant/avs/create-batch');   
                        }else{
                            return redirect('merchant/avs/history/batch');
                        }
                        
            }else{
                Session::flash('status','File must be selected.');
            }
        }else{
            Session::flash('status','Invalid action!');
        } 
        Session::flash('class','danger');
        return redirect('merchant/avs/create-batch');
    }

    private function csvValidation($importData,$avsType){
            $firmId = auth()->user()->firm_id;
            
            $validator = \Validator::make($importData, [
                "beneficiary_id_number"     => 'required|without_spaces|no_special_char',
                "beneficiary_last_name"=> 'required|no_special_char',
                "bank_name"    => 'required|no_special_char', 
                "branch_code"        => 'required|without_spaces|no_special_char',
                "bank_account_number"    => 'required|without_spaces|no_special_char|integer|regex:/[0-9]+/', 
                "bank_account_type"    => 'required|without_spaces|no_special_char', 
            ]);
            $validator->sometimes(['beneficiary_initial'],'required|no_special_char', function ($input) use($avsType){
                return ($avsType=="individual");
            });
            
            return $validator;
    }

    private function saveCSVdata($data){

        $avsEnquiry                       =new AvsEnquiry();
        $avsEnquiry->beneficiary_id_number=$data['beneficiary_id_number'];
        $avsEnquiry->beneficiary_initial  =$data['beneficiary_initial'];
        $avsEnquiry->beneficiary_last_name=$data['beneficiary_last_name'];
        $avsEnquiry->bank_name            =$data['bank_name'];
        $avsEnquiry->branch_code          =$data['branch_code'];
        $avsEnquiry->bank_account_number  =$data['bank_account_number'];
        $avsEnquiry->bank_account_type    =$data['bank_account_type'];
        $avsEnquiry->avs_status           ='pending'; 
        $avsEnquiry->created_on           =date('Y-m-d H:i:s');
        $avsEnquiry->created_by           =auth()->user()->id;
        $avsEnquiry->creation_type        ='batch';
        
        return $avsEnquiry; 
    }

    public function updateTempAvs(Request $request){
        //die('vv');
        $errors = array();
        
        $dataToUpdate = json_decode($request->data,true);
        $id       = decrypt($dataToUpdate["id"]);
        $loggedInuser=auth()->user()->id;
        $firmId=auth()->user()->firm_id;

        $tempAvsData=TempAvs::where(['id' => $id,'added_by' => $loggedInuser])->first();
        
        if(is_null($tempAvsData)){

            return \Response::json(array("errors" => ['Unable to process your request!']));
        }

        //echo $request['account_type'];
        $validator = $this->csvValidation($dataToUpdate,$tempAvsData->avs_type);
        if ($validator->fails()){
            $errorset           = json_encode($validator->errors());
            return \Response::json(array("errors" => $validator->getMessageBag()->toArray()));
            //$errors = $validator->getMessageBag()->toArray();
        }else{
            $avsBatch=AvsBatch::where(['firm_id' => $firmId,'batch_type' => $tempAvsData->avs_type,'status'=>'pending'])->orderBy("created_at",'desc')->first();
            if(is_null($avsBatch)){
                $avsBatch=new AvsBatch();
                $avsBatch->firm_id=$firmId;
                $avsBatch->batch_name="Avs Btach of ".date('d-m-Y H:i');
                $avsBatch->batch_type=$tempAvsData->avs_type;
                $avsBatch->status="pending";
                $avsBatch->save();
            }
            
            
            $avsEnquiry = $this->saveCSVdata($dataToUpdate);
            $avsEnquiry->avs_batch_id=$avsBatch->id;
            $avsEnquiry->firm_id=$firmId;
            $avsEnquiry->avs_type=$tempAvsData->avs_type;
            $avsEnquiry->save();
            TempAvs::where(['id' => $id,'added_by' => $loggedInuser])->delete();
            Session::flash('status','Avs updated Successfully.');
            Session::flash('class','success');
        }
        return \Response::json(array("errors" => $errors));
    }

    public function deleteTempAvs(Request $request,$id){

        if($request->isMethod('delete')){
            
            $id = decrypt($id);               
            $tmpAvs = TempAvs::where('id',$id)->where('added_by',auth()->user()->id)->delete();
            if ($tmpAvs) {
                Session::flash('status','Avs record deleted successfully');
                Session::flash('class','success');
            }else{
                Session::flash('status','Problem in deleting the record');
                Session::flash('class','danger');
            }
        }else{
           Session::flash('status','Sorry Your request Can not be processed');
           Session::flash('class','danger');
           
        }
        return redirect('merchant/avs/create-batch');
    }

    public function editMultipleTempAvs(Request $request){

        $status = true;
        $errors = array();
        $dataArray = json_decode($request->data, true);
        $loggedInuser=auth()->user()->id;
        $firmId=auth()->user()->firm_id;

        $i = $j = 0;
        foreach($dataArray as $dataToUpdate){
            $id       = decrypt($dataToUpdate["id"]);
            $tempAvsData=TempAvs::where(['id' => $id,'added_by' => $loggedInuser])->first();
        
            if(is_null($tempAvsData)){

                continue;
            }

            $validator = $this->csvValidation($dataToUpdate,$tempAvsData->avs_type);
            if ($validator->fails()){
                $errorset           = json_encode($validator->errors());
                $status = false;
                $errors[] = $validator->getMessageBag()->toArray();
                $i++;
            }else{
                $avsBatch=AvsBatch::where(['firm_id' => $firmId,'batch_type' => $tempAvsData->avs_type,'status'=>'pending'])->orderBy("created_at",'desc')->first();
                if(is_null($avsBatch)){
                    $avsBatch=new AvsBatch();
                    $avsBatch->firm_id=$firmId;
                    $avsBatch->batch_name="Avs Btach of ".date('d-m-Y H:i');
                    $avsBatch->batch_type=$tempAvsData->avs_type;
                    $avsBatch->status="pending";
                    $avsBatch->save();
                }

                $avsEnquiry = $this->saveCSVdata($dataToUpdate);
                $avsEnquiry->avs_batch_id=$avsBatch->id;
                $avsEnquiry->firm_id=$firmId;
                $avsEnquiry->avs_type=$tempAvsData->avs_type;
                $avsEnquiry->save();
                TempAvs::where(['id' => $id,'added_by' => $loggedInuser])->delete();
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

    public function deleteTempList(Request $request){

        if($request->isMethod('get')){
            $delete = TempAvs::where('added_by',auth()->user()->id)->delete();
            if($delete){
                Session::flash('status','Records deleted successfully');
                Session::flash('class','success');
            }else{
                Session::flash('status','Problem in deleting the record');
                Session::flash('class','dander');
            }
        }else{
            Session::flash('status','Problem in deleting the record');
            Session::flash('class','dander');
        }
        return redirect('merchant/avs/create-batch');
    }

    // above this
    
}
