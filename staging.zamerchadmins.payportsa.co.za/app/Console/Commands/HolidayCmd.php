<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Model\{PublicHolidays};
use App\{User};
use Illuminate\Support\Facades\Mail;
use App\Helpers\Helper;

class HolidayCmd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transaction:notify-holidays';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Makes a holiday entry in database as per the avilable reccuring holidays';
    
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
        $today=date('Y-m-d');
        $holidays=PublicHolidays::where('holiday_date','=', $today)
            ->where('is_reocurr','=', 1)->get();
            //->update(['batch_status' => 'sent']);

        foreach ($holidays as $eachKey => $eachBatch) {
            $newHoliday=new PublicHolidays();
            $newHoliday->holiday_event=$eachBatch->holiday_event;
            $newHoliday->is_reocurr=$eachBatch->is_reocurr;
            
            $holidayTs=strtotime($eachBatch->holiday_date);
            $newHoliday->holiday_date= date('Y-m-d',strtotime("+1 year",$holidayTs));
            
            $holidayExist = PublicHolidays::where(['holiday_date'=>$newHoliday->holiday_date,'is_reocurr'=> $newHoliday->is_reocurr,'holiday_event'=>$newHoliday->holiday_event])->first();
            
            if(empty($holidayExist)){
                $newHoliday->save();
            }
        }
        

    }

   
   

    
  
}
