<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Model\{Customer,Collections,PublicHolidays,Batch};
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
    protected $description = 'Create batch of reoccuring records which has to sent to bank';
    protected $batchOffsetDays=0;
    protected $sameDayBuffer=0;
    protected $oneDayBuffer=1;
    protected $twoDayBuffer=2;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->batchOffsetDays=Config('constants.batchOffsetDays');
        $this->sameDayBuffer=Config('constants.sameDayBuffer');
        $this->oneDayBuffer=Config('constants.oneDayBuffer');
        $this->twoDayBuffer=Config('constants.twoDayBuffer');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    
    public function handle()
    {   
        
        
        
        if(Helper::getSastTime()>=config('constants.bankingCutOffTime')){
            Batch::where('action_date','<=', date('Y-m-d'))
            ->where('batch_status','=', 'pending')
            ->update(['batch_status' => 'sent']);
        }else{
            Batch::where('action_date','<', date('Y-m-d'))
            ->where('batch_status','=', 'pending')
            ->update(['batch_status' => 'sent']);
        }
        
        $dateRange=$this->getDateRange('Same Day');
        
        $this->batchCollectionType('reoccur','Same Day',$dateRange['dateFrom'],$dateRange['dateUpto']);
        $this->batchCollectionType('normal','Same Day',$dateRange['dateFrom'],$dateRange['dateUpto']);
        

        $dateRange=$this->getDateRange('1 Day');
        $this->batchCollectionType('reoccur','1 Day',$dateRange['dateFrom'],$dateRange['dateUpto']);
        $this->batchCollectionType('normal','1 Day',$dateRange['dateFrom'],$dateRange['dateUpto']);

        $dateRange=$this->getDateRange('2 Day');
        $this->batchCollectionType('reoccur','2 Day',$dateRange['dateFrom'],$dateRange['dateUpto']);
        $this->batchCollectionType('normal','2 Day',$dateRange['dateFrom'],$dateRange['dateUpto']);
        
    }

    function getDateRange($serviceType){
        $currDate     = date('Y-m-d');
        $currDateTs=strtotime($currDate);
        if($serviceType=='Same Day'){
            $offsetDays=$this->batchOffsetDays+$this->sameDayBuffer;
            $dateFrom    = date('Y-m-d',strtotime("+".$this->sameDayBuffer." day",$currDateTs));
        }elseif($serviceType=='1 Day'){
            $offsetDays=$this->batchOffsetDays+$this->oneDayBuffer;
            $dateFrom    = date('Y-m-d',strtotime("+".$this->oneDayBuffer." day",$currDateTs));
        }elseif($serviceType=='2 Day'){
            $offsetDays=$this->batchOffsetDays+$this->twoDayBuffer;
            $offsetDays=$this->batchOffsetDays+$this->twoDayBuffer;
            $dateFrom    = date('Y-m-d',strtotime("+".$this->twoDayBuffer." day",$currDateTs));
        }
        $dateUpto    = date('Y-m-d',strtotime("+".$offsetDays." day",$currDateTs));

        return ['dateUpto'=>$dateUpto,'dateFrom'=>$dateFrom];
    }

    function batchCollectionType($collectionType,$serviceType,$paymentDateFrom,$paymentDateUpto){
        /*
        * get all records whose status is approved and not deleted and service type of same day
        * along with that collection date has to be todays date or the next collection date is todays date
        */

        $currDate     = date('Y-m-d');
        $currDateTs=strtotime($currDate);

        /*
        *get all records whose status is approved and not deleted and service type of 1 day or 2 day
        * along with that collection date has to be one day before today date or the next collection date as well
        * collection end date shoudn't be crossed
            We are will sending 1 day and 2 day transmision both as 1 day transmission
        */
        if($collectionType=='reoccur'){
            $customers = Customer::where(['status'=>1, 'is_deleted'=>0,'cust_type'=>$collectionType])
                                ->where('service_type',$serviceType)
                                ->where(function($query) use ($paymentDateFrom,$paymentDateUpto){
                                    $query->where(function($query) use ($paymentDateUpto){
                                         $query->where('collection_date',"<=",$paymentDateUpto)
                                         ->orWhere('recurring_start_date',"<=", $paymentDateUpto)
                                         ->orWhere('next_collection_date',"<=", $paymentDateUpto);
                                    })
                                    ->where(function($query) use ($paymentDateFrom){
                                         $query->where('collection_date',">",$paymentDateFrom)
                                         ->orWhere('recurring_start_date',">", $paymentDateFrom)
                                         ->orWhere('next_collection_date',">", $paymentDateFrom);
                                    });
                                })
                               ->where(function($query) use ($paymentDateUpto){
                                 $query->where('collection_end_date','>=',$paymentDateUpto)
                                 ->orWhereNull('collection_end_date');
                                })->get();
        }else{
            $customers = Customer::where(['status'=>1, 'is_deleted'=>0,'cust_type'=>$collectionType])
                                ->where('service_type',$serviceType)
                               ->where(function($query) use ($paymentDateFrom,$paymentDateUpto){
                                    $query->where(function($query) use ($paymentDateUpto){
                                        $query->where('collection_date','<=',$paymentDateUpto)
                                         ->orWhere('recurring_start_date','<=', $paymentDateUpto)
                                         ->orWhere('next_collection_date','<=', $paymentDateUpto);
                                    })
                                    ->where(function($query) use ($paymentDateFrom){
                                        $query->where('collection_date','>',$paymentDateFrom)
                                         ->orWhere('recurring_start_date','>', $paymentDateFrom)
                                         ->orWhere('next_collection_date','>', $paymentDateFrom);
                                    });
                                })->get();
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
        $currDate     = date('Y-m-d');
        $currDateTs=strtotime($currDate);
        $today    = date('Y-m-d',strtotime("+".$this->batchOffsetDays." day",$currDateTs));
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

    function checkAndCreateBatch($customer,$transmissionDate){
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
        $batchData = Batch::where(['batch_type'=>$batchType,'firm_id'=>$customer->firm_id, 'action_date'=>$transmissionDate])->first();
        if(empty($batchData)){
            $batchData=new Batch();
            $batchData->batch_type=$batchType;
            $batchData->firm_id=$customer->firm_id;
            $batchData->action_date=$transmissionDate;
            $batchData->batch_name="Batch of ".date('d-m-Y',strtotime($transmissionDate));
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


       if(count($collectionCheckSql)>0){
            $collection->id=$collectionCheckSql->id;
        }
        $collection->save();
        //return $collection;
    }

    /*
        get status of once off collection record for the customer record
        this function will provide information that , do onceoff payment for this customer is already taken,
        or status of the transmission is still pending
    */
    function checkForOnceOffPayment($customer){
        $onceOffCollection=null;
        $paymentDateTs=strtotime($customer->collection_date);
        $transmissionDateTs=strtotime("-".$this->batchOffsetDays." day",$paymentDateTs);
        $transmissionDate=date('Y-m-d',$transmissionDateTs);
        $dateRange=$this->getDateRange($customer->service_type);
        
        if(strtotime($dateRange['dateFrom'])<$paymentDateTs && strtotime($dateRange['dateUpto'])>=$paymentDateTs){
            $paymentDate=$this->getPaymentDate($customer,$transmissionDate);
            $onceOffTaken=Collections::where('customer_id',$customer->id)
                            ->where('payment_date',$paymentDate)
                            ->where('payment_type','onceoff')
                            //->whereIn('transmission_status',[0,1,2])
                            ->orderBy('payment_date')
                            ->get();
            //if no onceoff amout is taken before
            if($onceOffTaken->isEmpty()){
                $onceOffCollection=new Collections();
                $onceOffCollection->amount  = $customer->once_off_amount;
                $onceOffCollection->payment_type      = "onceoff";
                $onceOffCollection->payment_date     = $paymentDate; 
                
                $batchData=$this->checkAndCreateBatch($customer,$transmissionDate);
                $onceOffCollection->batch_id=$batchData->id;
            }

        }
        return $onceOffCollection;
    }

    function checkForReoccurPayment($customer){
        $recurringCollection=null;
        $paymentDateTs=strtotime($customer->next_collection_date);
        $transmissionDateTs=strtotime("-".$this->batchOffsetDays." day",$paymentDateTs);
        $transmissionDate=date('Y-m-d',$transmissionDateTs);
        $dateRange=$this->getDateRange($customer->service_type);
        
        if(strtotime($dateRange['dateFrom'])<$paymentDateTs && strtotime($dateRange['dateUpto'])>=$paymentDateTs){
            $paymentDate=$this->getPaymentDate($customer,$transmissionDate);
            $reoccuringTaken=Collections::where('customer_id',$customer->id)
                            ->where('payment_date',$paymentDate)
                            ->where('payment_type','recurring')
                            //->whereIn('transmission_status',[0,1,2])
                            ->orderBy('payment_date')
                            ->get();
            //if no onceoff amout is taken before
            if($reoccuringTaken->isEmpty()){
                $recurringCollection=new Collections();
                $recurringCollection->amount  = $customer->recurring_amount;
                $recurringCollection->payment_type      = "recurring";
                $recurringCollection->payment_date     = $paymentDate; 

                $batchData=$this->checkAndCreateBatch($customer,$transmissionDate);
                $onceOffCollection->batch_id=$batchData->id;
            }
            
        }
        return $recurringCollection;
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

    function getPaymentDate($customer,$today){
        $todayTs=strtotime($today);
        
        $paymentDate=$today; //date of transaction
        

        
        /*
         we need to have a working day to honour the transactions
         same day tranx, make sure transmission day is not sunday nor holiday
         For 1 day transmission make sure that , there should be a working day after the transmission date. and paymentdate should be of next working day
         tranmissionday -----working day ---- payment date

        check if it is a sunday*/
        if(date('N',$todayTs)==7){
            //get next valid daye
            $paymentDate=$this->calculatePaymentDate($today);
        }else{
            //if it falls on public holiday
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
