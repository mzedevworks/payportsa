<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Model\{Customer,Payments,TransmissionTable,PublicHolidays,PaymentTransmissionRecords,TransRepliedErrors,PaymentTransmissionErrors,TransactionErrorCodes,PaymentLedgers,PaymentBatches,OutputFileTransaction,OutputFile};
use App\Helpers\Helper;

class OnedayPayReplydownloadCmd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transaction:download-oneday-payment-outputs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download reply and output files of oneday payment from ABSA server';
    protected $dirName='';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->dirName=public_path(Config('constants.localOneDayPaymentFileDownloadStoragePath'));
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    
    public function handle()
    {
        
        //die("Stoped");
        
        $remoreDir=Config('constants.remoteOneDayFileDownloadLocation');
        $localDir=$this->dirName;
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
                }elseif($filePreName==Config('constants.oneDayPaymentSuitFolder')){
                    //download file
                    if($sftp->get($remoreDir.'/'.$eachFile, $localDir.'/'.$eachFile)){
                        $sftp->delete($remoreDir.'/'.$eachFile, false); //delete file from FTP
                    }
                }
            }
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
                if(substr($data, 0, 8)==Config('constants.oneDayPaymentSuitFolder').'R')
                {
                    $this->readReplyFile($data);
                    //update   `collections` set transaction_status=1 where transmission_status=2 and transaction_status=2
                    
                }elseif(substr($data, 0, 8)==Config('constants.oneDayPaymentSuitFolder').'O'){
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
        
        
        $file = public_path(Config('constants.localOneDayPaymentFileDownloadStoragePath').$fileName);
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
        $transRecords=PaymentTransmissionRecords::whereNull('transmission_status')->where('transmission_type','oneday')->orderBy('id','desc')->first();
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
                    $rawTranDate=trim(substr($result, 4, 8));//in CCYYMMDD format
                    $transDate=substr($rawTranDate, 0, 4).'-'.substr($rawTranDate, 4, 2).'-'.substr($rawTranDate, 6, 2);
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

                if($identifierFiller=="ACB USER SET"  && !is_null($lastTranmission)){
                    //it is meant for EFT/Collections user set
                    $generationNumber=trim(substr($result, 26, 7));

                    $userSetStatus=trim(substr($result, 41, 8));

                    $sequenceNumber=trim(substr($result, 34, 6));

                    //this particular user set is rejected
                    if($userSetStatus=="REJECTED"){
                        
                        //reject full user set in the transmission
                        $this->rejectFullTransmissionUserSet($generationNumber,$lastTranmission);
                        if($fullRejected==false){
                            $failedPayments=Payments::where('transmission_id', $lastTranmission->id)
                            ->where('service_type','dated')
                            ->where('payment_status',1)
                            ->where(function($query) use ($generationNumber){
                                    $query->where('user_set_number',intval($generationNumber))
                                    ->orWhere('user_set_number', $generationNumber);
                            })->get();
                            foreach($failedPayments as $eachFailedPayment){
                                $this->refundPaymentLedger($eachFailedPayment);
                            }
                        }
                    }elseif ($userSetStatus=="ACCEPTED") {
                        //set the generation number ans sequence number as per last accepted user set
                        $lastTranmission->generation_number=intval($generationNumber);
                        $lastTranmission->sequence_number=$sequenceNumber;
                        $lastTranmission->user_set_status=$userSetStatus;
                        $lastTranmission->combined_status="ACCEPTED";
                        $lastTranmission->save();

                        //insert credit records in the ledger for accepted user set
                        $this->updateAcceptedUserSet($lastTranmission->id,$generationNumber);
                    }
                }

                //reason why a indvidual transaction is rejected
                //there could be very less occurance of this
                if($recordIdentifier=="901" && $headerIdentifier=="001"){
                    $this->logTransactionFailure($lastTranmission,$result);
                }
            }

            
            $processedCollections = Payments::where('transmission_id',$lastTranmission->id)
            ->where('service_type','dated')
            ->groupBy('batch_id')->get();
            //dd($dailysequence);
            foreach ($processedCollections as $key => $eachCollection) {
                PaymentBatches::where('id', $eachCollection->batch_id)->update(['batch_status' => 'processed']);
            }
        
        }
         // //Move file to different folder
        $processedFilePath=Config('constants.localOneDayPaymentProcessedPath').$fileName;
        $moveFile=public_path($processedFilePath);
        if (copy($file,$moveFile)) 
        {
            $lastTranmission->reply_file=$processedFilePath;
            $lastTranmission->save();
            unlink($file);
        }
    }

    function readOutputFile($fileName){
        $fn         = fopen($this->dirName.$fileName,"r");
        $i          = 0;
        $file = public_path(Config('constants.localOneDayPaymentFileDownloadStoragePath').$fileName);
        $numberofrows = count(file($file, FILE_SKIP_EMPTY_LINES));

        $outputFile=new OutputFile();
        $outputFile->file_type='payment';
        $outputFile->receiving_date=date('Y-m-d H:i:s');
        $outputFile->transaction_count=0;
        $outputFile->transaction_amount=0;
        $outputFile->save();

        $transactionCount=0;
        $transactionAmount=0;


        while(!feof($fn))  
        {
            $i++;
            $result = fgets($fn);

            $recordIdentifier = trim(substr($result, 0, 3));  //to identify type of record

            if($recordIdentifier=="000"){
                //transmission header record
                $rawTranDate=trim(substr($result, 4, 8));//in CCYYMMDD format
                $transDate=substr($rawTranDate, 0, 4).'-'.substr($rawTranDate, 4, 2).'-'.substr($rawTranDate, 6, 2);
                $outputType=trim(substr($result, 44, 3));//EFT

            }

            

            if($recordIdentifier=="011"){
                //transmission header record
                $rawActionDate=trim(substr($result, 32, 8));//in CCYYMMDD format
                //This transaction was sent for this action date
                $actionDate=substr($rawActionDate, 0, 4).'-'.substr($rawActionDate, 4, 2).'-'.substr($rawActionDate, 6, 2);
                
            }

            if($recordIdentifier=="013"){
                //transmission header record
                $rawTransmissionDate=trim(substr($result, 6, 8));//in CCYYMMDD format
                $transmissionDate=substr($rawTransmissionDate, 0, 4).'-'.substr($rawTransmissionDate, 4, 2).'-'.substr($rawTransmissionDate, 6, 2);
                $userSeqNumber=trim(substr($result, 14, 6));
                $reffrenceStrg=trim(substr($result, 53, 30));

                $rejectionCode=trim(substr($result, 83, 3));
                $rejectQualification=trim(substr($result, 86, 5));

                $trxnErrorRecord=TransactionErrorCodes::where('error_code',intval($rejectionCode))->first(); 
                $transAmount=intval(trim(substr($result, 42, 11)))/100;
                //$transmissionIds=$this->getTranmissionIdsForDate($transmissionDate);
                
                // $collectionRecord=Collections::where('reffrence', $reffrenceStrg)
                // ->where('sequence_number',intval($userSeqNumber))->first();
                //$paymentRecord=Payments::where('reffrence', $reffrenceStrg)
                $paymentRecord=Payments::select('payments.*','payment_transmission_records.transmission_date')
                ->where('payments.transaction_status',1)
                ->where('payments.payment_status',1)
                ->where('payments.service_type','dated')
                ->where('payments.transmission_status',2)
                ->where('payments.amount',$transAmount)
                ->leftJoin('payment_transmission_records', function ($join) {
                    $join->on('payment_transmission_records.id', '=', 'payments.transmission_id');
                })
                ->where('payments.sequence_number',intval($userSeqNumber))
                ->where('payment_transmission_records.transmission_date', $transmissionDate)
                ->first();
                
                if($paymentRecord){
                    $paymentRecord->tranx_error_code=$rejectionCode;
                    $paymentRecord->tranx_error_id=$trxnErrorRecord->id;
                    $paymentRecord->transaction_status=2;
                    $paymentRecord->date_of_failure=date('Y-m-d H:i:s');
                    if($trxnErrorRecord->is_dispute==1){
                        $paymentRecord->transaction_status=3;
                        Helper::logStatusChange('payment',$paymentRecord,"Marked disputed");
                    }else{
                        Helper::logStatusChange('payment',$paymentRecord,"Marked failed");
                    }
                    
                    $paymentRecord->save();

                    $transactionCount++;
                    $transactionAmount+=$paymentRecord->amount;
                    
                    $outputFileTrax=new OutputFileTransaction();
                    $outputFileTrax->output_file_id=$outputFile->id;
                    $outputFileTrax->target_transaction_id=$paymentRecord->id;
                    $outputFileTrax->tranx_amount=$paymentRecord->amount;
                    $outputFileTrax->save();

                    $this->refundPaymentLedger($paymentRecord);
                }
                
            }
        }

         
        //Move file to different folder
        $processedFilePath=Config('constants.localOneDayPaymentProcessedPath').$fileName;
        $moveFile=public_path($processedFilePath);
        if (copy($file,$moveFile)) 
        {
            $outputFile->transaction_count=$transactionCount;
            $outputFile->transaction_amount=$transactionAmount;
            $outputFile->output_file_path=$processedFilePath;
            $outputFile->save();
            unlink($file);
        }
    }

    function refundPaymentLedger($payment){
        $lastLedgerEntry=PaymentLedgers::where('firm_id',$payment->firm_id)->orderBy("id",'desc')->first();
        $paymentLedger=new PaymentLedgers();
        $paymentLedger->firm_id=$payment->firm_id;
        $paymentLedger->target_reffrence_id=$payment->id;
        $paymentLedger->transaction_type='failed_payment';
        $paymentLedger->ledger_desc='Failed payment for '.$payment->account_holder_name;
        $paymentLedger->amount=$payment->amount;
        $paymentLedger->closing_amount=$lastLedgerEntry->closing_amount+$payment->amount;
        $paymentLedger->entry_type='cr';
        $paymentLedger->entry_date=date('Y-m-d');
        $paymentLedger->save();
    }
    function rejectFullTranmission($lastTranmission){
        $lastTranmission->transmission_status="REJECTED";
        $lastTranmission->user_set_status="REJECTED";
        $lastTranmission->combined_status="REJECTED";
        $lastTranmission->save();
        //update all the payments as rejected transmission and failed transaction
        Payments::where('transmission_id', $lastTranmission->id)->where('service_type','dated')->where('payment_status',1)->update(['transmission_status' => 3,'transaction_status'=>2]);
        $failedPayments=Payments::where('transmission_id', $lastTranmission->id)->where('payment_status',1)->where('service_type','dated')->get();
        foreach($failedPayments as $eachFailedPayment){
            $this->refundPaymentLedger($eachFailedPayment);
        }
        return $lastTranmission;
    }

    function rejectFullTransmissionUserSet($generationNumber,$lastTranmission){
        //update all the collections as rejected transmission and failed transaction
        Payments::where('transmission_id', $lastTranmission->id)
        ->where(function($query) use ($generationNumber){
                $query->where('user_set_number',intval($generationNumber))
                ->orWhere('user_set_number', $generationNumber);
        })
        ->where('service_type','dated')
        ->where('payment_status',1)
        ->update(['transmission_status' => 3,'transaction_status'=>2]);

    }
    function logTranmissionFailure($lastTranmission,$transmissionStrng){
        $transmissionErrorCode=trim(substr($transmissionStrng, 8, 5));
        $transmissionErrorMsg=trim(substr($transmissionStrng, 14, 50));
        if($lastTranmission){
            //make a log that why this tranmission is failed
            $errorEntry=new PaymentTransmissionErrors();
            $errorEntry->transmission_record_id =$lastTranmission->id;
            $errorEntry->error_code =$transmissionErrorCode;
            $errorEntry->error_message=$transmissionErrorMsg;
            $errorEntry->save();
        }
    }

    function logTransactionFailure($lastTranmission,$transmissionStrng){
        //it means error messages for collections/EFT
        $errGenerationNumber=trim(substr($transmissionStrng, 13, 7));
        $errSequenceNumber=trim(substr($transmissionStrng, 21, 6));
        //$errorCode=trim(substr($transmissionStrng, 28, 5));

        

        if(intval($errSequenceNumber)>0){
            $paymentRecord=Payments::where('transmission_id', $lastTranmission->id)
            ->where('user_set_number', $errGenerationNumber)
            ->where('service_type','dated')
            ->where('payment_status',1)
            ->where('sequence_number', $errSequenceNumber)->first();
            //make a log that why this tranmission is failed
            if($paymentRecord){
                $this->refundPaymentLedger($paymentRecord);
                $paymentRecord->tranx_error_code=2;
                $paymentRecord->tranx_error_id=2;
                $paymentRecord->transaction_status=2;
                $paymentRecord->save();
            }
            
        }
    }

    

    function updateAcceptedUserSet($localTransmissionId,$generationNumber){
        $eftRecords=Payments::where('transmission_id', $localTransmissionId)
                            ->where('service_type','dated')
                            ->where('payment_status',1)
                                ->where(function($query) use ($generationNumber){
                                        $query->where('user_set_number',intval($generationNumber))
                                        ->orWhere('user_set_number', $generationNumber);
                                    })
                                ->get();
        foreach ($eftRecords as $key => $eachTransaction) {

            $eachTransaction->transmission_status=2;
            $eachTransaction->transaction_status=1;
            $eachTransaction->save();

            Helper::logStatusChange('payment',$eachTransaction,"Accepted at bank");
        }
    }
    function getTranmissionIdsForDate($transmissionDate){
       $tranmissionOnDate=PaymentTransmissionRecords::where('transmission_date',$transmissionDate)->where('combined_status','ACCEPTED')->where('transmission_type','oneday')->get(); 

        $transmissionIds=[0];
        foreach ($tranmissionOnDate as $key => $eachTransmission) {
            $transmissionIds[]=$eachTransmission->id;
        } 
        return $transmissionIds;
    }

    function getTransmissionByNumber($transmissionNumber,$transmissionDate){
        $transRecords=PaymentTransmissionRecords::where(function($query) use ($transmissionNumber){
                                $query->where('transmission_number',intval($transmissionNumber))
                                ->orWhere('transmission_number', $transmissionNumber);
                        })
                        ->where('transmission_type','oneday')
                        ->where('transmission_date',$transmissionDate)
                        ->whereNull('transmission_status')
                        ->orderBy('id','desc')->first();
        return $transRecords;
    }
  
}
