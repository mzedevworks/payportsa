<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Model\{AvsTransmissionRecords,AvsTransmissionErrors,AvsEnquiry,AvsBatch,ChangeTracker,OutputFile};
use App\Helpers\Helper;

class AvsReplydownloadCmd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transaction:download-avs-outputs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download reply and output files of AVS from ABSA server';
    protected $dirName='';

    protected $localDownloadStoragePath='';
    protected $avsBankingSuitFolder='';
    protected $userSetNumber=null;
    protected $reffrenceNumber=null;
    protected $activeUserSequenceNumber=null;
    protected $transmissionResult=[];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->localDownloadStoragePath=Config('constants.localAvsDownloadStoragePath');
        $this->avsBankingSuitFolder=Config('constants.avsBankingSuitFolder');
        $this->dirName=public_path($this->localDownloadStoragePath);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    
    public function handle()
    {
        
        //die("Stoped");
        
        $remoreDir=Config('constants.remoteAvsFileDownloadLocation');
        $localDir=$this->dirName;
        try{
            $sftp=Helper::getSftp();
            if($sftp){
                $sftp->chdir($remoreDir);
                $filesInDir=$sftp->nlist();
                //loop through the files, got from remote ftp
                foreach($filesInDir as $key => $eachFile){

                    //get a block of file name, which will be used to identify type of content in that file
                    $filePreName=trim(substr($eachFile, 0, 7)); //should be ZR07303O or ZR07303R

                    if($eachFile=='.' || $eachFile=='..'){
                        continue;
                        //if block of file name is ZR07303, then only it is of our need
                    }elseif($filePreName==$this->avsBankingSuitFolder){
                        //download file
                        if($sftp->get($remoreDir.'/'.$eachFile, $localDir.'/'.$eachFile)){
                            $sftp->delete($remoreDir.'/'.$eachFile, false); //delete file from FTP
                        }
                    }
                }
            }
        }catch(\Exception $e){
            die("Unable to connect with sftp");
        }
        $this->readdata();
    }

    /*
    * Read files downloaded from ABSA server
    */
    public function readdata() 
    {
        
        
        $dir = scandir($this->dirName);
        
        foreach($dir as $key => $data){
            if($key>1){ 
                $batchsequence = false;
                if(substr($data, 0, 8)==$this->avsBankingSuitFolder.'R')
                {
                    $this->readReplyFile($data);
                    //update   `collections` set transaction_status=1 where transmission_status=2 and transaction_status=2
                    
                }elseif(substr($data, 0, 8)==$this->avsBankingSuitFolder.'O'){

                    $this->readOutputFile($data);
                    
                }
            }
            
        }    
    }

    function readReplyFile($fileName){
        $date = Date('Y-m-d-His');
        $fn         = fopen($this->dirName.$fileName,"r");
        $i          = 0;
        $flag       = false;
        $contraflag = false;
        
        
        $file = public_path($this->localDownloadStoragePath.$fileName);
        $numberofrows = count(file($file, FILE_SKIP_EMPTY_LINES));

        $transmissionNumber=null;
        $transmisionStatus=null;
        $generationNumber=null;
        $sequenceNumber=null;
        $userSetStatus=null;
        $errGenerationNumber=null;
        $errSequenceNumber=null;
        $fullRejected=false;
        //tranmission whose status of still null , will be the last tranmission
        $transRecords=AvsTransmissionRecords::whereNull('transmission_status')->orderBy('id','desc')->first();
        $lastTranmission=$transRecords;
        if(!is_null($lastTranmission)){
            while(!feof($fn))  
            {
                $i++;
                $result = fgets($fn);
                
                $numfilesaccepted =0;
                //get transaction num
                
                $identifierFiller = trim(substr($result, 7, 14)); //identifier of the record (ex:- Transmission, user set)
                $recordIdentifier = trim(substr($result, 0, 3));  //to identify type of record
                $headerIdentifier = trim(substr($result, 4, 3));  //To identify what type of message record is providing

                if($recordIdentifier=="000"){
                    //transmission header record
                   $transDate=$this->readTransmissionHeader($result);
                }
                
                if($identifierFiller=='TRANSMISSION'){
                    $transmisionStatus=substr($result,35, 8);
                    $transmissionNumber = trim(substr($result, 27, 7));
                    
                    $lastTranmission=$this->getTransmissionByNumber($transmissionNumber,$transDate);

                    if(!is_null($lastTranmission)){
                        if($transmisionStatus=="REJECTED"){
                            $fullRejected=true;
                            $lastTranmission=$this->rejectFullTranmission($lastTranmission);
                        }else{
                            $lastTranmission->transmission_status="ACCEPTED";
                            $lastTranmission->save();
                        }
                    }
                    
                }

                //reason why tranmission is rejected 
                if($recordIdentifier=="901" && $headerIdentifier=="000"){
                    //it means rejection of whole transmission
                    $this->logTranmissionFailure($lastTranmission,$result);
                }

                if($identifierFiller=="AHV USER SET"  && !is_null($lastTranmission)){
                    //it is meant for EFT/Collections user set
                    $generationNumber=trim(substr($result, 33, 7));

                    $userSetStatus=trim(substr($result, 41, 8));

                    $sequenceNumber=trim(substr($result, 34, 6));

                    //this particular user set is rejected
                    if($userSetStatus=="REJECTED"){
                        
                        //reject full user set in the transmission
                        $this->rejectFullTransmissionUserSet($generationNumber,$lastTranmission);
                        
                    }elseif ($userSetStatus=="ACCEPTED") {
                        //set the generation number ans sequence number as per last accepted user set
                        $lastTranmission->generation_number=$generationNumber;
                        //$lastTranmission->sequence_number=$sequenceNumber;
                        $lastTranmission->user_set_status=$userSetStatus;
                        $lastTranmission->combined_status="ACCEPTED";
                        $lastTranmission->save();

                        //insert credit records in the ledger for accepted user set
                        $this->updateAcceptedUserSet($lastTranmission->id,$generationNumber);
                    }
                }elseif($recordIdentifier=="901" && $headerIdentifier=="030"){
                    //reason why a indvidual transaction is rejected
                    //there could be very less occurance of this
                    $this->logTransactionFailure($lastTranmission,$result);
                }

                
                
            }

            
            $processedAvsEnquiry = AvsEnquiry::where('avs_transmission_id',$lastTranmission->id)
            ->where('creation_type','batch')
            ->groupBy('avs_batch_id')->get();
            //dd($dailysequence);
            foreach ($processedAvsEnquiry as $key => $eachAvsEnquiry) {
                AvsBatch::where('id', $eachAvsEnquiry->avs_batch_id)->update(['status' => 'processed']);
            }
        
        }
        $this->moveReplyFile($fileName,$lastTranmission);
        
    }

    function readUserSetHeader($resultStr){
        $this->reffrenceNumber=trim(substr($resultStr, 113, 30));
        $this->userSetNumber=trim(substr($resultStr, 4, 7));
    }

    function readTransmissionHeader($result){
        //transmission header record
        $rawTranDate=trim(substr($result, 4, 8));//in CCYYMMDD format
        $transDate=substr($rawTranDate, 0, 4).'-'.substr($rawTranDate, 4, 2).'-'.substr($rawTranDate, 6, 2);
        return $transDate;
    }

    function readReplyLineOne($result){
        //$this->activeUserSequenceNumber
        $this->activeUserSequenceNumber=trim(substr($result, 4, 7));

        $this->readReturnCode($result,'acc_number',105,2);
        $this->readReturnCode($result,'id_number',107,2);
        $this->readReturnCode($result,'initials',109,2);
        $this->readReturnCode($result,'last_name',111,2);
        
        $this->readReturnCode($result,'account_open',162,2);
        $this->readReturnCode($result,'accepts_debits',164,2);
        $this->readReturnCode($result,'accepts_credits',166,2);
        $this->readReturnCode($result,'last_3_month',168,2);
        $this->readReturnCode($result,'acc_type',170,2);
    }

    function readReplyLineTwo($result){
        //$this->activeUserSequenceNumber
        $this->activeUserSequenceNumber=trim(substr($result, 4, 7));

        $this->readReturnCode($result,'mob_number',123,2);
        $this->readReturnCode($result,'email_add',125,2);
    }

    function readReturnCode($string,$field,$offset,$digit){
        $returnValue=substr($string, $offset, $digit);
        $status="No";
        if($returnValue=="00"){
            $status="Yes";
        }
        
        $this->transmissionResult[$field]=$status;
    }

    function readOutputFile($fileName){
        $fn         = fopen($this->dirName.$fileName,"r");
        $i          = 0;
        $file = public_path($this->localDownloadStoragePath.$fileName);
        $numberofrows = count(file($file, FILE_SKIP_EMPTY_LINES));

        $outputFile=new OutputFile();
        $outputFile->file_type='avs';
        $outputFile->receiving_date=date('Y-m-d H:i:s');
        $outputFile->transaction_count=0;
        $outputFile->transaction_amount=0;
        $outputFile->save();

        while(!feof($fn))  
        {
            $i++;
            $result = fgets($fn);

            $recordIdentifier = trim(substr($result, 0, 3));  //to identify type of record

            if($recordIdentifier=="000"){
                $transDate=$this->readTransmissionHeader($result);
            }

            

            if($recordIdentifier=="030"){
                //extract user set number / user generation nummber
                $this->readUserSetHeader($result);
                
            }

            if($recordIdentifier=="031"){
                $this->readReplyLineOne($result);
            }

            if($recordIdentifier=="032"){
                $this->readReplyLineTwo($result);
                $this->saveAvsOutput();
            }
        }

         
        
        $this->moveFile($fileName,$outputFile);
    }

    private function saveAvsOutput(){
        $userSetNumber=$this->userSetNumber;
        $sequenceNumber=$this->activeUserSequenceNumber;
        $transmissionResult=json_encode($this->transmissionResult);

        $avsEnquiries=AvsEnquiry::select('id')->where(['avs_reffrence'=>$this->reffrenceNumber,'avs_status'=>'pending'])->whereNull('avs_json_result')->where(function($query) use ($userSetNumber){
                $query->where('user_set_number',intval($userSetNumber))
                ->orWhere('user_set_number', $userSetNumber);
            })->where(function($query) use ($sequenceNumber){
                $query->where('sequence_number',intval($sequenceNumber))
                ->orWhere('sequence_number', $sequenceNumber);
            })->get(); 
        Helper::logStatusChanges('avs',$avsEnquiries,"Avs enquiry got successful");


        //AvsEnquiry::where(['transaction_status'=>1,'transmission_status'=>2,'avs_status'=>'pending'])->
        AvsEnquiry::where(['avs_reffrence'=>$this->reffrenceNumber,'avs_status'=>'pending'])->whereNull('avs_json_result')->where(function($query) use ($userSetNumber){
                $query->where('user_set_number',intval($userSetNumber))
                ->orWhere('user_set_number', $userSetNumber);
            })->where(function($query) use ($sequenceNumber){
                $query->where('sequence_number',intval($sequenceNumber))
                ->orWhere('sequence_number', $sequenceNumber);
            })->update(['avs_json_result'=>$transmissionResult,'avs_status'=>'sucessful']);
            $this->transmissionResult=[];
    }
    private function moveFile($fileName,$outputFile=null){
        //Move file to different folder
        $processedFilePath=Config('constants.localAvsProcessedPath').$fileName;
        $file = public_path($this->localDownloadStoragePath.$fileName);
        //Move file to different folder
        $moveFile=public_path($processedFilePath);
        if (copy($file,$moveFile)) 
        {
            if(!is_null($outputFile)){
                $outputFile->output_file_path=$processedFilePath;
                $outputFile->save();
            }
            
            unlink($file);
        }
    }

    private function moveReplyFile($fileName,$lastTranmission){
        //Move file to different folder
        $processedFilePath=Config('constants.localAvsProcessedPath').$fileName;
        $file = public_path($this->localDownloadStoragePath.$fileName);
        //Move file to different folder
        $moveFile=public_path($processedFilePath);
        if (copy($file,$moveFile)) 
        {
            $lastTranmission->reply_file=$processedFilePath;
            $lastTranmission->save();
            
            unlink($file);
        }
    }


    
    function rejectFullTranmission($lastTranmission){
        $lastTranmission->transmission_status="REJECTED";
        $lastTranmission->user_set_status="REJECTED";
        $lastTranmission->combined_status="REJECTED";
        $lastTranmission->save();

        $avsEnquiries=AvsEnquiry::select('id')->where('avs_transmission_id', $lastTranmission->id)->get(); 
        Helper::logStatusChanges('avs',$avsEnquiries,"Transmission rejected");
        

        //update all the payments as rejected transmission and failed transaction
        AvsEnquiry::where('avs_transmission_id', $lastTranmission->id)->update(['transmission_status' => 3,'transaction_status'=>2]);
        
        return $lastTranmission;
    }

    function rejectFullTransmissionUserSet($generationNumber,$lastTranmission){
        //update all the avs_enquiry as rejected transmission and failed transaction

        $avsEnquiries=AvsEnquiry::select('id')->where('avs_transmission_id', $lastTranmission->id)
        ->where(function($query) use ($generationNumber){
                $query->where('user_set_number',intval($generationNumber))
                ->orWhere('user_set_number', $generationNumber);
        })->get(); 
        Helper::logStatusChanges('avs',$avsEnquiries,"Transmission user set rejected");


        AvsEnquiry::where('avs_transmission_id', $lastTranmission->id)
        ->where(function($query) use ($generationNumber){
                $query->where('user_set_number',intval($generationNumber))
                ->orWhere('user_set_number', $generationNumber);
        })
        ->update(['transmission_status' => 3,'transaction_status'=>2]);

    }
    function logTranmissionFailure($lastTranmission,$transmissionStrng){
        $transmissionErrorCode=trim(substr($transmissionStrng, 8, 5));
        $transmissionErrorMsg=trim(substr($transmissionStrng, 14, 50));
        if($lastTranmission){
            //make a log that why this tranmission is failed
            $errorEntry=new AvsTransmissionErrors();
            $errorEntry->transmission_record_id =$lastTranmission->id;
            $errorEntry->error_code =$transmissionErrorCode;
            $errorEntry->error_message=$transmissionErrorMsg;
            $errorEntry->save();
        }
    }

    function logTransactionFailure($lastTranmission,$transmissionStrng){
        //it means error messages for collections/EFT
        $errGenerationNumber=trim(substr($transmissionStrng, 15, 7));
        $errSequenceNumber=trim(substr($transmissionStrng, 40, 8));
        $errorCode=trim(substr($transmissionStrng, 49, 5));
        $transmissionErrorMsg=trim(substr($transmissionStrng, 55, 50));

        if(intval($errSequenceNumber)>0){
            $avsRecord=AvsEnquiry::where('avs_transmission_id', $lastTranmission->id)
            ->where('user_set_number', $errGenerationNumber)
            ->where('sequence_number', $errSequenceNumber)->first();
            //make a log that why this tranmission is failed
            if($avsRecord){
                //make a log that why this tranmission is failed
                $errorEntry=new AvsTransmissionErrors();
                $errorEntry->transmission_record_id =$lastTranmission->id;
                $errorEntry->avs_enquiry_id =$avsRecord->id;
                $errorEntry->error_code =$errorCode;
                $errorEntry->error_message=$transmissionErrorMsg;
                $errorEntry->save();
            }
            
        }
    }

    

    function updateAcceptedUserSet($localTransmissionId,$generationNumber){

        $avsEnquiries=AvsEnquiry::select('id')->where('avs_transmission_id', $localTransmissionId)
                                ->where(function($query) use ($generationNumber){
                                        $query->where('user_set_number',intval($generationNumber))
                                        ->orWhere('user_set_number', $generationNumber);
                                    })->get(); 
        Helper::logStatusChanges('avs',$avsEnquiries,"Transmission user set accepted");


        $eftRecords=AvsEnquiry::where('avs_transmission_id', $localTransmissionId)
                                ->where(function($query) use ($generationNumber){
                                        $query->where('user_set_number',intval($generationNumber))
                                        ->orWhere('user_set_number', $generationNumber);
                                    })->update(['transmission_status' => 2,'transaction_status'=>1]);
        
    }
    function getTranmissionIdsForDate($transmissionDate){
       $tranmissionOnDate=PaymentTransmissionRecords::where('transmission_date',$transmissionDate)->where('combined_status','ACCEPTED')->where('transmission_type','sameday')->get(); 

        $transmissionIds=[0];
        foreach ($tranmissionOnDate as $key => $eachTransmission) {
            $transmissionIds[]=$eachTransmission->id;
        } 
        return $transmissionIds;
    }

    function getTransmissionByNumber($transmissionNumber,$transmissionDate){
        $transRecords=AvsTransmissionRecords::where(function($query) use ($transmissionNumber){
                                $query->where('transmission_number',intval($transmissionNumber))
                                ->orWhere('transmission_number', $transmissionNumber);
                        })
                        ->where('transmission_date',$transmissionDate)
                        ->whereNull('transmission_status')
                        ->orderBy('id','desc')->first();
        return $transRecords;
    }
  
}
