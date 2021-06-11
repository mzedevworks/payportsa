<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Model\{Customer,Payments,PaymentLedgers,Firm,UntrackedTopup};
use App\Helpers\Helper;

class NmbDownloadCmd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transaction:download-nmb-outputs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download reply and output files of NMB (Notify Me for business) from ABSA server';
    protected $dirName='';
    protected $remoteDirName='';
    protected $processedDirName="";
    protected $filePreFix="";
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->dirName=public_path(Config('constants.localNotifyDownloadDirPath'));
        $this->remoteDirName=Config('constants.remoteNotifyDirPath');
        $this->processedDirName=public_path(Config('constants.localNotifyProcessedDirPath'));
        $this->filePreFix=Config('constants.NMBFilePrefix');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    
    public function handle()
    {
        
        
        //die("Stoped");

        $localDir=$this->dirName;
        $sftp=Helper::getSftp();
        if($sftp){
        $sftp->chdir($this->remoteDirName);
        $filesInDir=$sftp->nlist();
        //loop through the files, got from remote ftp
        foreach($filesInDir as $key => $eachFile){

        //get a block of file name, which will be used to identify type of content in that file
        $filePreName=trim(substr($eachFile, 0, 13)); //should be ZR07303O or ZR07303R

                    if($eachFile=='.' || $eachFile=='..'){
                        continue;
                        //if block of file name is ZR07303, then only it is of our need
                    }elseif($filePreName==$this->filePreFix){
                        //download file
                        if($sftp->get($this->remoteDirName.'/'.$eachFile, $localDir.'/'.$eachFile)){
                            $sftp->delete($this->remoteDirName.'/'.$eachFile, false); //delete file from FTP
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
                if(substr($data, 0, 13)==$this->filePreFix)
                {
                    $this->readReplyFile($data);
                }
            }
            
        }    
    }

    function readReplyFile($fileName){
        $date = Date('Y-m-d-His');
        $tranxData=$this->readXmlFile($this->dirName.$fileName);
        
        if(array_key_exists('TRANSACTION', $tranxData['DETAILS']) && is_array($tranxData['DETAILS']['TRANSACTION'])){
            //print_r($tranxData['DETAILS']);
            //print_r($tranxData['DETAILS']['TRANSACTION']);
            if(array_key_exists("CLREF", $tranxData['DETAILS']['TRANSACTION'])){
                $this->processTranx($tranxData['DETAILS']['TRANSACTION']);
            }else{
                foreach ($tranxData['DETAILS']['TRANSACTION'] as $eachTranx) {
                    $this->processTranx($eachTranx);
                }
            }
            
        }
        $file = $this->dirName.$fileName;
        
          //Move file to different folder
        $moveFile=$this->processedDirName.'/'.$fileName;
        if (copy($file,$moveFile)) 
        {
            unlink($file);
        }
    }

    private function processTranx($eachTranx){
        if(array_key_exists("CLREF", $eachTranx)){
            $firmDetails=null;
            
            $paymentReffArr=explode('PP', trim($eachTranx["CLREF"]));
            foreach ($paymentReffArr as $key => $eachPaymentRef) {
                $paymentRef="PP".trim(substr($eachPaymentRef, 0, 8));
                $firmDetails=Firm::where('payment_reff_number',$paymentRef)->first();
                if(!is_null($firmDetails)){
                    continue;
                }
            }
            
            if(!is_null($firmDetails)){
                $this->accountTopUp($firmDetails->id,$eachTranx);
            }else{
                $this->untrackedTop($eachTranx);
            }
        }
    }
    function untrackedTop($eachTranx){
        $untracked=new UntrackedTopup();
        $untracked->amount=trim($eachTranx['AMT']);
        $untracked->reffrence_number=trim($eachTranx["CLREF"]);
        $untracked->event_number=trim($eachTranx["EVENT-NO"]);
        $untracked->created_at=date('Y-m-d H:i:s');
        $untracked->save();
    }

    function readXmlFile($filePath){
        $xmlString=file_get_contents($filePath);
        $xmlObject = simplexml_load_string($xmlString);

        $json = json_encode($xmlObject);

        $tranxData = json_decode($json,TRUE);
        return $tranxData;
    }
    function accountTopUp($firmId,$eachTranx){
        try {
            DB::beginTransaction();
            $lastPaymentLedger=PaymentLedgers::where('firm_id',$firmId)->orderBy("id",'desc')->lockForUpdate()->first();

            $paymentLedger=new PaymentLedgers();

            $paymentLedger->firm_id=$firmId;
            $paymentLedger->transaction_type='refill';

            $paymentLedger->amount=trim($eachTranx['AMT']);
            $paymentLedger->ledger_desc='Account Topup';
            $paymentLedger->entry_type='cr';
            $paymentLedger->entry_date=date('Y-m-d');

            

            $paymentLedger->closing_amount=$lastPaymentLedger->closing_amount+$eachTranx['AMT'];
            $paymentLedger->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
        }
    }
}
