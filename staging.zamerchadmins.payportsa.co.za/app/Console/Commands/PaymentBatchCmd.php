<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Model\{PublicHolidays,PaymentBatches};
use App\{User};
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Helpers\Helper;

class PaymentBatchCmd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transaction:payment-batches';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform status change on the payment batches';
    protected $cuttoffMissed=false;
    
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


         
        //if cuttoff time is not reached, then we need deacrease a day of offset
        if(Helper::getSastTime()>=config('constants.paymentCutOffTime')){
            $this->cuttoffMissed=true;
        }
        
        $paymentDate=$this->getBatchPaymentDate();
        
        PaymentBatches::where('payment_date','<', $paymentDate)->where('batch_status','approved')
            ->update(['batch_status' => 'processed']);
        $pendingBatches=PaymentBatches::where('payment_date','<', $paymentDate)->where('batch_status','pending')->get();
        foreach ($pendingBatches as $key => $eachPendingBatch) {
            $pendingBatches->batch_status='cancelled';
            $pendingBatches->save();

            Helper::logStatusChange('payment_batch',$pendingBatches,"Batch cancelled");
            
            Payments::where(['batch_id' => $pendingBatches->id])->update(['payment_status' => 2]);
        }
            

    }

    

    
    /*
    get payment on basis of the offset. based on this date, Customers will be fetched to put into batch
    */
    function getBatchPaymentDate(){
        $paymentDate     = date('Y-m-d');
        $currDateTs=strtotime($paymentDate);
        $offsetDays=0;
        if($this->cuttoffMissed===true){
            $offsetDays++;
        }
        $paymentDate = date('Y-m-d',strtotime("+".$offsetDays." day",$currDateTs));
        while ($this->isHoliday($paymentDate)==true) {
            $offsetDays++;
            $paymentDate = date('Y-m-d',strtotime("+".$offsetDays." day",$currDateTs));
        }
        

        return $paymentDate;
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
    
    
  
}