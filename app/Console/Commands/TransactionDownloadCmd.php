<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Model\{Customer,Collections,TransmissionTable,PublicHolidays,TransmissionRecords,TransRepliedErrors,TransmissionRepliedErrors,TransactionErrorCodes,Ledgers,Batch,OutputFileTransaction,OutputFile};
use App\Helpers\Helper;
use Illuminate\Support\Facades\Mail;
class TransactionDownloadCmd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transaction:download-outputs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download reply and output files of collection transaction from ABSA server';
    protected $dirName='';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->dirName=public_path("files/collections/incoming/");
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function handle()
    {

        //die("Stoped");

        $remoreDir=Config('constants.remoteFileDownloadLocation');//"/transferzone/PayportSA/LDC_Outgoing";
        $localDir=public_path("files/collections/incoming");
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
                }elseif($filePreName==Config('constants.bankingSuitFolder')){
                    //download file
                    if($sftp->get($remoreDir.'/'.$eachFile, $localDir.'/'.$eachFile)){
                        $sftp->delete($remoreDir.'/'.$eachFile, false); //delete file from FTP
                    }
                }
            }
            $sftp->_disconnect("");
        }else{
            Mail::raw("Absa SFTP connection failed:- SOS", function($message){
               $message->from('noreply@payportsa.co.za');
               $message->to('dean@payportsa.co.za')->cc('operations@payportsa.co.za')->subject("ABSA SFTP connection failed- Downloading");
           });
            die("not connected");
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
                if(substr($data, 0, 8)==Config('constants.bankingSuitFolder').'R')
                {
                    $this->readReplyFile($data);
                    //update   `collections` set transaction_status=1 where transmission_status=2 and transaction_status=2

                }elseif(substr($data, 0, 8)==Config('constants.bankingSuitFolder').'O'){
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


        $file = public_path("files/collections/incoming/".$fileName);
        $numberofrows = count(file($file, FILE_SKIP_EMPTY_LINES));

        $transmissionNumber=null;
        $transmisionStatus=null;
        $generationNumber=null;
        $sequenceNumber=null;
        $userSetStatus=null;
        $errGenerationNumber=null;
        $errSequenceNumber=null;

        //tranmission whose status of still null , will be the last tranmission
        $transRecords=TransmissionRecords::whereNull('transmission_status')->orderBy('id','desc')->first();
        $lastTranmission=$transRecords;

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
                if($lastTranmission){
                    if($transmisionStatus=="REJECTED"){

                        $lastTranmission=$this->rejectFullTranmission($lastTranmission);

                        Mail::raw("Transmission failed.", function($message){
                           $message->from('operations@payportsa.co.za');
                           $message->to('musaz01@gmail.com')->cc('dean@payportsa.co.za')->cc('operations@payportsa.co.za')->subject("A transmission failed");
                       });
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

            if($identifierFiller=="ACB USER SET"){
                //it is meant for EFT/Collections user set
                $generationNumber=trim(substr($result, 26, 7));

                $userSetStatus=trim(substr($result, 41, 8));

                $sequenceNumber=trim(substr($result, 34, 6));

                //this particular user set is rejected
                if($userSetStatus=="REJECTED"){
                    //reject full user set in the transmission
                    $this->rejectFullTransmissionUserSet($generationNumber,$lastTranmission);
                }elseif ($userSetStatus=="ACCEPTED") {
                    //set the generation number ans sequence number as per last accepted user set
                    $lastTranmission->generation_number=$generationNumber;
                    $lastTranmission->sequence_number=$sequenceNumber;
                    $lastTranmission->user_set_status=$userSetStatus;
                    $lastTranmission->combined_status="ACCEPTED";
                    $lastTranmission->save();

                    //insert credit records in the ledger for accepted user set
                    $this->updateLedgerForUserSet($lastTranmission->id,$generationNumber);
                }
            }

            //reason why a indvidual transaction is rejected
            //there could be very less occurance of this
            if($recordIdentifier=="901" && $headerIdentifier=="001"){
                $this->logTransactionFailure($lastTranmission,$result);
            }
        }


        $processedCollections = Collections::where('transmission_id',$lastTranmission->id)
        ->groupBy('batch_id')->get();
        //dd($dailysequence);
        foreach ($processedCollections as $key => $eachCollection) {
            Batch::where('id', $eachCollection->batch_id)->update(['batch_status' => 'processed']);
        }


         // //Move file to different folder
        $processedFilePath='files'.DIRECTORY_SEPARATOR.'collections'.DIRECTORY_SEPARATOR.'processed'.DIRECTORY_SEPARATOR.$fileName;
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
        $file = public_path("files/collections/incoming/".$fileName);
        $numberofrows = count(file($file, FILE_SKIP_EMPTY_LINES));

        $outputFile=new OutputFile();
        $outputFile->file_type='collection';
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
                $userSeqNumber=trim(substr($result, 14, 6)); //398
                $reffrenceStrg=trim(substr($result, 53, 30)); //CRESCENT1 1603195657OCUS003396

                $rejectionCode=trim(substr($result, 83, 3)); //026
                $rejectQualification=trim(substr($result, 86, 5)); //83002

                $trxnErrorRecord=TransactionErrorCodes::where('error_code',intval($rejectionCode))->first();

                //$transmissionIds=$this->getTranmissionIdsForDate($transmissionDate);

                // $collectionRecord=Collections::where('reffrence', $reffrenceStrg)
                // ->where('sequence_number',intval($userSeqNumber))->first();
                $collectionRecord=Collections::where('reffrence', $reffrenceStrg)
                ->select(DB::raw('collections.*, transmission_records.transmission_date'))
                ->leftJoin('transmission_records', function ($join) {
                    $join->on('transmission_records.id', '=', 'collections.transmission_id');
                })
                ->where('collections.sequence_number',intval($userSeqNumber))
                ->where('transmission_records.transmission_date', $transmissionDate)
                ->first();

                if($collectionRecord){
                    $collectionRecord->tranx_error_code=$rejectionCode;
                    $collectionRecord->tranx_error_id=$trxnErrorRecord->id;
                    $collectionRecord->transaction_status=2;
                    $collectionRecord->error_qualifier=intval($rejectQualification);
                    $collectionRecord->date_of_failure=date('Y-m-d H:i:s');
                    if($trxnErrorRecord->is_dispute==1){
                        $collectionRecord->transaction_status=3;
                        Helper::logStatusChange('collection',$collectionRecord,'Transaction dispute');
                    }else{
                        Helper::logStatusChange('collection',$collectionRecord,'Transaction failed');
                    }

                    $collectionRecord->save();

                    $transactionCount++;
                    $transactionAmount+=$collectionRecord->amount;

                    $outputFileTrax=new OutputFileTransaction();
                    $outputFileTrax->output_file_id=$outputFile->id;
                    $outputFileTrax->target_transaction_id=$collectionRecord->id;
                    $outputFileTrax->tranx_amount=$collectionRecord->amount;
                    $outputFileTrax->save();

                    $lastLedgerEntry=Ledgers::whereIn('transaction_type',Config('constants.lastLedgerTranxCond'))->where('firm_id',$collectionRecord->firm_id)->orderByRaw('entry_date DESC,id desc')->orderBy('entry_date','desc')->first();
                    $closingAmount=$collectionRecord->amount*(-1);
                    if($lastLedgerEntry){

                        $closingAmount=$lastLedgerEntry->closing_amount-$collectionRecord->amount;
                    }

                    $ledgerEntry=new Ledgers();
                    $ledgerEntry->firm_id =$collectionRecord->firm_id;
                    $ledgerEntry->collection_id =$collectionRecord->id;
                    $ledgerEntry->transaction_type ='failed_collection';
                    $ledgerEntry->ledger_desc ='collection failed for '. $collectionRecord->customer->mandate_id.'('.$collectionRecord->customer->first_name.')';
                    $ledgerEntry->amount =$collectionRecord->amount;
                    $ledgerEntry->closing_amount=$closingAmount;
                    $ledgerEntry->entry_type ='dr';
                    $ledgerEntry->entry_date =$transDate;
                    $ledgerEntry->save();
                }

            }
        }


        //Move file to different folder
        $processedFilePath="files/collections/processed/".$fileName;
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

    function rejectFullTranmission($lastTranmission){
        $lastTranmission->transmission_status="REJECTED";
        $lastTranmission->user_set_status="REJECTED";
        $lastTranmission->combined_status="REJECTED";
        $lastTranmission->save();
        //update all the collections as rejected transmission and failed transaction
        //Collections::where('transmission_id', $lastTranmission->id)->update(['transmission_status' => 3,'transaction_status'=>2]);

        $collectionRecords=Collections::where('transmission_id', $lastTranmission->id)->get();
        Helper::logStatusChanges('collection',$collectionRecords,"Transmission rejected");

        Collections::where('transmission_id', $lastTranmission->id)->update(['transmission_status' => 3]);
        return $lastTranmission;
    }

    function rejectFullTransmissionUserSet($generationNumber,$lastTranmission){

        $collectionRecords=Collections::where('transmission_id', $lastTranmission->id)
        ->where(function($query) use ($generationNumber){
                $query->where('user_set_number',intval($generationNumber))
                ->orWhere('user_set_number', $generationNumber);
        })->get();
        Helper::logStatusChanges('collection',$collectionRecords,"Transmission rejected");

        //update all the collections as rejected transmission and failed transaction
        Collections::where('transmission_id', $lastTranmission->id)
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
            $errorEntry=new TransmissionRepliedErrors();
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
            $collectionRecord=Collections::where('transmission_id', $lastTranmission->id)
            ->where('user_set_number', $errGenerationNumber)
            ->where('sequence_number', $errSequenceNumber)->first();
            //make a log that why this tranmission is failed
            if($collectionRecord){
                $collectionRecord->tranx_error_code=2;
                $collectionRecord->tranx_error_id=2;
                $collectionRecord->transaction_status=2;
                $collectionRecord->save();

                Helper::logStatusChange('collection',$collectionRecord,"Transmission rejected");
            }

        }
    }



    function updateLedgerForUserSet($localTransmissionId,$generationNumber){
        $eftRecords=Collections::where('transmission_id', $localTransmissionId)
                                ->where(function($query) use ($generationNumber){
                                        $query->where('user_set_number',intval($generationNumber))
                                        ->orWhere('user_set_number', $generationNumber);
                                    })
                                ->get();
        foreach ($eftRecords as $key => $eachTransaction) {

            $eachTransaction->transmission_status=2;
            $eachTransaction->transaction_status=1;
            Helper::logStatusChange('collection',$eachTransaction,'Transmission Accepted');
            $eachTransaction->save();
            $lastLedgerEntry=Ledgers::whereIn('transaction_type',Config('constants.lastLedgerTranxCond'))->where('firm_id',$eachTransaction->firm_id)->orderBy('entry_date','desc')->first();
            $closingAmount=$eachTransaction->amount;
            if($lastLedgerEntry){
                $closingAmount=$eachTransaction->amount+$lastLedgerEntry->closing_amount;
            }
            $ledgerEntry=new Ledgers();
            $ledgerEntry->firm_id =$eachTransaction->firm_id;
            $ledgerEntry->collection_id =$eachTransaction->id;
            $ledgerEntry->transaction_type ='collection';
            $ledgerEntry->ledger_desc ='collection for '. $eachTransaction->customer->mandate_id.'('.$eachTransaction->customer->first_name.')';
            $ledgerEntry->amount =$eachTransaction->amount;
            $ledgerEntry->closing_amount=$closingAmount;
            $ledgerEntry->entry_type ='cr';
            $ledgerEntry->entry_date =date('Y-m-d');
            $ledgerEntry->save();

            $batchStatement=DB::select(DB::raw("select collections.batch_id,collections.firm_id,sum(collections.amount) as tot_amount,collections.payment_date,batches.batch_name from collections left join batches on collections.batch_id=batches.id where collections.batch_id=:batchId and collection_status=1 and collections.transmission_status =2 group by batch_id"),array('batchId'=>$eachTransaction->batch_id));
            if(sizeof($batchStatement)>0){
                $batchStatement=$batchStatement[0];
                $ledgerRecord=Ledgers::where('transaction_type','batch_collection')->where('collection_id',$eachTransaction->batch_id)->where('entry_type','cr')->first();
                //print_r($ledgerRecord);
                if(is_null($ledgerRecord)){
                    $ledgerRecord=new Ledgers();
                    //echo "test";
                }

                $ledgerRecord->firm_id =$batchStatement->firm_id;
                $ledgerRecord->collection_id =$batchStatement->batch_id;
                $ledgerRecord->transaction_type ='batch_collection';
                $ledgerRecord->ledger_desc ='Batch: '. $batchStatement->batch_name;
                $ledgerRecord->amount =$batchStatement->tot_amount;
                $ledgerRecord->entry_type ='cr';
                $ledgerRecord->closing_amount=$closingAmount;
                $ledgerRecord->entry_date =$batchStatement->payment_date;
                $ledgerRecord->save();
            }
        }
    }
    function getTranmissionIdsForDate($transmissionDate){
       $tranmissionOnDate=TransmissionRecords::where('transmission_date',$transmissionDate)->where('combined_status','ACCEPTED')->get();

        $transmissionIds=[0];
        foreach ($tranmissionOnDate as $key => $eachTransmission) {
            $transmissionIds[]=$eachTransmission->id;
        }
        return $transmissionIds;
    }

    function getTransmissionByNumber($transmissionNumber,$transmissionDate){
        $transRecords=TransmissionRecords::where(function($query) use ($transmissionNumber){
                                $query->where('transmission_number',intval($transmissionNumber))
                                ->orWhere('transmission_number', $transmissionNumber);
                        })
                        ->where('transmission_date',$transmissionDate)
                        ->whereNull('transmission_status')
                        ->orderBy('id','desc')->first();
        return $transRecords;
    }

}
