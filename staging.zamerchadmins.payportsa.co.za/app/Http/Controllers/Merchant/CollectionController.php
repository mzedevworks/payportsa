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
use App\Model\{Firm,BankDetails,Role,CompanyInformation,Employees,Customer,TempCollection,CustomerTransaction,Batch,CustomerCollection,Collections,Ledgers,ProfileTransactions};
//use Maatwebsite\Excel\Facades\Excel;
use Response;

class CollectionController extends Controller
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
  
    public function collectionList(){
        
        $pagename  = "Upload Debit Order List";
        $customers = TempCollection::where('added_by',auth()->user()->id)->where('is_deleted',0)->get();
        $existingCustomers = Customer::select('mandate_id','reference')->get();
        $mandateArray = array();
        $referenceArray = array();
        foreach ($existingCustomers as $key => $customer) {
            array_push($mandateArray, $customer->mandate_id);
            array_push($referenceArray, $customer->reference);
        }
        return view('merchant.collection.list',compact('customers','pagename','mandateArray','referenceArray'));
    }

    public function import(Request $request){

        if($request->file('file_name')!=''){
            
            $file = $request->file('file_name');
            // File Details 
            $filename  = rand().'_'.$file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
              
            $tempPath  = $file->getRealPath();
            $fileSize  = $file->getSize();
            $mimeType  = $file->getMimeType();

            // Valid File Extensions
            $valid_extension = array("csv","xls","xlsx");
            // 2MB in Bytes
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
                    $filepath = $location."/".$filename;

                    $dataArray = array("reference","mandate_id","first_name","last_name","email","contact_number","bank_name","account_type","branch_code","account_holder_name","account_number","once_off_amount","collection_date","collection_end_date","sms_notification","email_notification","entry_class");

                    $importData_arr=Helper::prepareCsvData($filepath,$dataArray,1);
                    Helper::deleteDir($location);

                    $batch_id = '';
                    
                    // Insert to MySQL database
                    foreach($importData_arr as $key => $importData){
                        $importData['account_type']=Helper::strializeAccountType($importData['account_type']);
                        $customer = Customer::where(['email'=> $importData['email'],'reference'=> $importData['reference'] , 'branch_code'=> $importData['branch_code'] , 'account_number'=> $importData['account_number'] ])->where('firm_id',auth()->user()->firm_id)->first();
                        if(!empty($customer)){
                            $validator = $this->transactionValidation($importData);
                            if ($validator->fails()){
                                $tempcollection  = new TempCollection();
                                $importData['entry_class']  =   array_search($request['entry_class'],Config('constants.entry_class'));
                                $dataset         = json_encode($importData);
                            
                                $errorset        = json_encode($validator->errors()->keys());
                                $tempcollection->dataset   = $dataset;
                                $tempcollection->errorset  = $errorset;
                                $tempcollection->file_name = $filename;
                                $tempcollection->added_by  = auth()->user()->id;
                                $tempcollection->save();
                            }else{
                                $customer = Customer::where(['email'=> $importData['email'],'merchant_id' => auth()->user()->merchant_id])->first();
                                if(!empty($customer)){
                                      $customer = $this->saveCSVdata($importData);
                                      $customer->save();
                                }
                                $batch_id = $this->saveTransaction($importData,$customer,$batch_id);
                                $customerCollection = $this->saveCustomerCollection($importData,$customer,$batch_id);
                                $customerCollection->save();
                            }
                        }else{
                            $validator = $this->csvValidation($importData);
                            if ($validator->fails()){
                                    $tempcollection  = new TempCollection();
                                    $dataset         = json_encode($importData);
                                    $errorset        = json_encode($validator->errors()->keys());
                                    $tempcollection->dataset   = $dataset;
                                    $tempcollection->errorset  = $errorset;
                                    $tempcollection->file_name = $filename;
                                    $tempcollection->added_by  = auth()->user()->id;
                                    $tempcollection->save();
                            }else{

                                    $customer = Customer::where(['email'=> $importData['email'],'firm_id' => auth()->user()->firm_id])->first();
                                    if(empty($customer)){
                                      $customer = $this->saveCSVdata($importData);
                                      $customer->save();
                                    }
                                    $batch_id = $this->saveTransaction($importData,$customer,$batch_id);
                                    $customerCollection = $this->saveCustomerCollection($importData,$customer,$batch_id);
                                    $customerCollection->save();
                            }
                        }
                    }
                    Session::flash('status','Collection had been imported successfully and batch is created');
                    Session::flash('class','success');
                    return redirect('merchant/collection/list');
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
        return redirect('merchant/collection/list');
    }

    private function createBatch(){
            $batch  = new Batch();
            $batch->batch_name  = date('Y-m-d').'_'.rand();
            $batch->type        = "collection";
            $batch->merchant_id = auth()->user()->id;
            $batch->firm_id     = auth()->user()->firm_id;
            $batch->save();
            return $batch->id;                  
    }

    private function saveTransaction($importData,$customer,$batch_id){
            if($batch_id==''){
                $batch_id =  $this->createBatch();
            }
            $transaction = new CustomerTransaction();
            $transaction->customer_id      = $customer->id;
            $transaction->firm_id          = auth()->user()->firm_id;
            $transaction->payment_date     = date('Y-m-d',strtotime($importData['collection_date'])); 
            $transaction->once_off_amount = $importData['once_off_amount'];  
            $transaction->status = 1;                             
            $transaction->batch_id = $batch_id;
            $transaction->added_formate = 2;
            $transaction->save();
            return $batch_id;
    }

    private function transactionValidation($request){
            
            $entryClassArray = array();
            foreach (Config('constants.entry_class') as $key => $value) {
                array_push($entryClassArray, $value);
            }
           $validator = \Validator::make($request, [
                    'mandate_id'          => 'required|regex:/[0-9]+/',
                    "first_name"          => 'required', 
                    "last_name"           => 'required', 
                    "contact_number"      => 'required','digits:10', 
                    "reference"           => 'required',
                    "bank_name"           => 'required|exists:bank_details,bank_name',
                    "account_type"        => [  'required',
                                                 Rule::in(Config('constants.accountType'))
                                             ],//'required|in:saving,cheque,Saving,Cheque', 
                    "account_number"      => 'required|integer|regex:/[0-9]+/', 
                    "branch_code"         => 'required',
                    "collection_date"     => 'required|date|after:'.date('Y-m-d').'',
                    "once_off_amount"     => 'required|integer|regex:/[0-9]+/',
                    "collection_end_date" => 'required|date|after:'.date('Y-m-d').'',
                    "entry_class"          => [  'required',
                                                 Rule::in($entryClassArray)
                                             ], 
            ]);
            return $validator;
    }
 
    private function csvValidation($request){

            $entryClassArray = array();
            foreach (Config('constants.entry_class') as $key => $value) {
                array_push($entryClassArray, $value);
            }
            $firmId = auth()->user()->firm_id;
            $request["account_type"] = strtolower($request["account_type"]);
            $validator = \Validator::make($request, [
                'mandate_id'          => 'required|regex:/[0-9]+/',
                "first_name"          => 'required', 
                "last_name"           => 'required', 
                "contact_number"      => 'required','digits:10', 
                "reference"           => 'required',
                "email"               => 'required',
                "bank_name"           => 'required|exists:bank_details,bank_name',
                "account_type"        => [  'required',
                                                 Rule::in(Config('constants.accountType'))
                                             ],//'required|in:saving,cheque,Saving,Cheque', 
                "account_number"      => 'required|integer|regex:/[0-9]+/', 
                "branch_code"         => 'required',
                "collection_date"     => 'required|date|after:' . date('Y-m-d') . '',
                "once_off_amount"     => 'required|integer|regex:/[0-9]+/',
                "collection_end_date" => 'required|date|after:' . date('Y-m-d') . '',
                "entry_class"          => [
                                            'required',
                                             Rule::in($entryClassArray)
                                         ], 
        ]);
        return $validator;  
    }

    private function saveCSVdata($request){
        
          $collection_start_date = strtotime($request["collection_date"]);
          if($request["collection_end_date"]!=''){                 
                  $collection_end_date   = strtotime($request["collection_end_date"]);  
                  $year1  = date('Y', $collection_start_date);
                  $year2  = date('Y', $collection_end_date);
                  $month1 = date('m', $collection_start_date);
                  $month2 = date('m', $collection_end_date);
                  $diff   = (($year2 - $year1) * 12) + ($month2 - $month1);
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
          $customer->reference              =   $request['reference'];
          $customer->bank_id                =   $bank_details->id;
          $customer->account_type           =   $request['account_type'];
          $customer->branch_code            =   $request['branch_code'];
          $customer->account_holder_name    =   $request['account_holder_name'];
          $customer->account_number         =   $request['account_number'];
          $customer->once_off_amount        =   $request['once_off_amount'];
          $customer->collection_date        =   Helper::convertDate($request['collection_date'],'Y-m-d');
          $customer->collection_end_date    =   Helper::convertDate($request['collection_end_date'],'Y-m-d');
          $customer->duration               =   $diff;
          $customer->firm_id                =   auth()->user()->firm_id;
          $customer->created_by             =   auth()->user()->id;
          $customer->next_collection_date   =   date('Y-m-d',strtotime($request['collection_date']));
          $customer->entry_class            =   array_search($request['entry_class'],Config('constants.entry_class'));
          $customer->status = 1;
          $customer->added_formate = 2;
          return $customer;
    }
    private function saveCustomerCollection($request,$customer,$batch_id){
            
          $bank_details = BankDetails::where('bank_name',trim($request['bank_name']))->first();
          $customerCollection                         =   new CustomerCollection();
          $customerCollection->mandate_id             =   $request['mandate_id'];
          $customerCollection->first_name             =   $request['first_name'];
          $customerCollection->last_name              =   $request['last_name'];
          $customerCollection->email                  =   $request['email'];
          $customerCollection->contact_number         =   $request['contact_number'];
          $customerCollection->reference              =   $request['reference'];
          $customerCollection->bank_id                =   $bank_details->id;
          $customerCollection->account_type           =   $request['account_type'];
          $customerCollection->branch_code            =   $request['branch_code'];
          $customerCollection->account_holder_name    =   $request['account_holder_name'];
          $customerCollection->account_number         =   $request['account_number'];
          $customerCollection->customer_id            =   $customer->id;
          $customerCollection->sms_notification       =   isset($request['sms_notification']) ? $request['sms_notification'] : '';
          $customerCollection->email_notification     =   isset($request['email_notification']) ? $request['email_notification'] : '';
          $customerCollection->batch_id               =   $batch_id;
          $customer->entry_class                      =  array_search($request['entry_class'],Config('constants.entry_class'));
          $customerCollection->merchant_id            =   auth()->user()->id;
          $customerCollection->entry_class            =   array_search($request['entry_class'],Config('constants.entry_class'));
          return $customerCollection;
    }

    public function editTempCustomerCollection(Request $request){

        $errors = array();
        $request = json_decode($request->data, true);
        $customer = Customer::where(['email'=> $request['email'],'reference'=> $request['reference'] , 'branch_code'=> $request['branch_code'] , 'account_number'=> $request['account_number'] ])->where('firm_id',auth()->user()->firm_id)->first();
        if(!empty($customer)){
            $validator = $this->transactionValidation($request);
        }else{
            $validator = $this->csvValidation($request);
        }
        if ($validator->fails()){
            $errorset           = json_encode($validator->errors());
            return \Response::json(array("errors" => $validator->getMessageBag()->toArray()));
            $errors = $validator->getMessageBag()->toArray();
        }else{
            
            $customer = Customer::where(['email'=> $request['email'],'firm_id' => auth()->user()->firm_id])->first();
            if(empty($customer)){
               $customer = $this->saveCSVdata($request);
               $customer->save();
            }

            $batch = CustomerCollection::where('merchant_id',auth()->user()->id)->orderBy('id','desc')->first();
            if(!empty($batch)){
                $batchId = $batch->batch_id;
            }else{
                $batchId = $this->createBatch();
            }

            $batch_id = $this->saveTransaction($request,$customer,$batchId);

            $customerCollection = $this->saveCustomerCollection($request,$customer,$batchId);
            $customerCollection->save();

            $id       = decrypt($request["id"]);
            $customer = TempCollection::where(['id' => $id,'added_by' => auth()->user()->id])->delete();
            Session::flash('status','Customer Collection updated Successfully.');
            Session::flash('class','success');
        }
        return \Response::json(array("errors" => $errors));
    }

    public function editMultipleTempCustomerCollection(Request $request){

        $status = true;
        $errors = array();
        $dataArray = json_decode($request->data, true);
        $i = $j = 0;
        foreach($dataArray as $request){
            
            $customer = Customer::where(['email'=> $request['email'],'reference'=> $request['reference'] , 'branch_code'=> $request['branch_code'] , 'account_number'=> $request['account_number'] ])->where('firm_id',auth()->user()->firm_id)->first();
            if(!empty($customer)){
                $validator = $this->transactionValidation($request);
            }else{
                $validator = $this->csvValidation($request);
            }
            if ($validator->fails()){
                $errorset           = json_encode($validator->errors());
                $status = false;
                $errors[] = $validator->getMessageBag()->toArray();
                $i++;
            }else{
                
                $customer = Customer::where(['email'=> $request['email'],'firm_id' => auth()->user()->firm_id])->first();
                 if(empty($customer)){
                    $customer = $this->saveCSVdata($importData);
                    $customer->save();
                }

                $batch = Batch::where('merchant_id',auth()->user()->id)->orderBy('id','desc')->first();
                if(!empty($batch)){
                    $batchId = $batch->id;
                }else{
                    $batchId = $this->createBatch();
                }

                $batch_id = $this->saveTransaction($request,$customer,$batchId);

                $customerCollection = $this->saveCustomerCollection($request,$customer,$batchId);
                $customerCollection->save();

                $id       = decrypt($request["id"]);
                $customer = TempCollection::where(['id' => $id,'added_by' => auth()->user()->id])->delete();
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

        $file    = public_path(). "/uploads/sampleEftCollections.csv";
        $headers = array(
                'Content-Type: application/csv',
        );
        return Response::download($file,'samplecollectionfile.csv',$headers);
    }

    public function deleteTempList(Request $request){

        if($request->isMethod('get')){
            $delete = TempCollection::where('added_by',auth()->user()->id)->delete();
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
        return redirect('merchant/collection/list');
    }

    public function tempCustomerDelete(Request $request,$id){

        if($request->isMethod('delete')){
            
            $id = decrypt($id);               
            $customerCollection = TempCollection::where('id',$id)->delete();
            if ($customerCollection) {
                Session::flash('status','Collection deleted successfully');
                Session::flash('class','success');
            }else{
                Session::flash('status','Problem in deleting the record');
                Session::flash('class','danger');
            }
        }else{
           Session::flash('status','Sorry Your request Can not be processed');
           Session::flash('class','danger');
           
        }
        return redirect('merchant/collection/list');
    }

    function failedTransactions(){
      // $users = Customer::where('votes', '>', 100)->paginate(15);
      $transactions=Collections::where('transmission_status', 2)->whereIn('transaction_status', [2])->orderBy('id', 'desc')->paginate(20);
      return view('merchant.collection.failedTransactions',compact('transactions'));
    }

    function transactionStatement(){
      $pagename = "Collection Statement";
      $firmId=auth()->user()->firm_id;
      
      $transactionLimit=ProfileTransactions::where('firm_id',$firmId)->where('product_type','collection_topup')->orderBy("transmission_date",'desc')->first();
      
      $transactedAmount=DB::select(DB::raw("SELECT sum(amount) as tot_amount FROM `collections` where transmission_status in (0,1,2) and collection_status=1 and DATE_FORMAT(payment_date, '%Y-%m')=:monthYear and firm_id=:firmId"),array('monthYear'=>date('Y-m'),'firmId'=>$firmId));
      $transactedAmount=$transactedAmount[0];
      //$statements=DB::select(DB::raw("select a.*,batches.batch_name from (select ledger.*,sum(ledger.amount) as trx_amount, collections.batch_id,collections.payment_date from ledger left JOIN collections on collections.id=ledger.collection_id where ledger.firm_id=:firmId and ledger.entry_type='cr' and collection_status=1 and collections.transmission_status in (1,2) group by batch_id UNION select ledger.*,ledger.amount as trx_amount, collections.batch_id,collections.payment_date from ledger left JOIN collections on collections.id=ledger.collection_id where ledger.firm_id=:firmId and ledger.entry_type='dr' and collection_status=1 and collections.transmission_status in (1,2) ) a left join batches on a.batch_id=batches.id ORDER by a.entry_date desc"),array('firmId'=>$firmId));
      $ledgerData=Ledgers::where('firm_id',$firmId)->whereIn('transaction_type',['failed_collection','batch_collection'])->orderBy('entry_date','desc')->get();

      return view('merchant.collection.transactionStatement',compact('pagename','firms','transactionLimit','ledgerData','fundLimit','transactedAmount'));
    }
}
