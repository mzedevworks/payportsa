<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Model\{AvsTransmissionRecords,CompanyBankInfo,AvsEnquiry,PublicHolidays,AvsBatch};
use Illuminate\Support\Facades\Hash;
use App\Helpers\Helper;
use phpseclib\Net\SFTP;
use Illuminate\Support\Facades\Mail;
class CreateAvsCmd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transaction:create-avs-transmission';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create and upload transmission files for AVS';
    
    // bank variables
    protected $localDir='/'; //location where file will be generated
    protected $remoteDir='/';
    protected $environment='T'; //environment of the API
    protected $bankserUserCode = ''; //Usercode of the API user
    protected $electronicBankingSuitUserCode = '';
    protected $electronicBankingSuitUserName = '';
    protected $transmissionSeqNumber=0;
    // transmission data variables
    protected $avsTransactions=[];
    protected $outputContent='';

    protected $transmissionFileNumber=0;
    protected $sentUserSetNumber=0;
    protected $currentUserSetNumber=0;


    protected $transactionCountOfToday=0;
    protected $firstTransactionSequence=0;
    protected $linesInTransmissionFile=0;
    
    protected $avsEnquiryCount=0;
    protected $sumAccountNum=0;

    
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->localDir=Config('constants.localAvsFileStoragePath');
        $this->remoteDir=Config('constants.remoteAvsOutputPath');
        $this->environment=Config('constants.payportEnv');
        $this->bankserUserCode = Config('constants.avsbankSerUserCode');
        $this->electronicBankingSuitUserCode = Config('constants.avsBankingSuitUserCode');
        $this->electronicBankingSuitUserName = Config('constants.avsSuitUserName');

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
        
        if($this->isTransmissionResultAwaited()){

            die("Status of Last transmission is pending, You can't do next transmission");
            exit();
        }

         $sftp="";
        $sftp=Helper::getSftp(); //
         if($sftp){

            $this->fetchAvsRecords();
            // Add transmission header in the file
            
            $this->generateTransmissionHeader();
            $this->generateUserSetHeader();
            $this->generateAvsTransmission();
            $this->generateUserSetFooter();
            $this->generateTransmissionFooter();
            
            //echo $this->outputContent;

            $this->uploadTransmissionFile($sftp);
        }else{
            Mail::raw("Absa SFTP connection failed for Avs Batch:- SOS", function($message){
               $message->from('noreply@payportsa.co.za');
               $message->to('dean@payportsa.co.za')->cc('operations@payportsa.co.za')->subject("ABSA SFTP connection failed for Avs Batch");
           });
            die("not connected");
        }
    }

    function uploadTransmissionFile($sftp){
        try{
            // $sftp=Helper::getSftp();
            // if($sftp){

                /*Writing transmission file with output*/
                $file_name = Config('constants.avsBankingSuitFolder').".".Date("Ymdhis").".txt"; //name of the generated file
                $newfile   = public_path($this->localDir.$file_name);
                $file    = fopen($newfile, "w"); 
                fwrite($file, $this->outputContent); 
                fclose ($file);
                /*End of Writing transmission file with output*/

                $remote_file = $this->remoteDir.$file_name;

                //upload file on ftp of absa
                
                
                
                $fileSent=false;
                $fileSent=$sftp->put($remote_file, $newfile, SFTP::SOURCE_LOCAL_FILE);
                while($fileSent==false){
                    $fileSent=$sftp->put($remote_file, $newfile, SFTP::SOURCE_LOCAL_FILE);
                }
            
                //create an object to insert new Transmission record
                $newTransmissionRecord=new AvsTransmissionRecords();
                $newTransmissionRecord->transmission_number=$this->transmissionFileNumber;
                $newTransmissionRecord->file_path=$this->localDir.$file_name;
                $newTransmissionRecord->transmission_date=date('Y-m-d');

                if($newTransmissionRecord->save()){
                    $newTransId = $newTransmissionRecord->id;
                    $this->updateTransactionStatus($this->avsTransactions,$newTransId);
                }else{
                    $this->revertTransactionStatus($this->avsTransactions);
                }

                $sftp->_disconnect("");
                die("File created and uploaded successfuly");
            // }else{
            //     $this->revertTransactionStatus($this->avsTransactions);
            //     die("Unable to connect with sftp");
            // }
        }catch(\Exception $e){
            $this->revertTransactionStatus($this->avsTransactions);
            die("Unable to connect with sftp");
        }

    }

    function updateTransactionStatus($transactions,$transmissionId){
        foreach($transactions as  $eachTransaction) {
            $eachTransaction->avs_transmission_id  =$transmissionId;
            $eachTransaction->transmission_status  =1;
            $eachTransaction->save();


            Helper::logStatusChange('avs',$eachTransaction,"Avs enquiry sent");
            $avsBatch=AvsBatch::find($eachTransaction->avs_batch_id);
            if(!is_null($avsBatch)){
                $avsBatch->status='sent';
                $avsBatch->save();
            }
            
        }
    }

    private function revertTransactionStatus($transactions){
        foreach($transactions as  $eachTransaction) {
            $eachTransaction->avs_transmission_number=NULL;
            $eachTransaction->user_set_number=NULL;
            $eachTransaction->sequence_number=NULL;
            $eachTransaction->save();
            $avsBatch=AvsBatch::find($eachTransaction->avs_batch_id);
            if(!is_null($avsBatch)){
                $avsBatch->status='pending';
                $avsBatch->save();
            }
        }
        
    }

    private function generateUserSetHeader(){
        
        /*
        offset  name                               value
        1-3     Identifier                         030 (fixed) 
        4       Status                             T / L
        5-11    user Set Generation number         ABSAIN
        12-17   Department code                    Same As bankServ Code
        18-199  spaces
        */
        
        $this->currentUserSetNumber=$this->getNextUserSetNumber();
            
        $usersetGenerationNumber            = sprintf('%07d', $this->currentUserSetNumber);  
        
        $transmissionheader = "030".$this->environment.$usersetGenerationNumber.$this->bankserUserCode.str_repeat(' ',182)."\r"; 
        $this->linesInTransmissionFile++;
        $this->outputContent.=$transmissionheader;
        return $transmissionheader;
    }

    private function generateAvsTransmission(){
        $avsTranmissions=$this->avsTransactions;
        foreach ($avsTranmissions as $key => $eachAvsTransmission) {
            $this->avsEnquiryCount++;
            $this->getNextTransactionSequence();
            $userReffrence=$this->generate_string(30);
            $this->firstAvsLine($eachAvsTransmission,$userReffrence);
            $this->secondAvsLine($eachAvsTransmission);
            $eachAvsTransmission->avs_reffrence=$userReffrence;
            $transaction=$this->updateEnqyiryRecord($eachAvsTransmission);
            $this->avsTransactions[$key]=$transaction;
        }
    }
    private function updateEnqyiryRecord($transaction){
        $transmissionNumber=$this->transmissionFileNumber;
        $squenceNumber=$this->transactionCountOfToday;
        $userSetNumber=$this->currentUserSetNumber;
        $transaction->avs_transmission_number=intval($transmissionNumber);
        $transaction->user_set_number=intval($userSetNumber);
        $transaction->sequence_number=intval($squenceNumber);
        $transaction->save();
        return $transaction;
    }
    private function firstAvsLine($eachAvsTransmission,$userReffrence){
        /*
        offset  name                               value
        1-3     Identifier                         031 (fixed) 
        4       Status                             T / L
        5-11    Tranx sequence Number              7 digit number
        12-29   Account Number                    
        30-42   Identity Number                    space if not filled
        43-45   Initials of name                   space if not filled
        46-105  Surname                            space if not filled
        106-113 Zero
        114-143 user refference                    Any random string, will be return in reply file
        144-149 Branch Code of account
        150-155 originating bank                   000060 (fixed)
        156-162 LD code                             
        163-175 Zero
        176-200 spaces
        */
        $tranxSequenceNumber     = sprintf('%07d', $this->transactionCountOfToday);
        
        $this->sumAccountNum+=intval($eachAvsTransmission->bank_account_number);
        $accountNumber=Helper::spaceFiller($eachAvsTransmission->bank_account_number,18,0,"left");
        $identityNumber=Helper::spaceFiller($eachAvsTransmission->beneficiary_id_number,13," ","right");
        $initialName=strtoupper(Helper::spaceFiller($eachAvsTransmission->beneficiary_initial,3));
        $accountHolderName=strtoupper(Helper::spaceFiller($eachAvsTransmission->beneficiary_last_name,60));
        $accountBranchCode=strtoupper(Helper::spaceFiller($eachAvsTransmission->branch_code,6));
        
        $ldCode="LD".$this->electronicBankingSuitUserCode;
        
        
        $accountType=Helper::getAccountCodeAsAbsa($eachAvsTransmission->bank_account_type);
        

        $transmissionLine = "031".$this->environment.$tranxSequenceNumber.$accountNumber.$identityNumber.$initialName.$accountHolderName."00000000".$userReffrence.$accountBranchCode."000060".$ldCode.str_repeat('0',13)."YY".str_repeat(' ',22)."\r"; 
        $this->linesInTransmissionFile++;
        $this->outputContent.=$transmissionLine;
    }

    private function secondAvsLine($eachAvsTransmission){
        /*
        offset  name                               value
        1-3     Identifier                         032 (fixed) 
        4       Status                             T / L
        5-11    Tranx sequence Number              7 digit number
        12-23   Mobile Number                    
        24-123  Email address                    space if not filled
        124-127 Zero
        128-199 spaces
        */

        $mobNumber=Helper::spaceFiller("+27",12,"0","right");
        $emailAddress=Helper::spaceFiller("NONE",100," ","right");
        $tranxSequenceNumber     = sprintf('%07d', $this->transactionCountOfToday);

        $transmissionLine = "032".$this->environment.$tranxSequenceNumber.$mobNumber.$emailAddress.str_repeat('0',4).str_repeat(' ',72)."\r"; 
        $this->linesInTransmissionFile++;
        $this->outputContent.=$transmissionLine;
    }

    private function generateUserSetFooter(){
        
        /*
        offset  name                               value
        1-3     Identifier                         039 (fixed) 
        4       Status                             T / L
        5-11    Number of AVS records              7 digit number
        12-29   Hash total of account numbers      Same As bankServ Code
        30-199  spaces
        */
        $avsEnquiryCount            = sprintf('%07d', $this->avsEnquiryCount);  
        $accountNumberHash=$this->getAccountNumberHash();
        $transmissionheader = "039".$this->environment.$avsEnquiryCount.$accountNumberHash.str_repeat(' ',170)."\r"; 
        $this->linesInTransmissionFile++;
        $this->outputContent.=$transmissionheader;
        return $transmissionheader;
    }
    
    function getAccountNumberHash(){
                
        $hash = $this->sumAccountNum;
        $hash = str_pad($hash, 18,"0", STR_PAD_LEFT);
        return $hash;
    }

    private function generateTransmissionFooter(){
        
        /*
        offset  name                                value
        1-3     Identifier                          999 (fixed) 
        4       Status                              T / L
        5-13    Number of records in transmission   total no of lines + 2 header line + 2 trailer line 
        14-200  spaces                              186 spaces
        */
        $this->linesInTransmissionFile++;
        $transmissiontrailer = "999".$this->environment.sprintf('%09d', $this->linesInTransmissionFile).str_repeat(' ',186)."\r";
        $this->outputContent.=$transmissiontrailer;
        return $transmissiontrailer;
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

    private function getNextTransactionSequence(){
        $lastSequence=$this->transactionCountOfToday;
        $newSequenceNumber=intval($lastSequence)+1;
        //as the sequence number has to seven digit only, that is max 9999999
        $newSequenceNumber=$newSequenceNumber%10000000;
        if($newSequenceNumber==0){
            $newSequenceNumber++;
        }
        $this->transactionCountOfToday=$newSequenceNumber;
        return $newSequenceNumber;
    }


    
    /*
    * user generation number , this means number of successful header sent till date
    *
    */
    private function getLastSuccessfulUserSetNumber(){

        //we need to consider only succefuly sent user header
        $userSetNumber = AvsTransmissionRecords::where('user_set_status','ACCEPTED')
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
    *   A userset number is of 7 digits, max upto 9999999 , then after it has to be start from one again
    */
    private function getNextUserSetNumber(){
        $newUserSetNumber=intval($this->sentUserSetNumber)+1;
        //as the sequence number has to seven digit only, that is max 9999999
        $newUserSetNumber=$newUserSetNumber%10000000;
        if($newUserSetNumber==0){
            $newUserSetNumber=1;
        }
        $this->sentUserSetNumber=$newUserSetNumber;
        return $this->sentUserSetNumber;
    }

    /*
    * Get number of Successful transmission sent on today to ABSA
    */
    private function getTransmissionCountOfToday(){
        
        $dailysequence = AvsTransmissionRecords::where(DB::raw("(DATE_FORMAT(created_at,'%Y-%m-%d'))"),date('Y-m-d'))
                                    ->where('combined_status','ACCEPTED')
                                    ->groupBy(DB::raw("(DATE_FORMAT(created_at,'%Y-%m-%d'))"))
                                    ->sum('sequence_number');
        $dailysequence=intval($dailysequence);
        if(isset($dailysequence)){
            $count   = $dailysequence;  
        }else{
            $count = 0;
        }
        //starts with 1 for every file
        $count = 0;
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
        $this->linesInTransmissionFile++;
        $this->outputContent.=$transmissionheader;
        return $transmissionheader;
    }

    /*
    * get transmission number which need to be send to ABSA.
    */
    private function getTransmissionNumber(){

        //identify the last transmission number which was sent to ABSA and its status was marked as ACCEPTED
        $transmission = AvsTransmissionRecords::where('combined_status','ACCEPTED')->orderBy('id','desc')->first();


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
    private function fetchAvsRecords(){
        $createdDateTime=date('Y-m-d H:i:s', strtotime('+20 minutes'));
        
        //get those transmission whose tranmission status is pending (0)
        $this->avsTransactions =  AvsEnquiry::whereNull('sequence_number')
                                    ->where('creation_type','batch')
                                    ->where('created_on','<=',$createdDateTime)
                                    ->orderBy('firm_id')
                                    ->orderBy('created_on')
                                    ->take(9999999)->skip(0)
                                    ->get();
        $totalRecords=sizeof($this->avsTransactions);
        if($totalRecords<=0){
            die("No record to make transmission");
            exit();
        }
    }
    
    /*
        Check for transmission , if there is any pending results
        if there is any pending result, you can not create EFT further
    */
    private function isTransmissionResultAwaited(){
        $lastTranmission=AvsTransmissionRecords::orderBy('id','desc')->first(); 

        //check if reply of last transimission is received or not
        if($lastTranmission && is_null($lastTranmission->transmission_status)){
            return true;
        }else{
            return false;
        }
    }

    private function generate_string($strength) {
        $input = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $input_length = strlen($input);
        $timeStamp=strtotime(date('Y-m-d H:i:s'));
        $random_string = $timeStamp;
        for($i = 0; $i < ($strength-strlen($timeStamp)); $i++) {
            $random_character = $input[mt_rand(0, $input_length - 1)];
            $random_string .= $random_character;
        }
        return $random_string;
    }
}
