<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Model\{PaymentTransmissionRecords,CompanyBankInfo,Payments,PublicHolidays};
use Illuminate\Support\Facades\Hash;
use App\Helpers\Helper;
use phpseclib\Net\SFTP;
use Illuminate\Support\Facades\Mail;
class CreateSamedayPayCmd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transaction:create-sameday-payment-eft';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create and upload transmission files for same day Payments EFT';
    
    // bank variables
    protected $localDir='/'; //location where file will be generated
    protected $remoteDir='/';
    protected $environment='T'; //environment of the API
    protected $bankserUserCode = ''; //Usercode of the API user
    protected $electronicBankingSuitUserCode = '';
    protected $electronicBankingSuitUserName = '';
    protected $transmissionSeqNumber=0;
    // transmission data variables
    protected $paymentTransactions=[];
    protected $outputContent='';

    protected $transmissionFileNumber=0;
    protected $sentUserSetNumber=0;
    protected $currentUserSetNumber=0;


    protected $transactionCountOfToday=0;
    protected $firstTransactionSequence=0;
    protected $linesInTransmissionFile=0;
    

    
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->localDir=Config('constants.localSameDayPaymentFileStoragePath');
        $this->remoteDir=Config('constants.remoteSameDayPaymentOutputPath');
        $this->environment=Config('constants.payportEnv');
        $this->bankserUserCode = Config('constants.sameDayPaymentbankSerUserCode');
        $this->electronicBankingSuitUserCode = Config('constants.sameDayPaymentSuitUserCode');
        $this->electronicBankingSuitUserName = Config('constants.sameDayPaymentSuitUserName');

        //$this->companyBankInfo = CompanyBankInfo::first();
        $this->companyBankInfo = CompanyBankInfo::find(2);


        $this->transactionCountOfToday=$this->getTransmissionCountOfToday();
        $this->sentUserSetNumber=$this->getLastSuccessfulUserSetNumber();
        $this->getTransmissionNumber();
        
    }

    

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    
    public function handle()
    {
        
        //die("Stoped");
        if($this->isTransmissionResultAwaited()){

            die("Status of Last transmission is pending, You can't do next transmission");
            exit();
        }

        $this->fetchPaymentRecords();
        // Add transmission header in the file
        $this->linesInTransmissionFile++;
        $transmissionHeader = $this->generateTransmissionHeader();
        $output  = $transmissionHeader;

        $output .= $this->generateUserSet($this->paymentTransactions,"SAMEDAY");
       

        $this->linesInTransmissionFile++;
        $transmissionFooter = $this->generateTransmissionFooter();
        $output  = $transmissionFooter;

        //echo $this->outputContent;

        $this->uploadTransmissionFile();
    }

    function uploadTransmissionFile(){
        $sftp=Helper::getSftp();
        if($sftp){

            /*Writing transmission file with output*/
           $file_name = Config('constants.sameDayPaymentSuitFolder').".".Date("Ymdhis").".txt"; //name of the generated file
            $newfile   = public_path($this->localDir.$file_name);
            $file    = fopen($newfile, "w"); 
            fwrite($file, $this->outputContent); 
            fclose ($file);
            /*End of Writing transmission file with output*/
            
            $remote_file = $this->remoteDir.$file_name;

            //upload file on ftp of absa
            
            $sftp->put($remote_file, $newfile, SFTP::SOURCE_LOCAL_FILE);
            
            //create an object to insert new Transmission record
            $newTransmissionRecord=new PaymentTransmissionRecords();
            $newTransmissionRecord->transmission_type='sameday';
            $newTransmissionRecord->transmission_number=$this->transmissionFileNumber;
            $newTransmissionRecord->file_path=$this->localDir.$file_name;
            $newTransmissionRecord->transmission_date=date('Y-m-d');

            if($newTransmissionRecord->save()){
                $newTransId = $newTransmissionRecord->id;
                $this->updateTransactionStatus($this->paymentTransactions,$newTransId);
                
            }

           //  Mail::raw($file_name."is uploaded to ABSA at ".Date("d-m-Y H:i:s"), function($message)
           // {
           //     $message->from('rakesh.s@cisinlabs.com');
           //     $message->to('rakesh.s@cisinlabs.com')->subject("Cron job fro ABSA");
           // });
            die("File created and uploaded successfuly");
        }else{
            var_dump($sftp);
        }

    }

    function updateTransactionStatus($transactions,$transmissionId){
        foreach($transactions as  $eachTransaction) {
            $eachTransaction->transmission_id  =$transmissionId;
            $eachTransaction->transmission_status  =1;
            $eachTransaction->save();
        }
    }

    private function generateTransmissionFooter(){
        
        /*
        offset  name                                value
        1-3     Identifier                          999 (fixed) 
        4       Status                              T / L
        5-13    Number of records in transmission   total no of lines + 2 header line + 2 trailer line 
        14-200  spaces                              186 spaces
        */
        $transmissiontrailer = "999".$this->environment.sprintf('%09d', $this->linesInTransmissionFile).str_repeat(' ',186);
        $this->outputContent.=$transmissiontrailer;
        return $transmissiontrailer;
    }


    private function generateUserSet($paymentRecords,$serviceType){
        $output='';
        if(count($paymentRecords)>0){

            $this->generateUserHeader($paymentRecords,$serviceType);
        }
        $this->outputContent.=$output;
        return $output;
    }
    function userSetHead($paymentDates,$serviceType){
        $this->linesInTransmissionFile++;
        $userSetCreationDate =  date("ymd"); //date for creating userset
        $firstActionDateTs=strtotime(min($paymentDates));
        $lastActionDateTs=strtotime(max($paymentDates));

        $purgeDate     =  date('ymd', strtotime('+1 day', $lastActionDateTs)); //date after which this userset should not treated
        $firstActionDate      =  date("ymd",$firstActionDateTs); //First date of any transaction in the file
        $lastActionDate       =  date("ymd",$lastActionDateTs); //last of date of transaction in the file

        $firstSequenceNumber    =  sprintf('%06d', $this->firstTransactionSequence);
        $lastSequenceNumber     = sprintf('%06d', $this->transactionCountOfToday);
        $userGenerationNumber   =  sprintf('%04d', $this->currentUserSetNumber); 


        $userHeader= "020".$this->environment."04".$this->bankserUserCode.$userSetCreationDate.$purgeDate.$firstActionDate.$lastActionDate.$firstSequenceNumber.$userGenerationNumber.$serviceType.'YY'.str_repeat(' ',143)."\r";
        return $userHeader;
    }

    function userSetFoot($paymentDates,$debitcount,$contraCount,$debitTotal,$sumAccountNum){
        $this->linesInTransmissionFile++;
        $firstSequenceNumber    =  sprintf('%06d', $this->firstTransactionSequence);
        $lastSequenceNumber     = sprintf('%06d', $this->transactionCountOfToday);
        $userGenerationNumber   =  sprintf('%04d', $this->currentUserSetNumber); 

        $firstActionDateTs=strtotime(min($paymentDates));
        $lastActionDateTs=strtotime(max($paymentDates));

        $purgeDate     =  date('ymd', strtotime('+1 day', $lastActionDateTs)); //date after which this userset should not treated
        $firstActionDate      =  date("ymd",$firstActionDateTs); //First date of any transaction in the file
        $lastActionDate       =  date("ymd",$lastActionDateTs); //last of date of transaction in the file

        // code to add footer
        $creditRecords  = sprintf('%06d', $debitcount); // records of debits from the company account. Will be treated as credit records

        $debitRecords = $contraCount = sprintf('%06d',$contraCount);  //number of contra generated will be debit records, as this will be shown in the transaction statement of bank

        $totalcredit = sprintf('%012d', $debitTotal); //pre-pending zeros
        $totaldebit  = sprintf('%012d', $debitTotal); //pre-pending zeros
        $hash=$this->getAccountNumberHash($sumAccountNum,$contraCount);

        $userFooter= "020".$this->environment."92".$this->bankserUserCode.$firstSequenceNumber.$lastSequenceNumber.$firstActionDate.$lastActionDate.$debitRecords.$creditRecords.$contraCount.$totaldebit.$totalcredit.$hash.str_repeat(' ',111)."\r";
        return $userFooter;
    }

    
    private function generateUserHeader($paymentRecords,$servType){
        $bankserUserCode = $this->bankserUserCode;
        $this->currentUserSetNumber=$this->getNextUserSetNumber();

        $serviceType=$this->getServiceType($servType);

        
        /* variable for the transactions */
        
        $company_account_number    = sprintf('%011d', $this->companyBankInfo->account_number);
        $tax_code  = 0;

        $insertContra=false;
        $lastFirmId='';
        $lastPaymentDate='';
        $contraCount=0;
        $sumAccountNum = $creditcount=$debitTotal = 0;
        $transactionRecordStr="";
        $userSetTransmissionString="";
        $paymentDates=[];
        $insertUserSetHeader=false;
        $insertUserSetFooter=false;
        $amountsum = 0;
        $tax_code  = 0;

        $this->firstTransactionSequence=$this->getNextTransactionSequence();
        $this->transactionCountOfToday=$this->firstTransactionSequence;
        foreach ($paymentRecords as $key => $eachPayment) {

            if($key!==0){

                /*
                * add contra either it is last record
                * or it firmid is mismatch last user
                * or paymentDate is mismatch from last used
                */
                if($lastFirmId!==$eachPayment->firm_id ||  $lastPaymentDate!==$eachPayment->payment_date){
                    
                    $this->transactionCountOfToday=$this->getNextTransactionSequence();
                    
                    $this->linesInTransmissionFile++;
                    $transactionRecordStr .=  $this->contraRecords($amountsum,date('ymd',strtotime($lastPaymentDate)),$paymentRecords[$key-1]);
                    
                    $contraCount++;
                    $amountsum = 0 ;

                }
            }

            array_push($paymentDates, $eachPayment->payment_date);

            $actionDate  = date('ymd',strtotime($eachPayment->payment_date));
            $amount       = sprintf('%011d',$eachPayment->amount*100);
            $accountType=Helper::getAccountCodeAsAbsa($eachPayment->account_type);
            $amountsum = $amountsum+$amount;

            $usersequencenumber    = sprintf('%06d', (intval($this->transactionCountOfToday)));
            $accountHolderName=substr($eachPayment->account_holder_name, 0, 30);
            $custAccountNameFillerLen  = 30-strlen($accountHolderName);
            $custAccountName=strtoupper($accountHolderName).str_repeat(' ',$custAccountNameFillerLen);

            if(strlen($eachPayment->account_number)>11){
                $nonStandaredHomingAccountNumber = sprintf('%020d',$eachPayment->account_number);
                $homingAccountNumber = str_repeat('0',11);
            }else{
                $nonStandaredHomingAccountNumber = str_repeat('0',20);
                $homingAccountNumber = sprintf('%011d', $eachPayment->account_number);
            }

            $entryClass=88;
            // if($eachPayment->service_type=="Same Day"){
            //     $entryClass=44;
            // }

            $homingBranchCode=sprintf('%06d', $eachPayment->branch_code);

            
            $reference=$this->createRefrenceString($eachPayment);

            $this->linesInTransmissionFile++;
            $creditcount++;
            $transactionRecordStr .= "020".$this->environment."10".$this->companyBankInfo->branch_code.$company_account_number.$bankserUserCode.$usersequencenumber.$homingBranchCode.$homingAccountNumber.$accountType.$amount.$actionDate.$entryClass.$tax_code.str_repeat(' ',3).$reference.$custAccountName.$nonStandaredHomingAccountNumber.str_repeat(' ',16)."21".str_repeat(' ',27)."\r";

            //update the collection records with details
            $this->updateCollectionRecord($eachPayment);
            /*
            * as $key is not 0 , there is possibility that we may need to add contra, 
            *or else it is a last collection record
            * Contra will not be added on first record
            */
            if(($key+1)===sizeof($paymentRecords)){

                /*
                * add contra either it is last record
                * or it firmid is mismatch last user
                * or paymentDate is mismatch from last used
                */
                $this->transactionCountOfToday=$this->getNextTransactionSequence();
                
                $this->linesInTransmissionFile++;
                $transactionRecordStr .=  $this->contraRecords($amountsum,$actionDate,$eachPayment);
                
                $contraCount++;
                $amountsum = 0 ;

                
            }

            /* for userSet Footer */ 
            $accountNumber=$this->debitersAccountNumber($eachPayment);
            //$sumAccountNum  = $sumAccountNum + $accountNumber;
            $sumAccountNum=$this->sumAccountNumber($sumAccountNum,$accountNumber);
            
            $debitTotal     = $debitTotal+($eachPayment->amount*100);
            /* end of variables for the userSet footer */
            
            $lastPaymentDate=$eachPayment->payment_date;


            //company/firm/merchant is changed , so add footer
            if(($lastFirmId!='' && $lastFirmId!=$eachPayment->firm_id) || ($key+1)==sizeof($paymentRecords)){
                
                // need to add header here
                $userSetHeader=$this->userSetHead($paymentDates,$serviceType);
                
                $transactionRecordStr=$userSetHeader.$transactionRecordStr;
                $insertUserSetHeader=false;
                
                
                $userSetFooter=$this->userSetFoot($paymentDates,$creditcount,$contraCount,$debitTotal,$sumAccountNum);
                $transactionRecordStr=$transactionRecordStr.$userSetFooter;
                $insertUserSetFooter=false;
               
                $lastPaymentDate='';
                $paymentDates=[];
                $amountsum=0;
                $contraCount=0;
                $sumAccountNum = $creditcount=$debitTotal = 0;
                $userSetTransmissionString.=$transactionRecordStr;
                $transactionRecordStr='';
                $insertContra=false;
                $this->firstTransactionSequence=$this->getNextTransactionSequence();
                $this->transactionCountOfToday=$this->firstTransactionSequence;
            }else{
                $this->transactionCountOfToday=$this->getNextTransactionSequence();
            }
            
            
            $lastFirmId=$eachPayment->firm_id;
        }
        
        $this->outputContent.=$userSetTransmissionString;
        return $userSetTransmissionString;

    }


    /*
        Generate a 30 character long refference string , which will be used to identify the payments in response of the server
        first 10 digit will be User Abrrivated code , this User code has to be resgitered on the ABSA.(this registeration is manual)
        next 20 can be anything
    */
    function createRefrenceString($payment){

        /*
        * trading name is important in the refrence string, based on this ABSA will Justify legally that who is taking payment
        */
        $userAbbrivatedCode = $payment->firm->trading_as;
        //There is ixed length of the name , so we might needto put fillers
        $fillerLen = 10-strlen($userAbbrivatedCode);
        //final name to be sent

        $customStrg=$payment->reffrence;
        if(strlen($customStrg)>20){
            $customStrg=substr($customStrg, 0, 20);
        }elseif(strlen($customStrg)<20){
            $fillerLen = $fillerLen+20-strlen($customStrg);
        }
        $userAbbrivatedCode=$userAbbrivatedCode.str_repeat(' ',$fillerLen);

        return $reffrenceStr=$userAbbrivatedCode.$customStrg;
    }


    private function contraRecords($total_amount,$action_date,$payment){
        /*
            offset   name                               value
            1-3     Identifier                          020 - payments,  001 - collection
            4       Status                              T / L
            5-6     Bankserver record identifier        12- payments, 52 - collections
            7-12    User Branch                         dean's account branch code
            13-23   User Nominated Account number       dean's account number
            24-27   User Code                           same as code in user header
            28-33   User Sequence Number                start at 000001 every day, +1 for each line
            34-39   Homing branch                       dean's account branch code
            40-50   Homing AcNumber                     dean's account number
            51      Type of Account                      1
            52-62   Amount                              sum af all the transaction files amount
            63-68   Action Date                         date at which payment has to be done
            69-70   Entry Class                         10
            71-74   Filler                              00000 
            75-104  User reference 
            105-134 Filler                              30 spaces
            135-196 Filler                              61 spaces       
        */
        $bankserUserCode     =  $this->bankserUserCode;
        //get next sequence number to be get used in contra
        
        $usersequencenumber=sprintf('%06d', $this->transactionCountOfToday);
        
        $account_number    = sprintf('%011d', $this->companyBankInfo->account_number);
        $total_amount      = sprintf('%011d', $total_amount);
        
        //account holder Name to whome money has to transfered
        $accountHolderName = $payment->firm->trading_as;
        //There is ixed length of the name , so we might needto put fillers
        $accHolderNameFillerLen = 10-strlen($accountHolderName);

        //final name to be sent
        $userAbbrivatedName=$accountHolderName.str_repeat(' ',$accHolderNameFillerLen);

        $reference             = $userAbbrivatedName.'CONTRA'.strtoupper($this->generate_string(14));  
        
        $contraRecord = "020".$this->environment."12".$this->companyBankInfo->branch_code.$account_number.$bankserUserCode.$usersequencenumber.$this->companyBankInfo->branch_code.$account_number."1".$total_amount.$action_date."10".str_repeat('0',4).$reference.str_repeat(' ',30).str_repeat(' ',65)."\r";
        
        return $contraRecord;
    }


    private function updateCollectionRecord($transaction){
        $transmissionNumber=$this->transmissionFileNumber;
        $squenceNumber=$this->transactionCountOfToday;
        $userSetNumber=$this->currentUserSetNumber;
        $transaction->transmission_number=intval($transmissionNumber);
        $transaction->user_set_number=intval($userSetNumber);
        $transaction->sequence_number=intval($squenceNumber);
        $transaction->save();
    }

    function getAccountNumberHash($sumAccountNum,$contracount){
        
        $companyAccNum =  $this->companyBankInfo->account_number;
        
        $hash = ($sumAccountNum+(integer)$companyAccNum*$contracount)%1000000000000;;
        $hash = str_pad($hash, 12,"0", STR_PAD_LEFT);
        return $hash;
    }

    function debitersAccountNumber($collectionRow){
        /*
            acount number from which money has to be deducted. 
            Account number more then 11 digit, is treated as non-standard account
        */
        if(strlen($collectionRow->account_number)>11){
            $accountNumber = sprintf('%020d',$collectionRow->account_number);
        }else{
            $accountNumber = sprintf('%011d', $collectionRow->account_number);
        }
        
        return $accountNumber;
    }

    function sumAccountNumber($sumAccountNum,$accountNumber){
        $accountNumber = $accountNumber%100000000000;
        return $sumAccountNum+$accountNumber;
    }

    private function getNextTransactionSequence(){
        $lastSequence=$this->transactionCountOfToday;
        $newSequenceNumber=intval($lastSequence)+1;
        //as the sequence number has to six digit only, that is max 999999
        $newSequenceNumber=$newSequenceNumber%1000000;
        if($newSequenceNumber==0){
            $newSequenceNumber++;
        }
        $this->transactionCountOfToday=$newSequenceNumber;
        return $newSequenceNumber;
    }


    function getServiceType($servType){
        switch ($servType) {
            case 'SAMEDAY':
            case 'sameday':
                $servicetype     = "BATCH     "; 
                break;
            case 'ONE DAY':
            case 'ONEDAY':
            case 'oneday': 
                $servicetype     = "ONEDAYPAAF"; 
                break;
            default:
                $servicetype     = "BATCH     "; 
                break;
        }
        return $servicetype;
    }
    /*
    * user generation number , this means number of successful header sent till date
    *
    */
    private function getLastSuccessfulUserSetNumber(){

        //we need to consider only succefuly sent user header
        $userSetNumber = PaymentTransmissionRecords::where('user_set_status','ACCEPTED')
                                    ->where('transmission_type','sameday')
                                    ->where('combined_status','ACCEPTED')
                                    ->max('generation_number');
                                    //->get();
        $userSetNumber=intval($userSetNumber);
        $count = 0;
        if(isset($userSetNumber)){
            $count   = $userSetNumber;  
        }
        
        return $count;
    }

    /*
    *   get next userset number.
    *   User set , is set of group of transaction sent to ABSA. 
    *   Single transmission could have multiple UserSets.
    *   A userset number is of 4 digits, max upto 9999 , then after it has to be start from one again
    */
    private function getNextUserSetNumber(){
        $lastUserSetNumber=$this->sentUserSetNumber;
        $newUserSetNumber=intval($lastUserSetNumber)+1;
        //as the sequence number has to four digit only, that is max 9999
        $newUserSetNumber=$newUserSetNumber%10000;
        if($newUserSetNumber==0){
            $newUserSetNumber++;
        }
        return $newUserSetNumber;
    }

    /*
    * Get number of Successful transmission sent on today to ABSA
    */
    private function getTransmissionCountOfToday(){
        
        $dailysequence = PaymentTransmissionRecords::where(DB::raw("(DATE_FORMAT(created_at,'%Y-%m-%d'))"),date('Y-m-d'))
                                    ->where('combined_status','ACCEPTED')
                                    ->where('transmission_type','sameday')
                                    ->groupBy(DB::raw("(DATE_FORMAT(created_at,'%Y-%m-%d'))"))
                                    ->sum('sequence_number');
        $dailysequence=intval($dailysequence);
        if(isset($dailysequence)){
            $count   = $dailysequence;  
        }else{
            $count = 0;
        }
        return $count;
    }

    /*
    *  Create a tranmission header . there will be only one transmission header for every transmission file
    */
    private function generateTransmissionHeader(){
        
        /*
        offset  name                                 value
        1-3     Identifier                         000 (fixed) 
        4      Status                              T / L
        5-12   Transmission Date                   today's date (Ymd)
        13-17  electronic banking suit user code   07303
        18-47  electronic banking suit user name   PAYPORT CONSUTING PTY LTD(if length is less than 30 we will put blank space in the sting)
        48-54  transmission number                 starting value will be 0000000 and will be incremented by 1 on each successfull transmission   
        55-59  destination                          00000(fixed=value) 
        60-178  spaces
        179-198  spaces
        199      spaces
        */
        
        $transmissionDate             = Date("Ymd"); //set transmission date
        $electronicBankingSuitUserCode=$this->electronicBankingSuitUserCode;
        $electronicBankingSuitUserName=$this->electronicBankingSuitUserName;
        
        
        $username_length               = strlen($electronicBankingSuitUserName);
            
        $transmissionNumber            = sprintf('%07d', $this->transmissionFileNumber);  
        
        $transmissionheader = "000".$this->environment.$transmissionDate.$electronicBankingSuitUserCode.$electronicBankingSuitUserName.str_repeat(' ',30-$username_length).$transmissionNumber."00000".str_repeat(' ',119).str_repeat(' ',21)."\r"; 

        $this->outputContent.=$transmissionheader;
        return $transmissionheader;
    }

    /*
    * get transmission number which need to be send to ABSA.
    */
    private function getTransmissionNumber(){

        //identify the last transmission number which was sent to ABSA and its status was marked as ACCEPTED
        $transmission = PaymentTransmissionRecords::where('combined_status','ACCEPTED')->where('transmission_type','sameday')->orderBy('id','desc')->first();


        if(!isset($transmission) || is_null($transmission)){
            $transmissionNumber   = 1;  //if there is no transmission , then start with 1
        }else{
            
            //increase the transmission number by one. It will be next tranmission number
            $transmissionNumber= intval($transmission->transmission_number)+1; 

            //as tranmission number has to be seven digit only , upto max 9999999. after reaching max it should re-strat with 1
            $transmissionNumber=$transmissionNumber%10000000;
            if($transmissionNumber==0){
                $transmissionNumber++;
            }
        }
        $this->transmissionFileNumber=$transmissionNumber;
        return $transmissionNumber;
    }


    //eft transmissions Which need to sent to bank
    private function fetchPaymentRecords(){
        $this->paymentTransactions = $this->getRecords(['sameday']); //sameday or dated

        $totalRecords=sizeof($this->paymentTransactions);
        if($totalRecords<=0){
            die("No record to make transmission");
            exit();
        }
    }
    
    private function getRecords($serviceType){
        
        $paymentDate=$this->getPaymentDate($serviceType,date('Y-m-d'));
        
        //get those transmission whose tranmission status is pending (0)
        $customerTransactions =  Payments::where('transmission_status',0)
                                    ->where('payment_status',1)
                                    ->where('payment_date',$paymentDate)
                                    ->whereIn('service_type',$serviceType)
                                    ->orderBy('firm_id')
                                    ->orderBy('payment_date')
                                    ->get();
        
        return $customerTransactions;
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
    
    function getPaymentDate($serviceTypeArr,$today){
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
        if(in_array("sameday",$serviceTypeArr)){
            return $paymentDate;
        }elseif(in_array("oneday",$serviceTypeArr) || in_array("dated",$serviceTypeArr)){
            $paymentDate=$this->calculatePaymentDate($paymentDate);
        }
        
        return $paymentDate;
    }

    /*
        Check for transmission , if there is any pending results
        if there is any pending result, you can not create EFT further
    */
    private function isTransmissionResultAwaited(){
        $lastTranmission=PaymentTransmissionRecords::where('transmission_type','sameday')->orderBy('id','desc')->first(); 

        //check if reply of last transimission is received or not
        if($lastTranmission && is_null($lastTranmission->transmission_status)){
            return true;
        }else{
            return false;
        }
    }

    private function generate_string($strength) {
        $input = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $input_length = strlen($input);
        $random_string = '';
        for($i = 0; $i < $strength; $i++) {
            $random_character = $input[mt_rand(0, $input_length - 1)];
            $random_string .= $random_character;
        }
        return $random_string;
    }
}
