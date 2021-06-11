<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Mail;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //Commands\CustomerTransactionCmd::class,
            Commands\CreateBatchCmd::class,
            Commands\CollectionCmd::class,
            Commands\EftCreateCmd::class,
            Commands\TransactionDownloadCmd::class,
            Commands\CreateDatedPayCmd::class,
            Commands\CreateSamedayPayCmd::class,
            Commands\NmbDownloadCmd::class,
            Commands\OnedayPayReplydownloadCmd::class,
            Commands\PaymentBatchCmd::class,
            Commands\SamedayPayReplydownloadCmd::class,
            Commands\HolidayCmd::class,
            Commands\AvsReplydownloadCmd::class,
            Commands\CreateAvsCmd::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
        //transaction:create-eft               Create and upload transmission files for EFT
        //transaction:create-eft-transmission  Create and upload transmission files for EFT
        //transaction:create-nach              Create records which has to sent to bank
        //transaction:download-outputs 
        //gmt+2

        
        
        
        
        $schedule->command('transaction:create-batch-collection')->everyFiveMinutes()
           ->timezone('Africa/Johannesburg');
        
    //     $schedule->command('transaction:create-nach')->everyMinute()
    //    ->timezone('Africa/Johannesburg')
    //    ->between('15:30', '15:45');
        
       $schedule->command('transaction:create-eft')->dailyAt('15:35')
           ->timezone('Africa/Johannesburg');

       $schedule->command('transaction:download-outputs')->dailyAt('15:50')
           ->timezone('Africa/Johannesburg');
       $schedule->command('transaction:download-outputs')->dailyAt('16:05')
           ->timezone('Africa/Johannesburg');
           
       $schedule->command('transaction:download-outputs')->everyFiveMinutes()
           ->timezone('Africa/Johannesburg')
           ->between('18:05', '19:00');
        $schedule->command('transaction:download-outputs')->twiceDaily(8, 12)
           ->timezone('Africa/Johannesburg');

        
           // create and send oneday/dated payment batches to absa
        $schedule->command('transaction:create-oneday-payment-eft')->weekdays()->at('15:10')
            ->timezone('Africa/Johannesburg');
        $schedule->command('transaction:create-oneday-payment-eft')->saturdays()->at('07:40')
            ->timezone('Africa/Johannesburg');
        // $schedule->command('transaction:create-oneday-payment-eft')->weekends()->at('07:40')
        //     ->timezone('Africa/Johannesburg');

        //download and read oneday/dated payment batches result from absa
        
        $schedule->command('transaction:download-oneday-payment-outputs')->weekdays()->everyMinute()
             ->between('15:20', '16:00')
             ->timezone('Africa/Johannesburg');
        $schedule->command('transaction:download-oneday-payment-outputs')->weekends()->everyMinute()
             ->between('07:50', '08:30')
             ->timezone('Africa/Johannesburg');
        
        // create and send sameday payment batched to absa
        $schedule->command('transaction:create-sameday-payment-eft')->weekdays()->at('16:10')
            ->timezone('Africa/Johannesburg');
        $schedule->command('transaction:create-sameday-payment-eft')->saturdays()->at('08:10')
            ->timezone('Africa/Johannesburg');
        // $schedule->command('transaction:create-sameday-payment-eft')->weekends()->at('08:10')
        //     ->timezone('Africa/Johannesburg');
        //download and read sameday payment batches result from absa
        
        $schedule->command('transaction:download-sameday-payment-outputs')->weekdays()->everyMinute()
             ->between('16:20', '17:00')
             ->timezone('Africa/Johannesburg');
         $schedule->command('transaction:download-sameday-payment-outputs')->weekdays()->everyMinute()
             ->between('08:30', '09:20')
             ->timezone('Africa/Johannesburg');
        

        //update status of unsent payment batches
        $schedule->command('transaction:payment-batches')->dailyAt('18:00')
            ->timezone('Africa/Johannesburg');

        //create holidays which are repetative
        $schedule->command('transaction:notify-holidays')->dailyAt('23:40')
           ->timezone('Africa/Johannesburg');
        
        //Download and read notify me transactions
        $schedule->command('transaction:download-nmb-outputs')->everyFiveMinutes()
           ->timezone('Africa/Johannesburg');

       $schedule->command('transaction:create-avs-transmission')->everyThirtyMinutes()
       ->timezone('Africa/Johannesburg');
       $schedule->command('transaction:download-avs-outputs')->everyThirtyMinutes()
       ->timezone('Africa/Johannesburg');
           //->between('18:05', '19:00');
        //* * * * * php /var/www/html/payport/artisan schedule:run >> /dev/null 2>&1

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
