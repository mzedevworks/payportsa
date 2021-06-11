<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Model\{Customer,Collections,PublicHolidays,Batch};
use App\Helpers\Helper;

class CollectionCmd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // not in use , please ignore this command
    protected $signature = 'transaction:create-nach';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create records which has to sent to bank';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    
    public function handle()
    {   

        die();
        $currDate     = date('Y-m-d');
        $currDateTs=strtotime($currDate);
        
        $tomorrow    = date('Y-m-d',strtotime("+1 day",$currDateTs));
        
        
        $two_day    = date('Y-m-d',strtotime("+2 day",$currDateTs));
        
       
                            
        /*get all records whose status is approved and not deleted and service type of same day
        * along with that collection date has to be todays date or the next collection date is todays date
		*/
        $sameDayTransmission = Customer::
                                where(['status'=>1, 'is_deleted'=>0 ,'service_type'=> "Same Day",'cust_type'=>'normal'])
                               ->where(function($query) use ($currDate){
                                 $query->where('collection_date',$currDate)
                                 ->orWhere('recurring_start_date', $currDate)
                                 ->orWhere('next_collection_date', $currDate);
                                })
                               ->get();

        /*
        *get all records whose status is approved and not deleted and service type of 1 day or 2 day
        * along with that collection date has to be one day before today date or the next collection date as well
        * collection end date shoudn't be crossed
            We are will sending 1 day and 2 day transmision both as 1 day transmission
        */
        $otherDayTransmission = Customer::
                                where(['status'=>1, 'is_deleted'=>0,'cust_type'=>'normal'])
                                ->whereIn('service_type',["1 Day","2 Day"])
                               ->where(function($query) use ($tomorrow){
                                 $query->where('collection_date',$tomorrow)
                                 ->orWhere('recurring_start_date', $tomorrow)
                                 ->orWhere('next_collection_date', $tomorrow);
                                })
                               ->where(function($query) use ($tomorrow){
                                 $query->where('collection_end_date',$tomorrow)
                                 ->orWhereNull('collection_end_date');
                                 
                                })
                               ->get();
        //select * from `customers` where (`status` = 1 and `is_deleted` = 0) and `service_type` in ('1 Day', '2 Day') and (`collection_date` = '2020-05-04' or `recurring_start_date` = '2020-05-04' or `next_collection_date` = '2020-05-04') and (`collection_end_date` = '2020-05-04' or `collection_end_date` is null)
        // print_r($sameDayTransmission);
        // print_r($otherDayTransmission);
        // die();
        
        if(count($sameDayTransmission)>0){
            $this->getCollection($sameDayTransmission,$currDate);
        }

        if(count($otherDayTransmission)>0){
            $this->getCollection($otherDayTransmission,$tomorrow);
        }
        
    }

    /*
    *   create a copy in Collections table from cutomers table for the customers from whome collection is due as per the payment day.
    * 
    */
    private function getCollection($customers,$paymentDate){
        
        $today  = date('Y-m-d');
        foreach($customers as $customer){

                $recurringCollection =null;

                $onceOffCollection=$this->checkForOnceOffPayment($customer,$paymentDate);
                
                $custNextCollectionDateTs=strtotime($customer->next_collection_date);
                $custCollectionEndDateTs=strtotime($customer->collection_end_date);
                $paymentDateTs=strtotime($paymentDate);
                /*collection will be taken if recurring amount is greater-then 0 and collection is same is given payment date
                Collection End date should have been passed payment date
                */
               if($customer->recurring_amount >0 && $custNextCollectionDateTs==$paymentDateTs && $custCollectionEndDateTs>=$paymentDateTs){
                    $recurringCollection = new Collections();
                    $recurringCollection->amount = $customer->recurring_amount;
                    
                    //need to set next collection date as per the frequency
                    if(in_array($customer->debit_frequency,Config('constants.debitFrequency'))){

                        $customer->next_collection_date  = Helper::getNextCollectionDate($customer->next_collection_date,$customer->debit_frequency);
                        $customer->save();
                    }
                    
                }

                $paymentActionDate=$this->getPaymentDate($customer,$today);
                
                
                if(!is_null($onceOffCollection)){
                    $onceOffCollection->payment_type      = "onceoff";
                    $onceOffCollection->payment_date     = $paymentActionDate; 
                    // $batchData=$this->checkAndCreateBatch($customer,$paymentActionDate);
                    // $onceOffCollection->batch_id=$batchData->id;
                    $onceOffCollection=$this->verifySaveCollectionData($onceOffCollection,$customer);
                }

                if(!is_null($recurringCollection)){
                    $recurringCollection->payment_date     = $paymentActionDate;   
                    $recurringCollection->payment_type      = 'recurring';
                    // $batchData=$this->checkAndCreateBatch($customer,$paymentActionDate);
                    // $recurringCollection->batch_id=$batchData->id;
                    $recurringCollection=$this->verifySaveCollectionData($recurringCollection,$customer);
                }
                // echo $customer->id; 
                // echo "\n"; 

        }
        echo "done";
    }
    function checkAndCreateBatch($customer,$paymentActionDate){
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
        $batchData = Batch::where(['batch_type'=>$batchType,'firm_id'=>$customer->firm_id, 'action_date'=>$paymentActionDate])->first();
        if(empty($batchData)){
            $batchData=new Batch();
            $batchData->batch_type=$batchType;
            $batchData->firm_id=$customer->firm_id;
            $batchData->action_date=$paymentActionDate;
            $batchData->batch_name="Batch of ".date('d-m-Y',strtotime($paymentActionDate));
            $batchData->batch_status='pending';
            $batchData->created_at=date('Y-m-d');
            $batchData->save();
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
        
        


        $collection->customer_id  = $customer->id;
        $collection->firm_id      = $customer->firm_id;
        $collection->bank_id      = $customer->bank_id;
        $collection->account_type = $customer->account_type;
        $collection->branch_code  = $customer->branch_code;
        $collection->account_holder_name      = $customer->account_holder_name;
        $collection->account_number      = $customer->account_number;
        $collection->service_type = $customer->service_type;
        $collection->transmission_status      =0;
        $collection->entry_class  = $customer->firm->entry_class;
        $collection->collection_status      = 1;
        
        
        $collectionCheckSql=Collections::
                    where(['customer_id'=>$customer->id, 'payment_type'=>$collectionType,'payment_date'=>$paymentDate])
                    ->whereIn('transmission_status',[0,1,2])
                   ->get();


       if(count($collectionCheckSql)<=0){
            
            $collection->save();
        }
        
    }

    function checkForOnceOffPayment($customer,$paymentDate){
        $onceOffStatusRecord=null;
        //we need to take onceoff payment only when amount is greater then 0 , and collection date is 
        if($customer->once_off_amount>0 && $customer->collection_date==$paymentDate){
            
            $onceOffStatusRecord=$this->getCustomersOnceOffCollectionStatus($customer);
            //if no onceoff amout is taken before
            
            if($onceOffStatusRecord->isEmpty()){
                $onceOffStatusRecord=new Collections();
                $onceOffStatusRecord->amount  = $customer->once_off_amount;
            }
            
        }
        return $onceOffStatusRecord;
    }

    /*
        get status of once off collection record for the customer record
        this function will provide information that , do onceoff payment for this customer is already taken,
        or status of the transmission is still pending
    */
    function getCustomersOnceOffCollectionStatus($customer){
        //get row from table if transmission of any customer's transmission is 0=pending,1=transmitted,3=rejected but not accepted (2=accepted)  

        $onceOffStatus=  Collections::where('customer_id',$customer->id)
                                    ->whereIn('transmission_status',[0,1,2])
                                    ->orderBy('payment_date')
                                    ->get();
        return $onceOffStatus;
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

    private function calculatePaymentDate($offsetDate){
        $offsetDateTs=strtotime($offsetDate);
        $newDate     = date('Y-m-d',strtotime("+1 day",$offsetDateTs));
        $newDateTs=strtotime($newDate);
        
        //if given date is sunday
        if(date('N',$newDateTs)==7){
            $newDate=$this->calculatePaymentDate($newDate);
        }else{
            $publicHolidays=PublicHolidays::where('holiday_date','=',$newDate)
            ->get();
            if(count($publicHolidays)>0){
                $newDate=$this->calculatePaymentDate($newDate);  
            }
        }
        return $newDate;
    }
    
    function getPaymentDate($customer,$today){
        $todayTs=strtotime($today);
        
        $paymentDate=$today; //date of transaction
        

        
        /*
         we need to have a working to honour the transactions
         same day tranx, make sure transmission day is not sunday nor holiday
         For 1 day transmission make sure that , there should be a working day after the transmission date. and paymentdate should be of next working day
         tranmissionday -----working day ---- payment date

        check if it is a sunday*/
        if(date('N',$todayTs)==7){
            //get next valid daye
            $paymentDate=$this->calculatePaymentDate($today);
        }else{
            //if it false on public hiliday
            $publicHolidays=PublicHolidays::where('holiday_date','=',$today)
            ->get();
            if(count($publicHolidays)>0){
                $paymentDate=$this->calculatePaymentDate($today);  
            }
        }



        //get payment avoiding holidays in that
        if($customer->service_type=="Same Day"){
            return $paymentDate;
        }elseif(in_array($customer->service_type, ["1 Day","2 Day"])){
            $paymentDate=$this->calculatePaymentDate($paymentDate);
        }
        /*we are sending 1 day and 2 day service as 1 day service
        elseif($customer->service_type=="2 Day"){
            $paymentDate=$this->calculatePaymentDate($this->calculatePaymentDate($today));
        }*/
        return $paymentDate;
    }
    
  
}
