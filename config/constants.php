<?php
	$constant=[
		'userStatus'=>[
			["value"=>1,'title'=>'Active'],
			["value"=>0,'title'=>'In-Active']
		],
		'customerStatus'=>[
			["value"=>0,'title'=>'Pending'],
			["value"=>1,'title'=>'Active'],
			["value"=>2,'title'=>'In-active'],
			["value"=>3,'title'=>'Rejected']
			
		],
		'employeeStatus'=>[
			["value"=>0,'title'=>'Pending'],
			["value"=>1,'title'=>'Active'],
			["value"=>2,'title'=>'In-active'],
			["value"=>3,'title'=>'Rejected']
			
		],
		'collectionStatus'=>[
			["value"=>0,'title'=>'Pending'],
			["value"=>1,'title'=>'Approved'],
			["value"=>2,'title'=>'Rejected']
		],
		'status' => [
			1 => 'Active',
			0 => 'In-Active'
		],
		'employeestatus' => [
			1 => 'Active',
			2 => 'In-Active'
		],
		'transactionStatus'=>[
			["value"=>0,'title'=>'Pending'],
			["value"=>1,'title'=>'Success'],
			["value"=>2,'title'=>'Failed'],
			["value"=>3,'title'=>'Disputed']
		],
		'transmissionStatus'=>[
			["value"=>0,'title'=>'Pending'],
			["value"=>1,'title'=>'Transmitted'],
			["value"=>2,'title'=>'Accepted'],
			["value"=>3,'title'=>'Rejected']
		],
		'serviceType'=>['Same Day','1 Day','2 Day'],
		'debitFrequency'=>[
			//'Weekly',
			'Monthly',
			'Annually',
			//"Bi-annualy"
			],
		'accountType'=> ["cheque","saving","transmission","bond","subscription share"],
		'paymentAccountType'=> ["cheque","saving","transmission","bond","subscription share"],
		'collectionAccountType'=> ["cheque","saving"],
		'accountTypeCodes'=>[
			"cheque"=>1, 
			"saving"=>2, 
			"transmission"=>3,
			"bond"=>4, 
			"subscription share"=>6
		],
		'avsTypes'=> ["individual","business"],
		'collectionType'=>["OnceOff","Recurring"],
		'entry_class' => [
			21 => 'Insurance Premiums',
			22 => 'Pension Fund Contributions',
			23 =>  'Medical Aid Fund Contributions', 
			26 =>  'Unit Trust Purchases', 
			28 =>  'Charitable or Religious Contributions', 
			31 =>  'H.P. Repayment', 
			32 =>  'Account Repayment', 
			33 =>  'Loan Repayment (other than Mortgage)', 
			34 =>  'Rental-Lease (other than Property)', 
			35 =>  'Service Charge (Maintenance of Service Agreements, etc - Fixed amounts)', 
			36 =>  'Service Charge (Variable Amounts)', 
			37 =>  'Value Added Tax (Vat collection)',
			41 =>  'Rent (Property)',
			42 =>  'Bond Repayments', 
			44 =>  'Bank Use - Debit Transfer - Corporate Entry',
			49 =>  'NAEDO Disputes over 40 days',
			51 =>  'Municipal Accounts: Water and Lights',
			52 =>  'Municipal Accounts: Rates', 
			53 =>  'Telephone Accounts',
			54 =>  'Bank Use - Credit Card Merchant - Electronic Funds Transfer', 
			55 =>  'Bank Use - Credit Card Holder -Electronic Funds Transfer',
			56 =>  'Bank Use - Service charge except from duty and service fee',
			57 =>  'Bank Use - Garage Card', 
			58 =>  'Bank Use - Service Charge',
			59 =>  'Bankserv Use - Service Charge',
		],

		'untrackedFundStatus'=>[
			["value"=>'pending','title'=>'Pending'],
			["value"=>'allocated','title'=>'Allocated'],
			["value"=>'un-transferable','title'=>'Non-Transferable']
		],
		'maxFileUploadSize'=>10485760, //10MB 10*1024*1024
		'payportFirmId'=>1,
		'payportEnv'=>'L',
		'bankingSuitUserCode'=>"07303",
		'bankingSuitUserName'=>"PAYPORT CONSULTING PTY LTD",
		'bankSerUserCode'=>"D237",
		'bankingSuitFolder'=>'ZR07303',
		'bankingCutOffTime'=>'1530',
		'bankingCutOffTimeReadable'=>'03:30 PM',
		'sastTimeOffset'=>"120",
		'batchOffsetDays'=>1,
		'sameDayBuffer'=>0,
		'oneDayBuffer'=>1,
		'twoDayBuffer'=>1,
		'reocurTwoDayCalOffset'=>3,
		'normalTwoDayCalOffset'=>2,
		'normalOneDayCalOffset'=>1,
		'normalSameDayCalOffset'=>0,
		'currectionBufferDay'=>1,
		'remoteFileDownloadLocation'=>"/transferzone/PayportSA/LDC_Outgoing/",

		'localCollectionFileStoragePath'=>'files/collections/outgoing/',
		'remoteCollectionOutputPath'=>'/transferzone/PayportSA/incoming/',

		'lastLedgerTranxCond'=>['failed_collection','collection'],


		'sameDayPaymentSuitUserCode'=>"06431", //T"03686",L"06431",
		'sameDayPaymentSuitUserName'=>"PAYPORT CONSULTING PTY LTD",
		'sameDayPaymentbankSerUserCode'=>"C635", //L
		//'sameDayPaymentbankSerUserCode'=>"F799", //T
		'sameDayPaymentSuitFolder'=>'ZR06431', //L
		//'sameDayPaymentSuitFolder'=>'ZR03686', //T
		'sameDayPaymentOffset'=>0,
		'localSameDayPaymentFileStoragePath'=>'files/payments/sameday/outgoing/',
		'localSameDayPaymentFileDownloadStoragePath'=>'files/payments/sameday/incoming/',
		'localSameDayPaymentProcessedPath'=>'files/payments/sameday/processed/',
		//'remoteSameDayPaymentOutputPath'=>'/transferzone/PayportSA/UAT/incoming/', //T
		'remoteSameDayPaymentOutputPath'=>'/transferzone/PayportSA/incoming/',//L
		'sameDayPaymentCutOffTime'=>'1500',
		'sameDayPaymentCutOffTimeReadable'=>'03:00 PM',
		//'remoteSameDayFileDownloadLocation'=>"/transferzone/PayportSA/UAT/outgoing/", //T
		'remoteSameDayFileDownloadLocation'=>"/transferzone/PayportSA/LDC_Outgoing/", //L

		'oneDayPaymentSuitUserCode'=>"06432",//T"03686",L"06432",
		'oneDayPaymentSuitUserName'=>"PAYPORT CONSULTING PTY LTD",
		'oneDayPaymentbankSerUserCode'=>"C637", //L
		//'oneDayPaymentbankSerUserCode'=>"F800", //T
		'oneDayPaymentSuitFolder'=>'ZR06432', //L
		//'oneDayPaymentSuitFolder'=>'ZR03686', //T
		'oneDayPaymentOffset'=>1,
		'localOneDayPaymentFileStoragePath'=>'files/payments/oneday/outgoing/',
		'localOneDayPaymentFileDownloadStoragePath'=>'files/payments/oneday/incoming/',
		'localOneDayPaymentProcessedPath'=>'files/payments/oneday/processed/',
		//'remoteOneDayPaymentOutputPath'=>'/transferzone/PayportSA/UAT/incoming/', //T
		'remoteOneDayPaymentOutputPath'=>'/transferzone/PayportSA/incoming/', //L
		'oneDayPaymentCutOffTime'=>'1530',
		'oneDayPaymentCutOffTimeReadable'=>'03:30 PM',
		//'remoteOneDayFileDownloadLocation'=>"/transferzone/PayportSA/UAT/outgoing/", //T
		'remoteOneDayFileDownloadLocation'=>"/transferzone/PayportSA/LDC_Outgoing/", //L
		'profileAlertValue'=>500,

		'remoteNotifyDirPath'=>'/transferzone/PayportSA/NMB_Outgoing/',
		'localNotifyProcessedDirPath'=>'files/NMB/processed/',
		'localNotifyDownloadDirPath'=>'files/NMB/incoming/',
		'NMBBusinessCode'=>'NMB00324',
		'NMBFilePrefix'=>'NM00324.A4884',

		// Avs Settings
		//'avsBankingSuitFolder'=>'ZR06429', // L
		//'avsBankingSuitUserCode'=>'06429', // L
		'avsBankingSuitFolder'=>'ZR03686', // T
		'avsBankingSuitUserCode'=>'03686', // T
		'avsbankSerUserCode'=>'ABSAIN',
		'avsSuitUserName'=>"PAYPORT CONSULTING PTY LTD",
		'localAvsFileStoragePath'=>'files/avs/outgoing/', 
		'localAvsDownloadStoragePath'=>'files/avs/incoming/', 
		'localAvsProcessedPath'=>'files/avs/processed/',
		//'remoteAvsOutputPath'=>'/transferzone/PayportSA/incoming/', //L
		//'remoteAvsFileDownloadLocation'=>"/transferzone/PayportSA/LDC_Outgoing/", //L
		'remoteAvsOutputPath'=>'/transferzone/PayportSA/UAT/incoming/', //T
		'remoteAvsFileDownloadLocation'=>"/transferzone/PayportSA/UAT/outgoing/", //T
		'avsCutOffTime'=>'1530',

		//'apiHostUrl'=>'https://mercurius-uat.cib.digital/api/',//T
		'apiHostUrl'=>'https://capi.absa.co.za/api/', //L
		//'absaAvsApiKey'=>'c33e9fb7-24f6-4a25-bcd9-5e8cf2817854',//T
		'absaAvsApiKey'=>'747721195688886272', //L
		'apiUserName'=>'PayPortSA',
		'apiPassword'=>'P@yP0rtSa2020!',
		'apiCapiCode'=>'Not provided yet',
		'absaApiPemFile'=>'payport-cert.pem',
		'maxRecordInCsvFile'=>2500
		
	];

	return $constant;

//https://capi.absa.co.za/api/User/Authenticate
// {
//     "Username":"PayPortSA",
//     "Password":"P@yP0rtSa2020!"
// }
//kgNIGPECMbxl