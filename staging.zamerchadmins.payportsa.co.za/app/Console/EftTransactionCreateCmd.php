<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Model\{TransmissionRecords,CompanyBankInfo,Collections};
use Illuminate\Support\Facades\Hash;
use App\Helpers\Helper;
use phpseclib\Net\SFTP;

class EftTransactionCreateCmd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transaction:create-eft-transmission';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create and upload transmission files for EFT';
    protected $localDir='/'; //location where file will be generated
    protected $remoteDir='/';
    protected $firstSequenceNumber=0;
    protected $userSequenceNumber=0;
    protected $userGenerationNumber=1;
    protected $sameDaytransactions=[];
    protected $twoDaytransactions=[];
    protected $outputContent='';
    protected $environment='T';
    protected $bankserUserCode = '';
    protected $lastUsedSequenceNumber=0;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->localDir=Config('constants.localCollectionFileStoragePath');
        $this->remoteDir=Config('constants.remoteCollectionOutputPath');
        $this->environment=Config('constants.payportEnv');
        $this->bankserUserCode = Config('constants.bankSerUserCode');

        //content sent into the transmission file
        $this->outputContent='';
        
        //sequence numbers of the transaction record
        $this->lastUsedSequenceNumber=$this->getLastTransmissionSequence();
        $this->firstSequenceNumber=$this->getNextSequenceNumber();
        $this->lastSequenceNumber=$this->firstSequenceNumber;
        $this->userSetNumber=$this->getLastSuccessfulUserSetNumber();
        $this->firstUserSetNumber=$this->userSetNumber;  //firstUserSetNumber of this transaction
        
        //eft transmissions Which need to sent to bank
        $this->sameDaytransactions = $this->getRecords(["Same Day"]);
        $this->oneDaytransactions = $this->getRecords(["1 Day","2 Day"]);
        $this->twoDaytransactions = [];//$this->getRecords("2 Day");
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    
    public function handle()
    {
        
        //get last transmission record
        $lastTranmission=TransmissionRecords::orderBy('id','desc')->first(); 

        //check if reply of last transimission is received or not
        if($lastTranmission && is_null($lastTranmission->transmission_status)){
            die("Status of Last transmission is pending, You can't do next transmission");
        }
        
        $file_name = Config('constants.bankingSuitFolder').".".Date("Ymdhis").".txt"; //name of the generated file
        $newfile   = public_path($this->localDir.$file_name);

        $output = ''; //content to be written in the transmission file

        //create an object to insert new Transmission record
        $newTransmissionRecord=new TransmissionRecords();

        // Add transmission header in the file
        $transmissionHeader = $this->transmissionheader();
        $output  = $transmissionHeader;

        $output .= $this->getUserSetData($this->sameDaytransactions,"SAMEDAY");
        $output .= $this->getUserSetData($this->oneDaytransactions,"ONE DAY");
        
        /*
        * below line is not of use , as it we are treating 2 day service as well for 1 day service
        *  Just kep line below so if if we need it lately , we need to only it 
        */
        $output .= $this->getUserSetData($this->twoDaytransactions,"TWO DAY");

        // Add transmission trailer in the file
        $transmissiontrailer = $this->transmissiontrailer();
        $output .= $transmissiontrailer;

        $sftp=Helper::getSftp();

        if($sftp){
                $file    = fopen($newfile, "w"); 
                fwrite($file, $output); 
                fclose ($file);
                
                $remote_file = $this->remoteDir.$file_name;
                //upload file on ftp of absa
                $sftp->put($remote_file, $newfile, SFTP::SOURCE_LOCAL_FILE);
                
                $newTransmissionRecord->transmission_number=$this->getTransmissionNsumber();
                $newTransmissionRecord->file_path=$this->localDir.$file_name;
                $newTransmissionRecord->transmission_date=date('Y-m-d');
                if($newTransmissionRecord->save()){
                    $newTransId = $newTransmissionRecord->id;
                    $this->updateTransactionStatus($this->sameDaytransactions,$newTransId);
                    $this->updateTransactionStatus($this->oneDaytransactions,$newTransId);
                    $this->updateTransactionStatus($this->twoDaytransactions,$newTransId);
                }

                //$this->updateTransmissionNumber(0); // Updaate the sequence if file has been generated successfully    
            }

            
    }

    function getUserSetData($transactions,$serviceType){
        $output='';
        if(count($transactions)>0){
            // Add User header in the file
            $userheader  = $this->userheader($serviceType,$transactions);

            // // Add transaction records in the file
            // $transactionRecords = $this->userSetTransactions($transactions);

            // // Add user trailer in the file
            // $userTrailer = $this->userTrailer($transactions);

            $output.=$userheader;//.$transactionRecords.$userTrailer;
        }
        $this->outputContent.=$output;
        return $output;
    }

    function updateTransactionStatus($transactions,$transmissionId){
        foreach($transactions as  $eachTransaction) {
            $eachTransaction->transmission_id  =$transmissionId;
            $eachTransaction->transmission_status  =1;
            $eachTransaction->save();
        }
    }
    
    private function getRecords($serviceType=["Same Day"]){
        //2 Day
        //get those transmission whose tranmission status is pending (0)
        $customerTransactions =  Collections::where('transmission_status',0)
                                    ->whereIn('service_type',$serviceType)
                                    ->orderBy('payment_date')
                                    ->get();
        return $customerTransactions;
    }

    
    /*
    *  Create a tranmission header . there will be only one transmission header for every transmission file
    */
    private function transmissionheader(){
        
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
        
        $transmission_date             = Date("Ymd"); //set transmission date
        $electronicBankingSuitUserCode = Config('constants.bankingSuitUserCode');
        $electronicBankingSuitUserName = Config('constants.bankingSuitUserName');
        
        $username_length               = strlen($electronicBankingSuitUserName);
            
        $transmissionNumber            = sprintf('%07d', $this->getTransmissionNumber());  
        
        $transmissionheader = "000".$this->environment.$transmission_date.$electronicBankingSuitUserCode.$electronicBankingSuitUserName.str_repeat(' ',30-$username_length).$transmissionNumber."00000".str_repeat(' ',119).str_repeat(' ',21)."\r"; 

        $this->outputContent.=$transmissionheader;
        return $transmissionheader;
    }

    /*
    * get transmission number which need to be send to ABSA.
    */
    private function getTransmissionNumber(){

        //identify the last transmission number which was sent to ABSA and its status was marked as ACCEPTED
        $transmission = TransmissionRecords::where('combined_status','ACCEPTED')->orderBy('id','desc')->first();


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
        
        return $transmissionNumber;
    }

    private function userheader($servType,$transactionsTodo){

        /* 
            offset   name                               value
            1-3     Identifier                          001 (fixed) 
            4       Status                              T / L
            5-6     Bankserver record identifier        04
            7-10    Bankserver user code                D237
            11-16   Bankserv Creation date              will be today's date except sunday's and holiday's 
            17-22   Bankserv Purge Date                 last action date in the transaction set+1 day
            23-28   First Action Date                   first action date in the transaction set
            29-34   Last Action Date                    last action date in the transaction set
            35-40   First Sequence Number               sequentially generated as per transmission date.           
            41-44   User Generation Number              Mustbe equal to the last accepted user generation+1
            45-54   Type of Service                     ONEDAYPAAF,TWODAYPAAF
            55-200   146 spaces
        */
        
        $bankserUserCode = $this->bankserUserCode;
        $this->lastSequenceNumber=$this->firstSequenceNumber;
        //get userSetNumber , which need to to be assigned
        $this->userSetNumber=$this->getNextUserSetNumber();
        
        $servicetype=$this->getServiceType($servType);
        
        $paymentDate = array(); //initialise empty array of unique payment dates
        
        /* variable for the transactions */
            $company_bank_info = CompanyBankInfo::first();
            $amountsum = 0;
        /* End of variables for the transactions*/ 
        
        /* variables for the userSet footer */
            $sumAccountNum    = $debittotal = 0;
        /* end of variables for the userSet footer */
        
        /*
        *   get all the dates of payment. there could be payment for multiple days,why?
        *   Because ,advance transactions can also be listed to send to the banks.
        *   This list will help in identifying First and last action Action Dates
        */
        $transactionRecord=""; //empty string for transactions
        $contracount=0; //number of contras in a userSet
        foreach ($transactionsTodo as $key => $eachTransaction) {
            
            /*
            * Sequence number for very first occurance is already Set, So get next sequence from next key
            */
            if($key>0){
                $this->lastSequenceNumber=$this->getNextSequenceNumber();
            }
            /*  get all collection fro the payment date in reverse order, 
            *   when last transaction of the date will be added
            *   we will add a Contra
            */
            $dateDetails = Collections::where('payment_date',$eachTransaction->payment_date)->where('service_type',$eachTransaction->service_type)->orderBy('id','desc')->first();
            
            array_push($paymentDate, $eachTransaction->payment_date);
            
            /* Tranmission variables */
                
                $amount       = sprintf('%011d',$eachTransaction->amount*100);
                
                $account_type = isset($eachTransaction->account_type) && $eachTransaction->account_type== "Cheque" ? 1 : 2;
                
                $amountsum = $amountsum+$amount;
                $transactionRecord.=$this->getTransactionRecord($eachTransaction,$company_bank_info);
                
                //update the collection records with details
                $this->updateCollectionRecord($eachTransaction);
                
                //if this is the last transaction Id for the date
                if($dateDetails->id==$eachTransaction->id){
                    
                    $actionDate  = date('ymd',strtotime($eachTransaction->payment_date));
                    $contracount++;
                    $transactionRecord .=  $this->contraRecords($amountsum,$actionDate);
                    $amountsum = 0 ;
                } 
            /** End of transmission variables */
            
            /* for userSet Footer */ 
            
            
            
            $accountNumber=$this->debitersAccountNumber($eachTransaction);
            $sumAccountNum  = $sumAccountNum + $accountNumber;
            
            $debittotal     = $debittotal+($eachTransaction->amount*100);
            /* end of variables for the userSet footer */
        }
        
        
        
        $firstActionDateTs=strtotime(min($paymentDate));
        $lastActionDateTs=strtotime(max($paymentDate));

        $userSetCreationDate =  date("ymd"); //date for creating userset
        $purgeDate     =  date('ymd', strtotime('+1 day', $lastActionDateTs)); //date after which this userset should not treated
        $firstActionDate      =  date("ymd",$firstActionDateTs); //First date of any transaction in the file
        $lastActionDate       =  date("ymd",$lastActionDateTs); //last of date of transaction in the file
    
        $firstSequenceNumber    =  sprintf('%06d', $this->firstSequenceNumber);
        $lastSequenceNumber     = sprintf('%06d', $this->lastSequenceNumber);
        $userGenerationNumber   =  sprintf('%04d', $this->userSetNumber); 

        /* for userSet Footer */ 
        $debitcount  = sprintf('%06d', $transactionsTodo->count());
        $creditcount = $contracount = sprintf('%06d',$contracount);  
        
        $totalcredit = sprintf('%012d', $debittotal); //pre-pending zeros
        $totaldebit  = sprintf('%012d', $debittotal); //pre-pending zeros
        
        $hash=$this->getAccountNumberHash($sumAccountNum,$contracount);
        /* end of variables for the userSet footer */
        
        $userHeader= "001".$this->environment."04".$bankserUserCode.$userSetCreationDate.$purgeDate.$firstActionDate.$lastActionDate.$firstSequenceNumber.$userGenerationNumber.$servicetype.'YY'.str_repeat(' ',143)."\r";
        $userFooter= "001".$this->environment."92".$bankserUserCode.$firstSequenceNumber.$lastSequenceNumber.$firstActionDate.$lastActionDate.$debitcount.$creditcount.$contracount.$totaldebit.$totalcredit.$hash.str_repeat(' ',111)."\r";
        $this->firstSequenceNumber=$this->getNextSequenceNumber();
        return $header.$transactionRecord.$userFooter;
    }
    
    function getTransactionRecord($transaction,$company_bank_info){
        $tax_code            = 0;
        $usersequencenumber    = sprintf('%06d', (intval($this->lastSequenceNumber)));
        $bankserUserCode       = $this->bankserUserCode;
        $dateDetails = Collections::where('payment_date',$transaction->payment_date)->where('service_type',$transaction->service_type)->orderBy('id','desc')->first();
            
        $reffrenceFillerLen=30-strlen($transaction->reffrence);
        $reference=$transaction->reffrence.str_repeat(' ',$reffrenceFillerLen);

        $action_date  = date('ymd',strtotime($transaction->payment_date));
        $amount       = sprintf('%011d',$transaction->amount*100);
        $account_type = isset($transaction->account_type) && $transaction->account_type== "Cheque" ? 1 : 2;
        
        $custAccountNameFillerLen  = 30-strlen($transaction->account_holder_name);
        $custAccountName=strtoupper($transaction->account_holder_name).str_repeat(' ',$custAccountNameFillerLen);

        if(strlen($transaction->account_number)>11){
            $non_standared_homing_account_number = sprintf('%020d',$transaction->account_number);
            $homing_account_number = str_repeat('0',11);
        }else{
            $non_standared_homing_account_number = str_repeat('0',20);
            $homing_account_number = sprintf('%011d', $transaction->account_number);
        }

        $entryClass=$transaction->customer->entry_class;
        if($transaction->service_type=="Same Day"){
            $entryClass=44;
        }

        $homingBranchCode=sprintf('%06d', $transaction->branch_code);
        //echo $transaction->payment_date;
        
        //account holder Name to whome money has to transfered
        $accountHolderName = $company_bank_info->account_holder_name;
        //There is ixed length of the name , so we might needto put fillers
        $accHolderNameFillerLen = 10-strlen($accountHolderName);

        //final name to be sent
        $userAbbrivatedName=$accountHolderName.str_repeat(' ',$accHolderNameFillerLen);
        
        
        $transaction_records = "001".$this->environment."50".$company_bank_info->branch_code.$company_account_number.$bankserUserCode.$usersequencenumber.$homingBranchCode.$homing_account_number.$account_type.$amount.$action_date.$entryClass.$tax_code.str_repeat(' ',3).$reference.$custAccountName.$non_standared_homing_account_number.str_repeat(' ',16)."21".str_repeat(' ',27)."\r";
        
        
        return $transaction_records;
    }
    function getAccountNumberHash($sumAccountNum,$contracount){
        $company_bank_info = CompanyBankInfo::first();
        $companyAccNum =  $company_bank_info->account_number;
        
        $hash = $sumAccountNum+(integer)$companyAccNum*$contracount;
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
    function getServiceType($servType){
        switch ($servType) {
            case 'SAMEDAY':
                $servicetype     = "CORPSSV   "; 
                break;
            case 'ONE DAY':
                $servicetype     = "SAMEDAY   "; 
                break;
            case 'TWO DAY':
                $servicetype     = "TWO DAY   "; 
                break;
            default:
                $servicetype     = "SAMEDAY   "; 
                break;
        }
        return $servicetype;
    }
    /*
    *   get next userset number.
    *   User set , is set of group of transaction sent to ABSA. 
    *   Single transmission could have multiple UserSets.
    *   A userset number is of 4 digits, max upto 9999 , then after it has to be start from one again
    */
    private function getNextUserSetNumber(){
        $lastUserSetNumber=$this->userSetNumber;
        $newUserSetNumber=intval($lastUserSetNumber)+1;
        //as the sequence number has to four digit only, that is max 9999
        $newUserSetNumber=$newUserSetNumber%10000;
        if($newUserSetNumber==0){
            $newUserSetNumber++;
        }
        return $newUserSetNumber;
    }
    /*
    * to get sequence number of the tranmission sent on the day, based on this next squence of next trasnsaction will be decided
    */
    private function getLastTransmissionSequence(){
        
        $dailysequence = TransmissionRecords::where(DB::raw("(DATE_FORMAT(created_at,'%Y-%m-%d'))"),date('Y-m-d'))
                                    ->where('combined_status','ACCEPTED')
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

    private function getNextSequenceNumber(){
        $lastSequence=$this->lastUsedSequenceNumber;
        $newSequenceNumber=intval($lastSequence)+1;
        //as the sequence number has to six digit only, that is max 999999
        $newSequenceNumber=$newSequenceNumber%1000000;
        if($newSequenceNumber==0){
            $newSequenceNumber++;
        }
        $this->lastUsedSequenceNumber=$newSequenceNumber;
        return $newSequenceNumber;
    }

    /*
    * user generation number , this means number of successful header sent till date
    *
    */
    private function getLastSuccessfulUserSetNumber(){

        //we need to consider only succefuly sent user header
        $userSetNumber = TransmissionRecords::where('user_set_status','ACCEPTED')
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

    private function userSetTransactions($transactionsTodo){
        
        /*
            offset   name                                value
            1-3     Identifier                           020 / 001 (fixed) 
            4       Status                               T / L
            5-6     Bankserver record identifier         10 - payments / 50 - collection
            7-12    User Branch                          as defined
            13-23   User Nominated Account               this is the branch/account number code of the account the will be CREDITED (ie Receive the collected money) dean's account in case of collection 
            24-27   User Code                            as defined
            28-33   User Sequence number                 initial value will be 000001 and then incremented by 1 
            34-39   Homing Branch                        this is the account the money will be deducted from the debtor in case of - colletion           
            40-50   Homing AcNumber                     
            51      Type of Account                      saving - 2, cheque-1                 
            52-62   Amount                               total of onceoff and recurring amount
            63-68   Action Date                          date at which payment is to be done
            69-70   Entry Class                          88 - payments, 
            71      Tax Code                                0 
            72-74   Filler 
            75-104  User Reference                       
            105-134 Homing Account Name                   customer's account name for collection, merchant's accont name for payments 
            135-154 Non-standard Homing Account Number    homing account number which does not fit into standered homing account number
            155-170 Filler                                   16 spaces
            171-172 Homing Institution                       21
            173-201 Filler                                   27 spaces 
        */

        $payment_date          = array();
        $bankserUserCode       = $this->bankserUserCode;
        $status=Config('constants.payportEnv');
        $company_bank_info         = CompanyBankInfo::first();
        $company_account_number    = sprintf('%011d', $company_bank_info->account_number);
        
        
        $transaction_records = '';
        $tax_code            = 0;

        //sequence of the transaction
        $usersequencenumber    = sprintf('%06d', (intval($this->firstSequenceNumber)));

        //account holder Name to whome money has to transfered
        $accountHolderName = $company_bank_info->account_holder_name;
        //There is ixed length of the name , so we might needto put fillers
        $accHolderNameFillerLen = 10-strlen($accountHolderName);

        //final name to be sent
        $userAbbrivatedName=$accountHolderName.str_repeat(' ',$accHolderNameFillerLen);

        $amountsum = 0;
        
        $date = array();
       
        foreach($transactionsTodo as  $transaction) {
            //To check from here
            $dateDetails = Collections::where('payment_date',$transaction->payment_date)->where('service_type',$transaction->service_type)->orderBy('id','desc')->first();
            
            $reffrenceFillerLen=20-strlen($transaction->reffrence);
            $refrenceStrng=strtoupper($transaction->reffrence.str_repeat(' ',$reffrenceFillerLen));

            $reference             = $userAbbrivatedName.$refrenceStrng;

            $action_date  = date('ymd',strtotime($transaction->payment_date));
            $amount       = sprintf('%011d',$transaction->amount*100);
            $account_type = isset($transaction->account_type) && $transaction->account_type== "Cheque" ? 1 : 2;
            $amountsum = $amountsum+$amount;
            
            
            $custAccountNameFillerLen  = 30-strlen($transaction->account_holder_name);
            $custAccountName=strtoupper($transaction->account_holder_name).str_repeat(' ',$custAccountNameFillerLen);

            if(strlen($transaction->account_number)>11){
                $non_standared_homing_account_number = sprintf('%020d',$transaction->account_number);
                $homing_account_number = str_repeat('0',11);
            }else{
                $non_standared_homing_account_number = str_repeat('0',20);
                $homing_account_number = sprintf('%011d', $transaction->account_number);
            }

            $entryClass=$transaction->customer->entry_class;
            if($transaction->service_type=="Same Day"){
                $entryClass=44;
            }

            $homingBranchCode=sprintf('%06d', $transaction->branch_code);
            //echo $transaction->payment_date;
            $transaction_records .= "001".$status."50".$company_bank_info->branch_code.$company_account_number.$bankserUserCode.$usersequencenumber.$homingBranchCode.$homing_account_number.$account_type.$amount.$action_date.$entryClass.$tax_code.str_repeat(' ',3).$reference.$custAccountName.$non_standared_homing_account_number.str_repeat(' ',16)."21".str_repeat(' ',27)."\r";
            
            $this->userSequenceNumber=$usersequencenumber;
            $this->updateCollectionRecord($transaction);


            //increament $usersequencenumber by one
            $usersequencenumber = sprintf('%06d', $usersequencenumber+1); //used in next occurance
            
            if($dateDetails->id==$transaction->id){
              $transaction_records .=  $this->contraRecords($status,$usersequencenumber,$amountsum,$action_date);
              $usersequencenumber = sprintf('%06d', $usersequencenumber+1);
              $amountsum = 0 ;
            } 
        }
        
        return $transaction_records;
    }

    private function updateCollectionRecord($transaction){
        $transmissionNumber=$this->getTransmissionNumber();
        $squenceNumber=$this->userSequenceNumber;
        $userSetNumber=$this->userSetNumber;
        $transaction->transmission_number=intval($transmissionNumber);
        $transaction->user_set_number=intval($userSetNumber);
        $transaction->sequence_number=intval($squenceNumber);
        $transaction->save();
    }
    private function contraRecords($total_amount,$action_date){
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
        $this->lastSequenceNumber=$this->getNextSequenceNumber();
        $usersequencenumber=$this->lastSequenceNumber;
        $company_bank_info = CompanyBankInfo::first();
        $account_number    = sprintf('%011d', $company_bank_info->account_number);
        $total_amount      = sprintf('%011d', $total_amount);
        
        //account holder Name to whome money has to transfered
        $accountHolderName = $company_bank_info->account_holder_name;
        //There is ixed length of the name , so we might needto put fillers
        $accHolderNameFillerLen = 10-strlen($accountHolderName);

        //final name to be sent
        $userAbbrivatedName=$accountHolderName.str_repeat(' ',$accHolderNameFillerLen);

        $reference             = $userAbbrivatedName.'CONTRA'.strtoupper($this->generate_string(14));  
        
        $contraRecord = "001".$this->environment."52".$company_bank_info->branch_code.$account_number.$bankserUserCode.$usersequencenumber.$company_bank_info->branch_code.$account_number."1".$total_amount.$action_date."10".str_repeat('0',4).$reference.str_repeat(' ',30).str_repeat(' ',65)."\r";
        
        return $contraRecord;
    }
    
    private function userTrailer($transactionsTodo){
        /* 
            offset   name                               value
            1-3     Identifier                          001 - collection
            4       Status                              T / L
            5-6     Bankserver record identifier        92(fixed)
            7-10    Bankserver user code                as defined
            11-16   First Sequence Number               first sequence number for the date 
            17-22   Last Sequence Number                starting with the first sequence + total no of transaction lines
            23-28   First Action Date                   
            29-34   Last Action Date
            35-40   No Debit records                
            41-46   No Credit records              
            47-52   No contra records                 
            53-64   Total Debit Value
            65-76   Total Credit Value
            77-88   Hash Total of Homing Account Numbers
            89-200   111 spaces 
        */


        $payment_date     = array();
        $bankserUserCode       = $this->bankserUserCode;
        $status=Config('constants.payportEnv');
        
        
        $firstsequencenumber    = sprintf('%06d', $this->firstSequenceNumber);  // No of transaction in a date + 1
        $lastsequence     = 0;
        $sumAccountNum    = $debittotal = 0;
        
        foreach($transactionsTodo as $key => $transaction) {

            array_push($payment_date, $transaction->payment_date);
            $lastsequence++;
            if(strlen($transaction->account_number)>11){
                $account_number = sprintf('%020d',$transaction->account_number);
            }else{
                $account_number = sprintf('%011d', $transaction->account_number);
            }
            $sumAccountNum  = $sumAccountNum + $account_number;
            $debittotal     = $debittotal+($transaction->amount*100);
        
        }
        
        $contracount = 0;
        
        
        foreach($transactionsTodo->unique('payment_date') as $key => $transaction) {
          $contracount++;
          $lastsequence++;
        }
        

        //subtract one from it, so it become exact number
        $lastsequence=intval($firstsequencenumber)+$lastsequence-1;
        //$debitcount  = sprintf('%06d', $transactions->count()+$contracount);
        $debitcount  = sprintf('%06d', $transactionsTodo->count());
        $creditcount = $contracount = sprintf('%06d',$contracount);  
        $lastsequence = sprintf('%06d', $lastsequence);
        $totalcredit = sprintf('%012d', $debittotal); //pre-pending zeros
        $totaldebit  = sprintf('%012d', $debittotal); //pre-pending zeros
        
        $first_action_date    = date("ymd",strtotime(min($payment_date)));
        $last_action_date     = date("ymd",strtotime(max($payment_date)));
    
        $this->firstSequenceNumber  = intval($lastsequence)+1;
    
       
        $company_bank_info = CompanyBankInfo::first();
        $companyAccNum =  $company_bank_info->account_number;
        $hash = $sumAccountNum+(integer)$companyAccNum*$contracount;
        $hash = str_pad($hash, 12,"0", STR_PAD_LEFT);
        
        $footer = "001".$status."92".$bankserUserCode.$firstsequencenumber.$lastsequence.$first_action_date.$last_action_date.$debitcount.$creditcount.$contracount.$totaldebit.$totalcredit.$hash.str_repeat(' ',111)."\r";
        return $footer;
    }

    private function transmissiontrailer(){
        
        /*
        offset  name                                value
        1-3     Identifier                          999 (fixed) 
        4       Status                              T / L
        5-13    Number of records in transmission   total no of lines + 2 header line + 2 trailer line 
        14-200  spaces                              186 spaces
        */

        $status=Config('constants.payportEnv');
        
        
        $contracount = 0 ;
        $userHeaderCount=0;
        if(sizeof($this->sameDaytransactions)>0){
            foreach($this->sameDaytransactions->unique('payment_date') as $key => $transaction) {
              $contracount++;
            }
            $userHeaderCount++;
        }

        if(sizeof($this->oneDaytransactions)>0){
            foreach($this->oneDaytransactions->unique('payment_date') as $key => $transaction) {
              $contracount++;
            }
            $userHeaderCount++;
        }

        if(sizeof($this->twoDaytransactions)>0){
            foreach($this->twoDaytransactions->unique('payment_date') as $key => $transaction) {
              $contracount++;
            }
            $userHeaderCount++;
        }
        
        $linecount = count($this->sameDaytransactions)+count($this->oneDaytransactions)+count($this->twoDaytransactions)+$contracount+($userHeaderCount*2)+2;
        $transmissiontrailer = "999".$status.sprintf('%09d', $linecount).str_repeat(' ',186);
        return $transmissiontrailer;
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
