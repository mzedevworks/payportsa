<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Model\{Customer,Collections,PublicHolidays,Batch,Ledgers,ProfileTransactions,Firm};
use App\{User};
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Helpers\Helper;

class CreateBatchCmd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transaction:create-batch-collection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create batch of reccuring records which has to sent to bank';
    protected $correctionBuffer=0;
    protected $twoDayPayBuffer=2;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->correctionBuffer=Config('constants.currectionBufferDay');
        $this->twoDayPayBuffer=2;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    
    public function handle()
    {   

        
        //if cuttoff time is not reached, then we need deacrease a day of offset
        if(Helper::getSastTime()<config('constants.bankingCutOffTime')){
            $this->correctionBuffer--;
        }
        
        $paymentDate=$this->getBatchPaymentDate('2 Day');
        
        
        $batches=Batch::where('action_date','<', $paymentDate)
            ->where('batch_status','=', 'pending')->where('batch_type','reocurr-collection')->get();
            //->update(['batch_status' => 'sent']);

        $this->setBatchAsSent($batches);
        
        $this->batchCollectionType('reoccur','2 Day',$paymentDate);
        //$this->batchCollectionType('normal','2 Day',$paymentDate);
        
        while ($this->isHoliday($paymentDate)==true) {
            $paymentDate=$this->calculatePaymentDate($paymentDate);
            $this->batchCollectionType('reoccur','2 Day',$paymentDate);
            //$this->batchCollectionType('normal','2 Day',$paymentDate);
        }    

    }

    function setBatchAsSent($batches){
        foreach ($batches as $key => $eachBatch) {
            $collections = Collections::where('batch_id',$eachBatch->id)->where('collection_status',0)->get();
            foreach ($collections as $key => $eachCollection) {
                $this->_isProfileLimitCrossed($eachCollection);
            }

            $eachBatch->batch_status='sent';
            if($eachBatch->save()){
                Helper::logStatusChange('batch_collection',$eachBatch,"Marked as sent");
                $merchantAdmin=User::where('firm_id',$eachBatch->firm_id)->where('is_primary',1)->where('role_id',3)->first();
                Mail::raw("Batch '".$eachBatch->batch_name."' is sent to ABSA", function($message) use($merchantAdmin){
                   $message->from('lokesh.j@cisinlabs.com');
                   $message->to('lokesh.j@cisinlabs.com')->subject("A batch is sent to ABSA!");
               });
            }
            
        }
    }

    function _isProfileLimitCrossed($collectionData){
        $firmId=$collectionData->firm_id;
        $transactionLimit=ProfileTransactions::where('firm_id',$firmId)->where('product_type','collection_topup')->orderBy("transmission_date",'desc')->first();
        $paymentDateTs=strtotime($collectionData->payment_date);
        $transactedAmount=DB::select(DB::raw("SELECT sum(amount) as tot_amount FROM `collections` where transmission_status in (0,1,2) and firm_id=:firmId and collection_status=1 and DATE_FORMAT(payment_date, '%Y-%m')=:monthYear"),array('monthYear'=>date('Y-m',$paymentDateTs),'firmId'=>$firmId));
        $transactedAmount=$transactedAmount[0];

        if(($transactionLimit->closing_balance-$transactedAmount->tot_amount)>=$collectionData->amount){
            $collectionData->collection_status=1;
            Helper::logStatusChange('collection',$collectionData,"Approved");
        }else{
            $collectionData->collection_status=2;
            Helper::logStatusChange('collection',$collectionData,"Rejected: Limit crossed");
        }

        $collectionData->save();
        
    }
    /*
    get payment on basis of the offset. based on this date, Customers will be fetched to put into batch
    */
    function getBatchPaymentDate($serviceType){
        $currDate     = date('Y-m-d');
        $currDateTs=strtotime($currDate);

        
        if($serviceType=='2 Day'){
            $offsetDays=$this->correctionBuffer+$this->twoDayPayBuffer;
            
            $paymentDate = date('Y-m-d',strtotime("+".$offsetDays." day",$currDateTs));
        }
        

        return $paymentDate;
    }

    function batchCollectionType($collectionType,$serviceType,$paymentDate){
        if($collectionType=='reoccur'){
            $customers = Customer::where(['status'=>1, 'is_deleted'=>0,'cust_type'=>$collectionType])
                                ->where('service_type',$serviceType)
                                ->where(function($query) use ($paymentDate){
                                    $query->where(function($query) use ($paymentDate){
                                         $query->where('collection_date',"=",$paymentDate)
                                         ->orWhere('recurring_start_date',"=", $paymentDate)
                                         ->orWhere('next_collection_date',"=", $paymentDate);
                                    });
                                })
                               ->where(function($query) use ($paymentDate){
                                 $query->where('collection_end_date','>=',$paymentDate)
                                 ->orWhereNull('collection_end_date');
                                })->get();
        }elseif($collectionType=='normal'){
            $customers = Customer::where(['status'=>1, 'is_deleted'=>0,'cust_type'=>$collectionType])->where('service_type',$serviceType)
                                ->where('collection_date',"=",$paymentDate)->get();
        }
        
        
        if(count($customers)>0){
            $this->getCollection($customers);
        }
    }

    /*
    *   create a copy in Collections table from cutomers table for the customers from whome collection is due as per the payment day.
    * 
    */
    private function getCollection($customers){
        
        //$today  = date('Y-m-d');
        
        
        foreach($customers as $customer){

            $recurringCollection =null;
            $onceOffCollection=null;
            



            if($customer->once_off_amount>0){
                $onceOffCollection=$this->checkForOnceOffPayment($customer);
            }
            
            
            $custNextCollectionDateTs=strtotime($customer->next_collection_date);
            $custCollectionEndDateTs=strtotime($customer->collection_end_date);
           
            /*collection will be taken if recurring amount is greater-then 0 and collection is same is given payment date
            Collection End date should have been passed payment date
            */
           if($customer->recurring_amount >0 && $custCollectionEndDateTs >=$custNextCollectionDateTs){
                $recurringCollection=$this->checkForReoccurPayment($customer);
            }
            
            if(!is_null($onceOffCollection)){
                
                $onceOffCollection=$this->verifySaveCollectionData($onceOffCollection,$customer);
            }

            if(!is_null($recurringCollection)){
                
                $recurringCollection=$this->verifySaveCollectionData($recurringCollection,$customer);

                //need to set next collection date as per the frequency
                if(in_array($customer->debit_frequency,Config('constants.debitFrequency'))){

                    $customer->next_collection_date  = Helper::getNextCollectionDate($customer->next_collection_date,$customer->debit_frequency);
                    $customer->save();
                }
            }
            
        }
        echo "done";
    }

    function checkAndCreateBatch($customer,$paymentDate){
        $batchType="";

        switch ($customer->cust_type) {
            case 'normal':
                $batchType="normal-collection";
                break;
            case 'reoccur':
                $batchType="reocurr-collection";
                break;
            default:
                $batchType="reocurr-collection";
                break;
        }
        $batchData = Batch::where(['batch_type'=>$batchType,'firm_id'=>$customer->firm_id, 'action_date'=>$paymentDate,'batch_service_type'=>$customer->service_type])->first();
        if(empty($batchData)){
            $batchData=new Batch();
            $batchData->batch_type=$batchType;
            $batchData->batch_service_type=$customer->service_type;
            $batchData->firm_id=$customer->firm_id;
            $batchData->action_date=$paymentDate;
            $batchData->batch_name="Batch of ".date('d-m-Y',strtotime($paymentDate));
            $batchData->batch_status='pending';
            $batchData->created_at=date('Y-m-d');
            $batchData->save();

            Helper::logStatusChange('batch_collection',$batchData,"Created");
            $merchantAdmin=User::where('firm_id',$customer->firm_id)->where('is_primary',1)->where('role_id',3)->first();
            Mail::raw("A new batch is created with name '".$batchData->batch_name."'", function($message) use($merchantAdmin){
               $message->from('lokesh.j@cisinlabs.com');
               $message->to('lokesh.j@cisinlabs.com')->subject("A new batch is created!");
           });
        }
        return $batchData;
    }
    function verifySaveCollectionData($collection,$customer){
        
        $paymentDate=$collection->payment_date;
        $collectionType=$collection->payment_type;
        //echo $collection->payment_type;
        
        if($collection->payment_type=="onceoff"){
            $collection->reffrence=trim($this->createRefrenceString($customer,$paymentDate,'O'));
        }else{
            $collection->reffrence=trim($this->createRefrenceString($customer,$paymentDate,'R'));
        }
        
        


        $collection->customer_id      = $customer->id;
        $collection->firm_id          = $customer->firm_id;
        $collection->bank_id      = $customer->bank_id;
        $collection->account_type      = $customer->account_type;
        $collection->branch_code      = $customer->branch_code;
        $collection->account_holder_name      = $customer->account_holder_name;
        $collection->account_number      = $customer->account_number;
        $collection->service_type      = $customer->service_type;
        $collection->transmission_status      =0;
        $collection->entry_class      = $customer->firm->entry_class;
        
        
        $collectionCheckSql=Collections::
                    where(['customer_id'=>$customer->id, 'payment_type'=>$collectionType,'payment_date'=>$paymentDate])
                    //->whereIn('transmission_status',[0,1,2])
                   ->get();

        $isNew=true;
       if(count($collectionCheckSql)>0){
            $collection->id=$collectionCheckSql->id;
            $isNew=false;
        }
        //$this->_isProfileLimitCrossed($collection);
        $collection->save();
        if($isNew==true){
            Helper::logStatusChange('collection',$collection,"Collection created");
        }
        //return $collection;
    }

    /*
        get status of once off collection record for the customer record
        this function will provide information that , do onceoff payment for this customer is already taken,
        or status of the transmission is still pending
    */
    function checkForOnceOffPayment($customer){
        $onceOffCollection=null;
        $paymentDate=$customer->collection_date;
        
        while ($this->isHoliday($paymentDate)==true) {
            $paymentDate=$this->calculatePaymentDate($paymentDate);
        }
        $paymentDateTs=strtotime($paymentDate);

        $transmissionDateTs=strtotime("-".$this->twoDayPayBuffer." day",$paymentDateTs);
        $transmissionDate=date('Y-m-d',$transmissionDateTs);
        $collectionDate=$this->getBatchPaymentDate($customer->service_type);
        while ($this->isHoliday($collectionDate)==true) {
            $collectionDate=$this->calculatePaymentDate($collectionDate);
        }
        
        if(strtotime($collectionDate)==$paymentDateTs){
            $paymentDate=$this->getPaymentDate($customer,$transmissionDate);
            $onceOffTaken=Collections::where('customer_id',$customer->id)
                            //->where('payment_date',$paymentDate)
                            ->where('payment_type','onceoff')
                            ->whereIn('transmission_status',[0,1,2])
                            ->orderBy('payment_date')
                            ->get();
            //if onceoff amout is not taken before
            if($onceOffTaken->isEmpty()){
                $onceOffCollection=new Collections();
                $onceOffCollection->amount  = $customer->once_off_amount;
                $onceOffCollection->payment_type      = "onceoff";
                $onceOffCollection->payment_date     = $paymentDate; 
                
                $batchData=$this->checkAndCreateBatch($customer,$paymentDate);
                $onceOffCollection->batch_id=$batchData->id;
            }

        }
        return $onceOffCollection;
    }

    function checkForReoccurPayment($customer){
        $recurringCollection=null;
        $paymentDate=$customer->next_collection_date;
        while ($this->isHoliday($paymentDate)==true) {
            $paymentDate=$this->calculatePaymentDate($paymentDate);
        }
        $paymentDateTs=strtotime($paymentDate);
        $transmissionDateTs=strtotime("-".$this->twoDayPayBuffer." day",$paymentDateTs);
        $transmissionDate=date('Y-m-d',$transmissionDateTs);
        $collectionDate=$this->getBatchPaymentDate($customer->service_type);
        while ($this->isHoliday($collectionDate)==true) {
            $collectionDate=$this->calculatePaymentDate($collectionDate);
        }
        if(strtotime($collectionDate)==$paymentDateTs){
            $paymentDate=$this->getPaymentDate($customer,$transmissionDate);
            

            $reoccuringTaken=Collections::where('customer_id',$customer->id)
                            ->where('payment_date',$paymentDate)
                            ->where('payment_type','recurring')
                            //->whereIn('transmission_status',[0,1,2])
                            ->orderBy('payment_date')
                            ->get();
            
            if($reoccuringTaken->isEmpty()){
                $recurringCollection=new Collections();
                $recurringCollection->amount  = $customer->recurring_amount;
                $recurringCollection->payment_type      = "recurring";
                $recurringCollection->payment_date     = $paymentDate; 

                $batchData=$this->checkAndCreateBatch($customer,$paymentDate);
                $recurringCollection->batch_id=$batchData->id;
            }
            
        }
        return $recurringCollection;
    }

    private function calculatePaymentDate($offsetDate){
        $offsetDateTs=strtotime($offsetDate);
        $newDate     = date('Y-m-d',strtotime("+1 day",$offsetDateTs));
        $newDateTs=strtotime($newDate);
        
        //if given date is sunday
        if($this->isHoliday($newDate)==true){
            $newDate=$this->calculatePaymentDate($newDate);
        }
        
        return $newDate;
    }

    /*
        Generate a 30 character long refference string , which will be used to identify the payments in response of the server
        first 10 digit will be User Abrrivated code , this User code has to be resgitered on the ABSA.(this registeration is manual)
        next 20 can be anything
    */
    function createRefrenceString($customer,$paymentDate,$type){

        /*
        * trading name is important in the refrence string, based on this ABSA will Justify legally that who is taking payment
        */
        $userAbbrivatedCode = $customer->firm->trading_as;
        //There is ixed length of the name , so we might needto put fillers
        $fillerLen = 10-strlen($userAbbrivatedCode);
        //final name to be sent
        
        $userAbbrivatedCode=$userAbbrivatedCode.str_repeat(' ',$fillerLen);

        $customStrg=time().$type.$customer->mandate_id;
        if(strlen($customStrg)>20){
            $customStrg=substr($customStrg, 0, 20);
        }
        

        return $reffrenceStr=$userAbbrivatedCode.$customStrg;
    }

    function isHoliday($today){
        $todayTs=strtotime($today);
        //check if it is a sunday*/
        if(date('N',$todayTs)==7){
            //get next valid daye
            return true;
        }else{
            //if it falls on public holiday
            $publicHolidays=PublicHolidays::where('holiday_date','=',$today)
            ->get();
            if(count($publicHolidays)>0){
                return true;
            }
        }
        return false;
    }
    function getPaymentDate($customer,$today){
        $todayTs=strtotime($today);
        
        $paymentDate=$today; //date of transaction
        

        
        /*
         we need to have a working day to honour the transactions
         same day tranx, make sure transmission day is not sunday nor holiday
         For 1 day transmission make sure that , there should be a working day after the transmission date. and paymentdate should be of next working day
         tranmissionday -----working day ---- payment date

        check if it is a sunday*/
        if($this->isHoliday($today)==true){
            $paymentDate=$this->calculatePaymentDate($today);
        }
        



        //get payment avoiding holidays in that
        if($customer->service_type=="Same Day"){
            return $paymentDate;
        }elseif(in_array($customer->service_type, ["1 Day"])){
            $paymentDate=$this->calculatePaymentDate($paymentDate);
        }elseif($customer->service_type=="2 Day"){
            $paymentDate=$this->calculatePaymentDate($this->calculatePaymentDate($today));
        }
        return $paymentDate;
    }
    
  
}
