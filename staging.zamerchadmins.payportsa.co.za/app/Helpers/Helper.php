<?php

namespace App\Helpers;
use PDO;
use App\Model\{Firm,BankDetails,PublicHolidays,ProfileTransactions,ChangeTracker};
use Illuminate\Support\Facades\Mail;
use phpseclib\Net\SFTP;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session; //Here

class Helper
{
    public static function writeProfileLimitTrax($adminFirmId,$merchantFirmId,$amount,$remark,$tranxType){

            $payportFirm = Firm::find($adminFirmId);

            $merchantFirm = Firm::find($merchantFirmId);

            $firmTrxRec=new ProfileTransactions(); //merchant firm 
            $payportTrxRec=new ProfileTransactions(); //payport firm


            $lastFirmEntry=ProfileTransactions::where('firm_id',$merchantFirmId)->orderBy('transmission_date','desc')->first();

            $payPortInfo=ProfileTransactions::where('firm_id',$adminFirmId)->orderBy('transmission_date','desc')->first();

            
            $firmTrxRec->firm_id=$merchantFirmId;
            $firmTrxRec->trans_against_firm_id=$adminFirmId;
            $firmTrxRec->transmission_type=$tranxType;
            $firmTrxRec->remark=$remark;
            $firmTrxRec->product_type='collection_topup';
            $firmTrxRec->transmission_date=date('Y-m-d H:i:s');

            $payportTrxRec->firm_id=$adminFirmId;
            $payportTrxRec->trans_against_firm_id=$merchantFirmId;
            $payportTrxRec->transmission_type='cr';
            $payportTrxRec->remark=$remark;
            $payportTrxRec->product_type='topup_transfer';
            $payportTrxRec->transmission_date=date('Y-m-d H:i:s');

            $toFirm=-1;
            $toPayport=1;

            if($firmTrxRec->transmission_type=='cr'){
                $payportTrxRec->transmission_type='dr';
                $toFirm=1;
                $toPayport=-1;
            }

            $payportTrxRec->closing_balance=$payPortInfo->closing_balance+($amount*$toPayport);
            $payportFirm->monthly_limit=$payportTrxRec->closing_balance;


            $payportTrxRec->amount=$amount*$toPayport;

            if($lastFirmEntry){
                $firmTrxRec->closing_balance=$lastFirmEntry->closing_balance+($amount*$toFirm);
                $firmTrxRec->amount=$amount*$toFirm;
                
            }else{
                $firmTrxRec->closing_balance=$amount*$toFirm;
                $firmTrxRec->amount=$amount*$toFirm;
            }
            
            $merchantFirm->monthly_limit=$firmTrxRec->closing_balance;

            $payportTrxRec->save();
            $payportFirm->save();

            $firmTrxRec->save();
            $merchantFirm->save();
    }

    public static function sendMail($data) {
        
        //return view('emails.'.$data['template'],compact('data'));
        
        try {
            Mail::send('emails.'.$data['template'], ['data' => $data], function ($m) use ($data) {
                $m->from($data['from_email'],$data['from_name']);
                $m->to($data['to_email'])->subject($data['subject']);
            });
            return true;
        } catch(Exception $e) {
            return false;
        }
        
    }


    public static function getDTCompatibleFilterValueForFirmStatus($searchString){
        $userStatus  = config('constants.userStatus');
        $returnStr=$searchString;
        $searchString=strtolower($searchString);
        foreach ($userStatus as $eachUserStatus) {
            if(strpos(strtolower($eachUserStatus['title']), $searchString)!== false){
                
                $returnStr=$eachUserStatus['value'];
                break;
            }
        }
        return $returnStr;
    }

    public static function getDTCompatibleFilterValueForCollectionStatus($searchString){
        $collectionStatus  = config('constants.collectionStatus');
        $returnStr=$searchString;
        $searchString=strtolower($searchString);
        foreach ($collectionStatus as $eachCollectionStatus) {
            if(strpos(strtolower($eachCollectionStatus['title']), $searchString)!== false){
                $returnStr=$eachCollectionStatus['value'];
                break;
            }
        }
        return $returnStr;
    }

    public static function getDTCompatibleFilterValueForCollectionTransactionStatus($searchString){
        $collectionStatus  = config('constants.transactionStatus');
        $returnStr=$searchString;
        $searchString=strtolower($searchString);
        foreach ($collectionStatus as $eachCollectionStatus) {
            if(strpos(strtolower($eachCollectionStatus['title']), $searchString)!== false){
                $returnStr=$eachCollectionStatus['value'];
                break;
            }
        }
        return $returnStr;
    }

    public static function getDTCompatibleFilterValueForCollectionTransmissionStatus($searchString){
        $collectionStatus  = config('constants.transmissionStatus');
        $returnStr=$searchString;
        $searchString=strtolower($searchString);
        foreach ($collectionStatus as $eachCollectionStatus) {
            if(strpos(strtolower($eachCollectionStatus['title']), $searchString)!== false){
                $returnStr=$eachCollectionStatus['value'];
                break;
            }
        }
        return $returnStr;
    }

    public static function getAccountCodeAsAbsa($accountType='saving'){
        $accountTypeCodes  = config('constants.accountTypeCodes');
        if(array_key_exists(strtolower($accountType), $accountTypeCodes)){
            return $accountTypeCodes[strtolower($accountType)];
        }else{
            return 0;
        }
        
    }
    public static function getDTCompatibleFilterValueForUntrackedFundStatus($searchString){
        $fundStatus  = config('constants.untrackedFundStatus');
        $returnStr=$searchString;
        $searchString=strtolower($searchString);
        foreach ($fundStatus as $eachFundStatus) {
            if(strpos(strtolower($eachFundStatus['title']), $searchString)!== false){
                
                $returnStr=$eachFundStatus['value'];
                break;
            }
        }
        return $returnStr;
    }

    public static function sendInviteMail($data) {
        
        try {
            Mail::send('emails.'.$data['template'], ['data' => $data], function ($m) use ($data) {
                $m->from(env('MAIL_FROM_EMAIL'),env('MAIL_FROM_NAME'));
                $m->to($data['to']->email)->subject($data['subject']);
            });
            return true;
        } catch(Exception $e) {
            return false;
        }
    }

    public static function getCustomerStatusTitle($statusId){
        $custStatus  = config('constants.customerStatus');
        $statusTitle = "In-valid";
        foreach ($custStatus as $eachCustStatus) {
            if($eachCustStatus['value']==$statusId){
                $statusTitle=$eachCustStatus['title'];
                break;
            }
        }
        return $statusTitle;
    }

    public static function getEmployeeStatusTitle($statusId){
        $empStatus  = config('constants.employeeStatus');
        $statusTitle = "In-valid";
        foreach ($empStatus as $eachEmpStatus) {
            if($eachEmpStatus['value']==$statusId){
                $statusTitle=$eachEmpStatus['title'];
                break;
            }
        }
        return $statusTitle;
    }

    public static function getUserStatusTitle($statusId){
        $userStatus  = config('constants.userStatus');
        $statusTitle = "In-valid";
        foreach ($userStatus as $eachUserStatus) {
            if($eachUserStatus['value']==$statusId){
                $statusTitle=$eachUserStatus['title'];
                break;
            }
        }
        return $statusTitle;
    }

    public static function getUnTrackedStatusTitle($statusId){
        $userStatus  = config('constants.untrackedFundStatus');
        $statusTitle = "In-valid";
        foreach ($userStatus as $eachUserStatus) {
            if($eachUserStatus['value']==$statusId){
                $statusTitle=$eachUserStatus['title'];
                break;
            }
        }
        return $statusTitle;
    }

    public static function getCollectionStatusTitle($statusId){
        $userStatus  = config('constants.collectionStatus');
        $statusTitle = "In-valid";
        foreach ($userStatus as $eachUserStatus) {
            if($eachUserStatus['value']==$statusId){
                $statusTitle=$eachUserStatus['title'];
                break;
            }
        }
        return $statusTitle;
    }

    public static function getCollectionTransactionTitle($statusId){
        $userStatus  = config('constants.transactionStatus');
        $statusTitle = "In-valid";
        foreach ($userStatus as $eachUserStatus) {
            if($eachUserStatus['value']==$statusId){
                $statusTitle=$eachUserStatus['title'];
                break;
            }
        }
        return $statusTitle;
    }

    public static function getCollectionTransmissionTitle($statusId){
        $userStatus  = config('constants.transmissionStatus');
        $statusTitle = "In-valid";
        foreach ($userStatus as $eachUserStatus) {
            if($eachUserStatus['value']==$statusId){
                $statusTitle=$eachUserStatus['title'];
                break;
            }
        }
        return $statusTitle;
    }

    public static function getExistingFirms(){
        $firms    = Firm::where('is_deleted','!=',1)->get();
        return $firms;
    }

    public static function getBankDetails(){
        $bank    = BankDetails::all();
        return $bank;
    }

    public static function getBankInfo($whereCond){
        //is_realtime_avs
        $bank    = BankDetails::where($whereCond)->get();
        return $bank;
    }

    public static function getNextCollectionDate($startDate,$currCollectionDate,$debit_frequency,$lastDay=false){
        //Helper::getPaymentDate(["SAME DAY"],date($next_collection_date));
        $dayOfMonth=date('d',strtotime($startDate));
        $month=date('m',strtotime($startDate));
        $nextYear=$year=date('Y',strtotime($startDate));

        $dayInCurrMonth=cal_days_in_month(CAL_GREGORIAN,$month,$year);
        $next_collection_date=$startDate;
        while(strtotime($next_collection_date)<=strtotime($currCollectionDate)){
            if($debit_frequency=="Weekly"){
                $next_collection_date = date('Y-m-d',strtotime("+1 week",strtotime($currCollectionDate)));
            }
            if($debit_frequency=="Monthly"){

                $nextMonth=$month+1;

                if($nextMonth>12){
                    $nextYear=$year+intval($nextMonth/12);
                    $nextMonth=($nextMonth%12==0)?1:$nextMonth%12;
                }
                $daysNextMonth=cal_days_in_month(CAL_GREGORIAN,$nextMonth,$nextYear);
                
                if(($dayInCurrMonth>$daysNextMonth && $dayOfMonth>$daysNextMonth) || $lastDay==true){

                    //$next_collection_date = date("Y-m-d",strtotime("+1 months",strtotime($currCollectionDate)));
                    $next_collection_date = $nextYear."-".str_pad($nextMonth, 2, "0", STR_PAD_LEFT)."-".str_pad($daysNextMonth, 2, "0", STR_PAD_LEFT);
                }elseif($dayOfMonth<=$daysNextMonth){
                    $next_collection_date = date('Y-m-'.$dayOfMonth,strtotime("+1 months",strtotime($currCollectionDate)));
                }else{
                    $next_collection_date = date('Y-m-d',strtotime("+1 months",strtotime($currCollectionDate)));
                }
                //$next_collection_date = date('Y-m-d',strtotime("+1 months",strtotime($currCollectionDate)));
                //die('jg');
            }
            if($debit_frequency=="Annually"){

                $nextYear=$year+1;
                $daysNextMonth=cal_days_in_month(CAL_GREGORIAN,$month,$nextYear);
                if($dayInCurrMonth>$daysNextMonth || $lastDay==true){

                    //$next_collection_date = date("Y-m-d",strtotime("+1 months",strtotime($currCollectionDate)));
                    $next_collection_date = $nextYear."-".str_pad($month, 2, "0", STR_PAD_LEFT)."-".str_pad($daysNextMonth, 2, "0", STR_PAD_LEFT);
                }else{
                    $next_collection_date = date('Y-m-d',strtotime("+1 year",strtotime($currCollectionDate)));
                }
            }
            if($debit_frequency=="Bi-annualy"){
                $next_collection_date = date('Y-m-d',strtotime("+6 months",strtotime($currCollectionDate)));
            }
        }
        
        return Helper::getPaymentDate(["SAME DAY"],date($next_collection_date));;
    }

    public static function getCollectionEndDate($date,$debit_frequency,$duration,$lastDay=false){
        
        //$duration=$duration-1; //as first installment is the startdate itself

       
        $dayOfMonth=date('d',strtotime($date));
        $month=date('m',strtotime($date));
        $nextYear=$year=date('Y',strtotime($date));

        $dayInCurrMonth=cal_days_in_month(CAL_GREGORIAN,$month,$year);


        if($debit_frequency=="Weekly"){
            $endDate = date('Y-m-d',strtotime("+".$duration." week",strtotime($date)));
        }
        if($debit_frequency=="Monthly"){
            $nextMonth=$month+$duration;

            if($nextMonth>12){
                $nextYear=$year+intval($nextMonth/12);
                $nextMonth=($nextMonth%12==0)?1:$nextMonth%12;
            }
            
            
            $daysNextMonth=cal_days_in_month(CAL_GREGORIAN,$nextMonth,$nextYear);

            if($dayInCurrMonth>$daysNextMonth || $lastDay==true){
                $endDate = $nextYear."-".str_pad($nextMonth, 2, "0", STR_PAD_LEFT)."-".str_pad($daysNextMonth, 2, "0", STR_PAD_LEFT);
            }else{
                $endDate = date('Y-m-d',strtotime("+".$duration." months",strtotime($date)));
            }
        }
        if($debit_frequency=="Annually"){
            $nextYear=$year+$duration;
            $daysNextMonth=cal_days_in_month(CAL_GREGORIAN,$month,$nextYear);
            if($dayInCurrMonth>$daysNextMonth || $lastDay==true){

                //$next_collection_date = date("Y-m-d",strtotime("+1 months",strtotime($date)));
                $endDate = $nextYear."-".str_pad($month, 2, "0", STR_PAD_LEFT)."-".str_pad($daysNextMonth, 2, "0", STR_PAD_LEFT);
            }else{
                $endDate = date('Y-m-d',strtotime("+".$duration." year",strtotime($date)));
            }
        }
        if($debit_frequency=="Bi-annualy"){
            $endDate = date('Y-m-d',strtotime("+".$duration." months",strtotime($date)));
        }

        return $endDate;
    }

    public static function deleteDir($dirPath) {
        if (! is_dir($dirPath)) {
            throw new InvalidArgumentException("$dirPath must be a directory");
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::deleteDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }

    public static function convertDate($date,$format="d-m-Y"){
        //echo "\n".$date."\n";
        if( is_null($date) || $date=="null" || trim($date)=="" || $date=="MCO" || strtolower($date)=="none" || $date=="0000-00-00 00:00:00" || $date=="0000-00-00"){
            return "";
        }
        $date1=$date;
        $date=str_replace('/', '-', $date);
        if(gettype($date1)=="object" && get_class($date1)=="MongoDB\BSON\UTCDateTime"){
            return $date1->toDateTime()->format($format);
        }elseif(sizeof(explode('-', $date))>2 || strtotime($date)>0){
        //}elseif(sizeof(explode('-', $date))>2 ){
            return date($format,strtotime($date));  
        }else{
            
            $date=str_replace(',', "", trim($date));
            
            $date = \DateTime::createFromFormat('d F Y', $date); 
            return $date->format($format);
        }
    }

    public static function getSftp(){
        define('NET_SSH2_LOGGING', 2);
        // Upload the file on the bank server
        $ftp_server   = "mftzone.absa.africa";          // Bank server domain
        $ftp_username = "payportsa";             // Bank server user name
        $ftp_userpass = "P@ymentP0rt2020!";  // Bank server password
        

        $sftp = new SFTP($ftp_server);
        $sftp->_disconnect("");
        //$sftp->login($ftp_username, $ftp_userpass);
        if (!$sftp->login($ftp_username, $ftp_userpass)) {
            //print_r($sftp->getLog());
            return false;
        }

        return $sftp;
    }
    public static function getSastTime(){
        $today=gmdate("d-m-Y H:i:s");
        $sastTime=strtotime("+".Config('constants.sastTimeOffset')." minutes",strtotime($today));
        // $hrs=date("Hi",$sastTime);
        // $min=date("i",$sastTime);
        // $sastHrs=intval($hrs)*100+ intval($min);
        return date("Hi",$sastTime);
    }

    public static function businessDayOffset($businessDay){
        $offsetDay=0;
        $workingDays=0;
        $currentDay = strtotime(date('Y-m-d'));
        
        $holidayData=PublicHolidays::where('holiday_date','>=',date('Y-m-d'))->get();
        $holidayDates=[];
        foreach ($holidayData as $key => $eachHoliday) {
            $holidayDates[]=strtotime($eachHoliday['holiday_date']);
        }

        while($workingDays!==$businessDay){
            
            $dayOfTheWeek =date('w',$currentDay);
            
            //if it is not a sunday
            if(intval($dayOfTheWeek)!==0){
                $isHoliday=false;
                if(in_array($currentDay, $holidayDates)){
                    $isHoliday = true;
                }
                
                if($isHoliday===false){
                    $workingDays++;
                }

            }

            $offsetDay++;
            
            $currentDay=strtotime("+1 day",$currentDay);
            
        }


        return $offsetDay;
    }

    public static function getDebitFrequency(){
        return json_encode(Config('constants.debitFrequency'));
    }

    public static function getAccountType(){
        return json_encode(Config('constants.accountType'));
    }

    public static function encryptVar($variable){
        return encrypt($variable);
    }

    public static function generatePaymentReff($limit){
        $accNumber = '';
        for($i = 0; $i < $limit; $i++) { 
            $accNumber .= mt_rand(0, 9); 
        }
        $accNumber="PP".$accNumber;

        $frimData=Firm::where('payment_reff_number',$accNumber)->first();
        if($frimData){
            $accNumber=self::generatePaymentReff($limit);
        }

        return $accNumber;
    }

    public static function lockProfileTransactions(){
        //DB::select('lock tables profile_transactions write')->get();
        //$test=DB::unprepared(DB::raw('lock tables profile_transactions write'));
        //dd(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);
        //echo DB::connection()->getPdo()->getAttribute(1000) . "\n";
        DB::connection()->getPdo()->setAttribute(1000, false);
        echo DB::connection()->getPdo()->getAttribute(1000) . "\n";
        //dd();
        $test=DB::connection()->getPdo()->query('lock tables profile_transactions write');
        $test->fetchAll();
        //$test=DB::statement(DB::raw('select * from profile_transactions'));
        
        //DB::getPdo()->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        //$stmt=DB::getPdo()->exec("lock tables profile_transactions write;");
        //$stmt->fetchAll();
        //dd($test);
        //DB::statement(DB::raw('lock tables profile_transactions write;'));
    }

    public static function unlockTables(){

        //DB::unprepared('unlock tables');
    }

    public static function spaceFiller($word,$sentenceSize,$filler=" ",$position="right"){
        if(strlen($word)>$sentenceSize){
            $word = substr($word, 0, $sentenceSize);
        }

        $sizeOfSpaces = $sentenceSize-strlen($word);
        //final name to be sent
        if($position=="right"){
            $finalWord=$word.str_repeat($filler,$sizeOfSpaces);
        }else{
            $finalWord=str_repeat($filler,$sizeOfSpaces).$word;
        }
        
        if(strlen($finalWord)<$sentenceSize){
            $finalWord=self::spaceFiller($finalWord,$sentenceSize,$filler,$position);
        }
        return $finalWord;
    }


    public static function getCsvDelimiter(string $filePath, int $checkLines = 3): string
   {
          $delimeters =[",", ";", "\t"];

          $default =',';

           $fileObject = new \SplFileObject($filePath);
           $results = [];
           $counter = 0;
           while ($fileObject->valid() && $counter <= $checkLines) {
               $line = $fileObject->fgets();
               foreach ($delimeters as $delimiter) {
                   $fields = explode($delimiter, $line);
                   $totalFields = count($fields);
                   if ($totalFields > 1) {
                       if (!empty($results[$delimiter])) {
                           $results[$delimiter] += $totalFields;
                       } else {
                           $results[$delimiter] = $totalFields;
                       }
                   }
               }
               $counter++;
           }
           if (!empty($results)) {
               $results = array_keys($results, max($results));

               return $results[0];
           }
    return $default;
    }

    public static function prepareCsvData($filepath,$dataArray,$skipRow=0){
        // Reading file
        $file = fopen($filepath,"r");
        $importData_arr=[];
        $i=1;
        while (($filedata = fgetcsv($file, 100000, self::getCsvDelimiter($filepath))) !== FALSE) {
             $num = count($filedata);
             if($num!=sizeof($dataArray)){
                break;
             }
             // Skip first row (Remove below comment if you want to skip the first row)
             if($i <= $skipRow){
                $i++;
                continue; 
             }
             $dataRow=trim(implode('', $filedata));
             if($dataRow==''){
                continue; 
             }
             $importedRow=[];
            for ($c=0; $c < $num; $c++) {
                $importedRow[$dataArray[$c]] = $filedata [$c];
            }
            $importData_arr[$i]=$importedRow;
            $i++;
        }
        fclose($file); 
        return $importData_arr;
    }

    public static function strializeAccountType($accountType){
        $accountType=strtolower($accountType);
        $returnValue=$accountType;
        
        if(in_array($accountType, ['savings','saving'])){
            $returnValue='saving';
        }elseif(in_array($accountType, ['cheque','Cheque',"current"])){
            $returnValue='cheque';
        }elseif(in_array($accountType, ['transmission','transmissions'])){
            $returnValue='transmission';
        }elseif(in_array($accountType, ['bond','bonds','bonded'])){
            $returnValue='bond';
        }elseif(in_array($accountType, ['subscription share','subscriptions share'])){
            $returnValue='subscription share';
        }
        return $returnValue;
    }


    public static function isAvsFieldCorrect($resultSet,$feild){
        $returnValue=false;
        

        if(array_key_exists($feild, $resultSet)){
            if($resultSet[$feild]=='Yes'){
                $returnValue=true;
            }
        }
        return $returnValue;
    }

    public static function sendRealTimeAvsEnquiry($avsEnquiryData=''){
        $sessionKey=Session::get('apiSessionKey');
        if(is_null($sessionKey)){
            //unable to authenticate at ABSA
            if(self::authenticateABSA()==false){
                return false;
            }
        }

        $replyData=self::executeAvsEnquiry($avsEnquiryData);
        return $replyData;
    }

    public static function authABSA(){
        $pemFile = public_path('uploads/cert.p12');
        $a=openssl_x509_parse($pemFile);

        $config = array(
            "digest_alg" => "sha256",
            "private_key_bits" => 4096,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        );
           
        // Create the private and public key
        $res = openssl_pkey_new($config);
        openssl_pkey_export($res, $privKey);

        // Extract the public key from $res to $pubKey
        $pubKey = openssl_pkey_get_details($res);
        $pubKey = $pubKey["key"];

        $userName=config('constants.apiUserName');
        $userPass=config('constants.apiPassword');
        $inputJson=json_encode(["Username"=>$userName,"Password"=>$userPass]);

        $currentTimeObj = new \DateTime();
        $currentTime=$currentTimeObj->format(\DateTime::ISO8601);
        $currentTime=date('Y-m-d\TH:i:s.v\Z');
        $dateStr="X-Date:";
        $xApiStr="X-Client-API-Key:";
        $absaAvsApiKey=config('constants.absaAvsApiKey');

        $requestBody=nl2br('{"Username":"'.$userName.'","Password":"'.$userPass.'"}');
        $newLine=nl2br("\n");
        $signatureToSign="$dateStr$currentTime$newLine$xApiStr$absaAvsApiKey$newLine$requestBody";

        $url=config('constants.apiHostUrl')."/User/Authenticate";

        // Encrypt the data to $encrypted using the public key
        openssl_public_encrypt($signatureToSign, $encrypted, $pubKey);

        openssl_private_decrypt($encrypted, $decrypted, $privKey);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $inputJson);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'X-Date:'.$currentTime,
                'X-Client-API-Key:'.$absaAvsApiKey,
                //'X-SessionKey:EJGSWpZGjKRKqxSX9EiTG4m9spnmZ4L0HfapHKZji9HC6TFE45gl3rkn7D9Dn+PH57gu1TokGyG4PV8GUingpD4=',
                'X-Signature:'.$encrypted,
                'Accept:*/*'
            )
        );

        $data = curl_exec($ch);

        if(curl_errno($ch)){
          curl_close($ch);
          return false;
        }
        
        curl_close($ch);
        //$responseArr=json_decode($data,true);
        print_r($data);
        die();
    }
    public static function authenticateABSA(){
        $userName=config('constants.apiUserName');
        $userPass=config('constants.apiPassword');
        $inputJson=json_encode(["Username"=>$userName,"Password"=>$userPass]);

        $currentTimeObj = new \DateTime();
        $currentTime=$currentTimeObj->format(\DateTime::ISO8601);
        $currentTime=date('Y-m-d\TH:i:s.v\Z');
        $dateStr="X-Date:";
        $xApiStr="X-Client-API-Key:";
        $absaAvsApiKey=config('constants.absaAvsApiKey');

        $requestBody=nl2br('{"Username":"'.$userName.'","Password":"'.$userPass.'"}');
        $newLine=nl2br("\n");
        $signatureToSign="$dateStr$currentTime$newLine$xApiStr$absaAvsApiKey$newLine$requestBody";

        $url=config('constants.apiHostUrl')."/User/Authenticate";

        $pemFile = public_path('uploads/'.config('constants.absaApiPemFile'));
        //$pemFile = file_get_contents('file://'.$pemFile);
        $pkeyid = openssl_pkey_get_private($pemFile, 'kgNIGPECMbxl');
        if ($pkeyid === false) {
            var_dump(openssl_error_string());
        }
        die();
        // $fp = fopen($pemFile, "r");
        // $priv_key = fread($fp, 8192);
        // fclose($fp);
        // $pkeyid = openssl_get_privatekey($priv_key);

        // compute signature with SHA-256
        openssl_sign($signatureToSign, $signature, $pkeyid, "sha256");

        // free the key from memory
        openssl_free_key($pkeyid);


        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $inputJson);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'X-Date:'.$currentTime,
                'X-Client-API-Key:'.$absaAvsApiKey,
                //'X-SessionKey:EJGSWpZGjKRKqxSX9EiTG4m9spnmZ4L0HfapHKZji9HC6TFE45gl3rkn7D9Dn+PH57gu1TokGyG4PV8GUingpD4=',
                'X-Signature:'.$signature,
                'Accept:*/*'
            )
        );

        $data = curl_exec($ch);

        if(curl_errno($ch)){
          curl_close($ch);
          return false;
        }
        
        curl_close($ch);
        print_r($responseArr);
        die();
        $responseArr=json_decode($data,true);
        if(sizeof($responseArr["ErrorList"])<=0){
            Session::put('apiSessionKey',$responseArr["Session"]);
            return true;
        }else{
            
            return false;
        }
        
    }

    public static function executeAvsEnquiry($avsEnquiryData){
        $sessionKey=Session::get('apiSessionKey');
        $capiCode=config('constants.apiCapiCode');
        $clientIdType="1"; //company
        if($avsEnquiryData->avs_type=='individual'){
            $clientIdType="2";
        }
        $accountType=Helper::getAccountCodeAsAbsa($avsEnquiryData->bank_account_type);

        $inputJson=json_encode([
            "BankCode"=>16,
            "BranchCode"=>$avsEnquiryData->branch_code,
            "CapiCode"=>$capiCode,
            "AccountNumber"=>$avsEnquiryData->bank_account_number,
            "AccountType"=>$accountType,
            "ClientIdType"=>$clientIdType,
            "ClientIdNumber"=>$avsEnquiryData->beneficiary_id_number,
            "ClientName"=>$avsEnquiryData->beneficiary_last_name,
            "ClientInitials"=>$avsEnquiryData->beneficiary_initial,
            "Session"=>$sessionKey
            ]);

        $currentTimeObj = new \DateTime();
        $currentTime=$currentTimeObj->format(\DateTime::ISO8601);
        $absaAvsApiKey=config('constants.absaAvsApiKey');

        $url=config('constants.apiHostUrl')."/User/Authenticate";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $inputJson);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'X-Date: '.$currentTime,
                'X-Client-API-Key:'.$absaAvsApiKey,
                'X-SessionKey:EJGSWpZGjKRKqxSX9EiTG4m9spnmZ4L0HfapHKZji9HC6TFE45gl3rkn7D9Dn+PH57gu1TokGyG4PV8GUingpD4=',
                'X-Signature:test',
                'Accept:*/*'
            )
        );

        $data = curl_exec($ch);

        if(curl_errno($ch)){
          curl_close($ch);
          return false;
        }
        
        curl_close($ch);

        $responseArr=json_decode($data,true);
        
        return $responseArr;
    }

    public static function executeAvsReEnquiry($avsEnquiryData){
        $sessionKey=Session::get('apiSessionKey');
        if(is_null($sessionKey)){
            //unable to authenticate at ABSA
            if(self::authenticateABSA()==false){
                return false;
            }
        }

        $capiCode=config('constants.apiCapiCode');
        

        $inputJson=json_encode([
            "ReferenceNumber "=>$avsEnquiryData->avs_reffrence,
            "CapiCode"=>$capiCode,
            "Session"=>$sessionKey
            ]);

        $currentTimeObj = new \DateTime();
        $currentTime=$currentTimeObj->format(\DateTime::ISO8601);
        $absaAvsApiKey=config('constants.absaAvsApiKey');

        $url=config('constants.apiHostUrl')."/Account/ValidateBankReference";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $inputJson);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'X-Date: '.$currentTime,
                'X-Client-API-Key:'.$absaAvsApiKey,
                'X-Signature:test',
                'Accept:*/*'
            )
        );

        $data = curl_exec($ch);

        if(curl_errno($ch)){
          curl_close($ch);
          return false;
        }
        
        curl_close($ch);

        $responseArr=json_decode($data,true);
        
        return $responseArr;
    }

    static function logStatusChanges($changeType,$transmissions,$changeStatement=""){
        foreach ($transmissions as $key => $eachTransmission) {
            $avsLogger=new ChangeTracker();
            $avsLogger->change_type=$changeType;
            $avsLogger->target_id=$eachTransmission->id;
            $avsLogger->change_statement=$changeStatement;
            $avsLogger->created_at=date('Y-m-d H:i:s');
            $avsLogger->save();
        } 
    }

    static function logStatusChange($changeType,$transmission,$changeStatement=""){
            $avsLogger=new ChangeTracker();
            $avsLogger->change_type=$changeType;
            $avsLogger->target_id=$transmission->id;
            $avsLogger->change_statement=$changeStatement;
            $avsLogger->created_at=date('Y-m-d H:i:s');
            $avsLogger->save();
    }

    static function calculatePaymentDate($offsetDate){
        $offsetDateTs=strtotime($offsetDate);
        $newDate     = date('Y-m-d',strtotime("+1 day",$offsetDateTs));
        $newDateTs=strtotime($newDate);
        
        //if given date is sunday
        if(date('N',$newDateTs)==7){
            $newDate=self::calculatePaymentDate($newDate);
        }else{
            $publicHolidays=PublicHolidays::where('holiday_date','=',$newDate)
            ->get();
            if(count($publicHolidays)>0){
                $newDate=self::calculatePaymentDate($newDate);  
            }
        }
        return $newDate;
    }
    
    static function getPaymentDate($serviceTypeArr,$today){
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
            $paymentDate=self::calculatePaymentDate($today);
        }else{
            //if it false on public hiliday
            $publicHolidays=PublicHolidays::where('holiday_date','=',$today)
            ->get();
            if(count($publicHolidays)>0){
                $paymentDate=self::calculatePaymentDate($today);  
            }
        }



        //get payment avoiding holidays in that
        if(in_array("Same Day",$serviceTypeArr) || in_array("sameday",$serviceTypeArr)){
            return $paymentDate;
        }elseif(in_array("1 Day",$serviceTypeArr) || in_array("2 Day",$serviceTypeArr) || in_array("oneday",$serviceTypeArr) || in_array("dated",$serviceTypeArr)){
            $paymentDate=self::calculatePaymentDate($paymentDate);
        }
        
        return $paymentDate;
    }
}

?>