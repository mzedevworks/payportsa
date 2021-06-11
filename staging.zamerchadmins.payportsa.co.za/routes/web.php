<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
	if(auth()->user()){
		return redirect('home');
	}
    return view('auth.login');
});

Route::get('customer/transaction','HomeController@handle');
Route::get('collection','HomeController@collection');
Route::get('readdata','HomeController@readdata');
Route::get('downloadreply','HomeController@downloadreply');

Auth::routes();

Route::get('home','HomeController@index');
Route::any('change/password','HomeController@changePassword')->name('change.password');

// Super Admin Routing
Route::namespace('Admin')->middleware('is_admin','is_password_changed')->prefix('admin')->group(function()
{

	Route::get('', 'DashboardController@index');
	Route::get('dashboard', 'DashboardController@index');
	Route::post('dashboard/collection-graph-data', 'DashboardController@collectionGraph');

	Route::post('dashboard/payment-graph-data', 'DashboardController@paymentGraph');
	
	Route::any('edit/profile','DashboardController@editProfile');
	

	//payment wallets
	Route::get('payment-wallet/non-allocated', 'PaymentWalletController@nonAllocatedFundList');
	Route::get('payment-wallet/ajax-non-allocated', 'PaymentWalletController@ajaxNonAllocatedFundList');
	Route::any('payment-wallet/allocate/{id}', 'PaymentWalletController@allocatedFund');
	Route::get('payment-wallet/*', 'PaymentWalletController@nonAllocatedFundList');
	// Collection wallet routes
	Route::get('collection-wallet', 'CollectionWalletController@profilestats');
	Route::get('collection-wallet/profilestats', 'CollectionWalletController@profilestats');
	Route::get('collection-wallet/topup', 'CollectionWalletController@topups');
	Route::post('collection-wallet/topup', 'CollectionWalletController@topups');

	Route::get('collection-wallet/*', 'CollectionWalletController@profilestats');
	// Firms/Company Routing
	Route::get('firms', 'FirmController@index');
	Route::any('firms/create', 'FirmController@create');
	Route::get('firms/listing/ajax', 'FirmController@ajaxFirmsList');
	Route::any('firms/update/{id}', 'FirmController@updateFirm');

	Route::any('firms/info/{id}', 'FirmController@viewFirmInfo');
	Route::any('firms/user/{id}', 'FirmController@viewFirmUser');
	Route::any('firms/rates/{id}', 'FirmController@viewFirmRates');
	Route::any('firms/collection-limit/{id}', 'FirmController@viewFirmCollectionProfile');
	Route::any('firms/monthly-collection/{id}', 'FirmController@viewFirmMonthlyCollection');
	Route::any('firms/payment-stats/{id}', 'FirmController@viewFirmPaymentStats');
	Route::get('firms/payment-batch/tranx/{batchId}', 'FirmController@batchPaymentList');
	Route::get('firms/payment-batch/ajax-tranx/{batchId}','FirmController@ajaxBatchPaymentList');


	Route::delete('firms/delete', 'FirmController@deleteFirm');
	Route::delete('firms/deletemultiple', 'FirmController@deleteMultipleFirms');

	// Rates and profile limits
	Route::any('firms/add/rates/{id}','FirmController@addRatesAndProfileLimit');
	Route::any('firms/update/rates/{id}','FirmController@updateRatesAndProfileLimit');
   

   	Route::get('banks', 'BankController@index');
	Route::any('banks/create', 'BankController@create');
	Route::get('banks/listing/ajax', 'BankController@ajaxBanksList');
	Route::any('banks/update/{id}', 'BankController@updateBank');

	//Merchant Routing
	Route::get('merchants', 'MerchantController@index');
	Route::any('merchants/create', 'MerchantController@store');
	Route::get('merchants/listing/ajax', 'MerchantController@ajaxUsersList');
	Route::any('merchants/update/{id}', 'MerchantController@update');
	Route::delete('merchants/delete', 'MerchantController@deleteUser');
	Route::delete('merchants/deletemultiple', 'MerchantController@deleteMultipleUsers');


	Route::get('login/as/merchant/{id}','MerchantController@loginAsMerchant');

	//Administor management Routing
	Route::get('administors', 'UserController@administors');
	Route::any('add/administors', 'UserController@addAdministors');
	Route::get('listing/administor/ajax', 'UserController@ajaxAdministorsList');
	Route::delete('delete/administor', 'UserController@deleteAdministors');
	// Route::delete('delete/administor/{id}', 'UserController@deleteAdministors');

	Route::delete('deletemultiple/administor', 'UserController@deleteMultipleAdministors');
	Route::any('update/administor/{id}', 'UserController@updateAdministors');


	// Holiday Routing
	Route::get('holidays','HolidayController@index');
	Route::any('holidays/create', 'HolidayController@create');
	Route::get('holidays/listing/ajax', 'HolidayController@ajaxHolidayList');
	Route::delete('holidays/delete', 'HolidayController@deleteHoliday');
	Route::delete('holidays/deletemultiple', 'HolidayController@deleteMultipleHoliday');

	Route::get('setting','HolidayController@index');
	Route::any('setting/holidays/update/{id}', 'HolidayController@updateHoliday');
	Route::get('setting/holidays','HolidayController@index');
	Route::get('setting/setproduct','SettingController@setProducts');
	Route::get('setting/merchant/product/{id}','SettingController@getMerchantProcucts');
	Route::get('setting/profile-limit','SettingController@profileLimit');
	Route::post('setting/profile-limit','SettingController@profileLimit');


	// Output file listing
	Route::get('outputs', 'OutputController@collection');
	
	Route::any('outputs/collection', 'OutputController@collection');
	Route::get('outputs/ajax-collection-listing', 'OutputController@ajaxCollectionList');
	Route::get('outputs/download-file/{outputFileId}','OutputController@outputDownload');

	Route::get('outputs/collection-transactions/{outputFileId}', 'OutputController@collectionDetail');
	Route::get('outputs/collection-ajax-transaction/{outputFileId}', 'OutputController@ajaxCollectionDetail');

	Route::any('outputs/payment', 'OutputController@payment');
	Route::get('outputs/ajax-payment-listing', 'OutputController@ajaxPaymentList');

	Route::get('outputs/payment-transaction/{outputFileId}', 'OutputController@paymentDetail');
	Route::get('outputs/payment-ajax-transaction/{outputFileId}', 'OutputController@ajaxPaymentDetail');

	Route::any('outputs/avs', 'OutputController@avs');
	Route::get('outputs/ajax-avs-listing', 'OutputController@ajaxAvsList');
	// end of output file listing urls

	//transmision routings
	Route::get('transmission', 'TransmissionController@collection');
	
	Route::any('transmission/collection', 'TransmissionController@collection');
	Route::get('transmission/ajax-collection-listing', 'TransmissionController@ajaxCollectionList');
	Route::get('transmission/collection-trax/{outputFileId}','TransmissionController@collectionTranDownload');
	Route::get('transmission/collection-reply/{outputFileId}','TransmissionController@collectionReplyDownload');
	Route::get('transmission/collection-transactions/{transmissionId}', 'TransmissionController@collectionDetail');
	Route::get('transmission/collection-ajax-transaction/{transmissionId}', 'TransmissionController@ajaxCollectionDetail');

	Route::any('transmission/payment', 'TransmissionController@payment');
	Route::get('transmission/ajax-payment-listing', 'TransmissionController@ajaxPaymentList');
	Route::get('transmission/payment-trax/{outputFileId}','TransmissionController@paymentTranDownload');
	Route::get('transmission/payment-reply/{outputFileId}','TransmissionController@paymentReplyDownload');
	Route::get('transmission/payment-transactions/{transmissionId}', 'TransmissionController@paymentDetail');
	Route::get('transmission/payment-ajax-transaction/{transmissionId}', 'TransmissionController@ajaxPaymentDetail');

	Route::any('transmission/avs', 'TransmissionController@avs');
	Route::get('transmission/ajax-avs-listing', 'TransmissionController@ajaxAvsList');
	Route::get('transmission/avs-trax/{outputFileId}','TransmissionController@avsTranDownload');
	Route::get('transmission/avs-reply/{outputFileId}','TransmissionController@avsReplyDownload');
	Route::get('transmission/avs-transactions/{transmissionId}', 'TransmissionController@avsDetail');
	Route::get('transmission/avs-ajax-transaction/{transmissionId}', 'TransmissionController@ajaxAvsDetail');
	Route::get('transmission/avs/result/{avsId}', 'TransmissionController@showAvsResult');

	
	// normal batch urls
	Route::get('batch-collection/normal', 'NormalBatchController@pending');
	Route::get('batch-collection/normal/pending', 'NormalBatchController@pending');
	Route::get('batch-collection/normal/ajax-pending','NormalBatchController@ajaxPendingList');

	Route::get('batch-collection/normal/pending/{id}','NormalCollectionController@pendingList');
	Route::get('batch-collection/normal/ajax-pending/{id}','NormalCollectionController@ajaxPendingList');

	Route::get('batch-collection/normal/queued','NormalBatchController@queued');
	Route::get('batch-collection/normal/ajax-queued','NormalBatchController@ajaxQueuedList');

	Route::get('batch-collection/normal/queued/{id}','NormalCollectionController@queuedList');
	Route::get('batch-collection/normal/ajax-queued/{id}','NormalCollectionController@ajaxProcessedList');

	Route::get('batch-collection/normal/processed','NormalBatchController@processed');
	Route::get('batch-collection/normal/ajax-processed','NormalBatchController@ajaxProcessedList');

	Route::get('batch-collection/normal/processed/{id}','NormalCollectionController@processedList');
	Route::get('batch-collection/normal/ajax-processed/{id}','NormalCollectionController@ajaxProcessedList');
	
	// Reoccur batches urls
	Route::get('batch-collection/reoccur/pending', 'ReoccurBatchController@pending');
	Route::get('batch-collection/reoccur/ajax-pending','ReoccurBatchController@ajaxPendingList');

	Route::get('batch-collection/reoccur/pending/{id}','ReoccurCollectionController@pendingList');
	Route::get('batch-collection/reoccur/ajax-pending/{id}','ReoccurCollectionController@ajaxProcessedList');

	Route::get('batch-collection/reoccur/submitted','ReoccurBatchController@submitted');
	Route::get('batch-collection/reoccur/ajax-submitted','ReoccurBatchController@ajaxSubmittedList');

	Route::get('batch-collection/reoccur/submitted/{id}','ReoccurCollectionController@submittedList');
	Route::get('batch-collection/reoccur/ajax-submitted/{id}','ReoccurCollectionController@ajaxProcessedList');

	Route::get('batch-collection/reoccur/processed','ReoccurBatchController@processed');
	Route::get('batch-collection/reoccur/ajax-processed','ReoccurBatchController@ajaxProcessedList');

	Route::get('batch-collection/reoccur/processed/{id}','ReoccurCollectionController@processedList');
	Route::get('batch-collection/reoccur/ajax-processed/{id}','ReoccurCollectionController@ajaxProcessedList');


	// payment batch urls (salary)
	Route::get('batch-payment/salary', 'SalaryBatchController@pending');
	Route::get('batch-payment/salary/pending', 'SalaryBatchController@pending');
	Route::get('batch-payment/salary/ajax-pending','SalaryBatchController@ajaxPendingList');

	Route::get('batch-payment/salary/pending/{id}','SalaryPaymentController@pendingList');
	Route::get('batch-payment/salary/ajax-pending/{id}','SalaryPaymentController@ajaxPendingList');

	Route::get('batch-payment/salary/queued','SalaryBatchController@queued');
	Route::get('batch-payment/salary/ajax-queued','SalaryBatchController@ajaxQueuedList');

	Route::get('batch-payment/salary/queued/{id}','SalaryPaymentController@queuedList');
	Route::get('batch-payment/salary/ajax-queued/{id}','SalaryPaymentController@ajaxProcessedList');

	Route::get('batch-payment/salary/processed','SalaryBatchController@processed');
	Route::get('batch-payment/salary/ajax-processed','SalaryBatchController@ajaxProcessedList');

	Route::get('batch-payment/salary/processed/{id}','SalaryPaymentController@processedList');
	Route::get('batch-payment/salary/ajax-processed/{id}','SalaryPaymentController@ajaxProcessedList');

	// payment batch urls (creditors)
	Route::get('batch-payment/credit/pending', 'CreditBatchController@pending');
	Route::get('batch-payment/credit/ajax-pending','CreditBatchController@ajaxPendingList');

	Route::get('batch-payment/credit/pending/{id}','CreditPaymentController@pendingList');
	Route::get('batch-payment/credit/ajax-pending/{id}','CreditPaymentController@ajaxProcessedList');

	Route::get('batch-payment/credit/queued','CreditBatchController@queued');
	Route::get('batch-payment/credit/ajax-queued','CreditBatchController@ajaxQueuedList');

	Route::get('batch-payment/credit/queued/{id}','CreditPaymentController@submittedList');
	Route::get('batch-payment/credit/ajax-queued/{id}','CreditPaymentController@ajaxProcessedList');

	Route::get('batch-payment/credit/processed','CreditBatchController@processed');
	Route::get('batch-payment/credit/ajax-processed','CreditBatchController@ajaxProcessedList');

	Route::get('batch-payment/credit/processed/{id}','CreditPaymentController@processedList');
	Route::get('batch-payment/credit/ajax-processed/{id}','CreditPaymentController@ajaxProcessedList');

	// transaction reports url
	Route::get('tranx-report','TransactionReportController@reports');
	Route::get('tranx-report/collection','TransactionReportController@collection');
	Route::get('tranx-report/collection/ajax-reports','TransactionReportController@ajaxCollection');

	Route::get('tranx-report/payment','TransactionReportController@payment');
	Route::get('tranx-report/payment/ajax-reports','TransactionReportController@ajaxPayment');

	Route::get('tranx-report/avs','TransactionReportController@avs');
	Route::get('tranx-report/avs/ajax-reports','TransactionReportController@ajaxAvs');
	Route::post('tranx-report/avs/detailed','TransactionReportController@ajaxAvsDetail');

	Route::post('tranx-report/logs','TransactionReportController@ajaxLogList');

});

Route::any('merchant/password/reset','Merchant\HomeController@passwordReset')->name('merchant.password.update');

// Merchant Routing
Route::namespace('Merchant')->middleware('is_merchant','is_password_changed')->prefix('merchant')->group(function(){
	
	// Dashboard routing
	Route::get('', 'DashboardController@index');
	Route::get('dashboard', 'DashboardController@index');
	
	Route::post('dashboard/collection-graph-data', 'DashboardController@collectionGraph');

	Route::post('dashboard/payment-graph-data', 'DashboardController@paymentGraph');

	Route::get('wallet/payment/tranx/{id}','PaymentWalletController@batchTranx');
	Route::get('wallet/payment/ajax-batch-transaction/{id}','PaymentWalletController@ajaxBatchTranxList');
	
	Route::any('wallet/payment', 'PaymentWalletController@profilestats');


	// Route::get('wallet/collection', 'DashboardController@collectionWallet');
	Route::get('wallet/collection/summary','DashboardController@collectionSummary');
	
	Route::get('wallet/collection/profilestats', 'CollectionWalletController@profilestats');
	Route::get('wallet/collection', 'CollectionWalletController@profilestats');
	Route::get('wallet', 'DashboardController@index');
	

	Route::post('login/as/admin','DashboardController@loginAsAdmin')->name('merchant.admin.login');
    Route::any('edit/profile','DashboardController@editProfile');

    // Employees Routing 
	Route::get('employees','EmployeesController@index')->middleware('is_product:salary');
	Route::any('employees/create', 'EmployeesController@create')->middleware('is_product:salary');
	Route::get('employees/listing/ajax', 'EmployeesController@ajaxEmployeesList');
	Route::any('employees/update/{id}', 'EmployeesController@update')->middleware('is_product:salary');
	Route::get('employees/view/{id}', 'EmployeesController@viewEmployee')->middleware('is_product:salary');
	Route::get('employees/transactions/{id}', 'EmployeesController@viewEmployeeTransactions')->middleware('is_product:salary');
	Route::get('employees/transactions/ajax-tranx/{id}','EmployeesController@ajaxEmployeePaymentList')->middleware('is_product:salary');
	//Route::delete('employees/delete', 'EmployeesController@deleteEmployee');
	//Route::delete('employees/deletemultiple', 'EmployeesController@deleteMultipleEmployees');

	Route::get('employees/pending-list','EmployeesController@pendingList')->middleware('is_product:salary')->middleware('is_merchant_admin');;
	Route::get('employees/ajax-pendinglist', 'EmployeesController@pendingAjaxUserList');
	Route::any('employees/pendingupdate/{id}', 'EmployeesController@updatePendingCustomer')->middleware('is_product:salary')->middleware('is_merchant_admin');;
	Route::any('employees/pendingview/{id}', 'EmployeesController@viewPendingCustomer')->middleware('is_product:salary')->middleware('is_merchant_admin');;

	Route::post('employees/statusupdate', 'EmployeesController@statusUpdate')->middleware('is_product:salary')->middleware('is_merchant_admin');;
	Route::any('employees/mul-statusupdate', 'EmployeesController@mulStatusUpdate')->middleware('is_product:salary')->middleware('is_merchant_admin');;
	
	Route::any('employees/create-batch', 'EmployeesController@createBatch')->middleware('is_product:salary');
	Route::any('employees/update-batch/{id}', 'EmployeesController@updateBatch')->middleware('is_product:salary');

	Route::any('employees/listforbatch', 'EmployeesController@ajaxlistforbatch')->middleware('is_product:salary');
	Route::post('employees/savebatch', 'EmployeesController@savebatch')->middleware('is_product:salary');
	Route::get('employees/samplebatchcsv','EmployeesController@samplebatchcsvDownload')->middleware('is_product:salary');
	Route::post('employees/batchimport','EmployeesController@batchimport')->middleware('is_product:salary');
	Route::any('employees/savecsvbatch','EmployeesController@savecsvbatch')->middleware('is_product:salary');

	// Temporary Employees Routing 
	Route::get('employees/samplecsv','EmployeesController@samplecsvDownload')->middleware('is_product:salary');
	Route::post('employees/import','EmployeesController@import')->middleware('is_product:salary');
	Route::get('employees/temp/list','EmployeesController@tempList');
	Route::delete('temp/employees/delete/{id}', 'EmployeesController@tempEmployeeDelete')->middleware('is_product:salary');
	Route::post('employees/save/temp','EmployeesController@editTempEmp');
	Route::get('employees/delete/tempcsv','EmployeesController@deleteTempList');
	Route::post('employees/save-multiple/temp','EmployeesController@editMultipleTempEmployees')->middleware('is_product:salary');

	// salary batches urls

	Route::get('employees/batch/pending','SalaryBatchController@index')->middleware('is_product:salary');
	Route::get('employees/batch/ajax-pending','SalaryBatchController@ajaxPendingList')->middleware('is_product:salary');
	Route::post('employees/batch/statusupdate','SalaryBatchController@statusUpdate')->middleware('is_product:salary');

	Route::any('employees/batch/pending-transaction/{id}','SalaryController@pendingList')->middleware('is_product:salary');
	Route::any('employees/batch/pending-tranx-detail/{id}','SalaryController@pendingListView')->middleware('is_product:salary');
	Route::get('employees/batch/pending-ajax-transaction/{id}','SalaryController@ajaxPendingList')->middleware('is_product:salary');
	Route::post('employees/batch/transmission/statusupdate','SalaryController@updateStatus');
	
	Route::post('employees/batch/pending-transmission/amountupdate','SalaryController@updateAmount')->middleware('is_product:salary')->middleware('is_merchant_admin');;
	
	Route::get('employees/batch/queued','SalaryBatchController@queued')->middleware('is_product:salary');
	Route::get('employees/batch/ajax-queued','SalaryBatchController@ajaxQueuedList')->middleware('is_product:salary');

	Route::get('employees/batch/queued-transaction/{id}','SalaryController@queuedList')->middleware('is_product:salary');
	Route::get('employees/batch/queued-ajax-transaction/{id}','SalaryController@ajaxQueuedList')->middleware('is_product:salary');

	Route::get('employees/batch/processed','SalaryBatchController@processedList')->middleware('is_product:salary');
	Route::get('employees/batch/ajax-processed','SalaryBatchController@ajaxProcessedList')->middleware('is_product:salary');

	Route::get('employees/batch/processed-transaction/{id}','SalaryController@processedList')->middleware('is_product:salary');
	Route::get('employees/batch/processed-ajax-transaction/{id}','SalaryController@ajaxProcessedList')->middleware('is_product:salary');
	// end of salary routing
	
	// Creditors routing
	//merchant/creditors
	Route::get('creditors','CreditorsController@index')->middleware('is_product:creditor');
	Route::any('creditors/create', 'CreditorsController@create')->middleware('is_product:creditor');
	Route::get('creditors/listing/ajax', 'CreditorsController@ajaxCreditorsList');
	Route::any('creditors/update/{id}', 'CreditorsController@update')->middleware('is_product:creditor');
	Route::get('creditors/view/{id}', 'CreditorsController@viewCreditor')->middleware('is_product:creditor');
	Route::get('creditors/transactions/{id}', 'CreditorsController@viewCreditorTransactions')->middleware('is_product:creditor');
	Route::get('creditors/transactions/ajax-tranx/{id}','CreditorsController@ajaxCreditorPaymentList')->middleware('is_product:creditor');

	Route::post('creditors/statusupdate', 'CreditorsController@statusUpdate')->middleware('is_product:creditor')->middleware('is_merchant_admin');;

	Route::any('creditors/create-batch', 'CreditorsController@createBatch')->middleware('is_product:creditor');
	Route::any('creditors/update-batch/{id}', 'CreditorsController@updateBatch')->middleware('is_product:creditor');

	Route::any('creditors/listforbatch', 'CreditorsController@ajaxlistforbatch')->middleware('is_product:creditor');
	Route::post('creditors/savebatch', 'CreditorsController@savebatch')->middleware('is_product:creditor');
	Route::get('creditors/samplebatchcsv','CreditorsController@samplebatchcsvDownload')->middleware('is_product:creditor');
	Route::post('creditors/batchimport','CreditorsController@batchimport')->middleware('is_product:creditor');
	Route::any('creditors/savecsvbatch','CreditorsController@savecsvbatch')->middleware('is_product:creditor');

	
	// Temporary Employees Routing 
	Route::get('creditors/samplecsv','CreditorsController@samplecsvDownload')->middleware('is_product:creditor');
	Route::post('creditors/import','CreditorsController@import')->middleware('is_product:creditor');
	Route::get('creditors/temp/list','CreditorsController@tempList');
	Route::delete('temp/creditors/delete/{id}', 'CreditorsController@tempCreditorDelete')->middleware('is_product:creditor');
	Route::post('creditors/save/temp','CreditorsController@editTempEmp');
	Route::get('creditors/delete/tempcsv','CreditorsController@deleteTempList');
	Route::post('creditors/save-multiple/temp','CreditorsController@editMultipleTempEmployees')->middleware('is_product:creditor');

	// creditors batches urls

	Route::get('creditors/batch/pending','CreditorBatchController@index')->middleware('is_product:creditor');
	Route::get('creditors/batch/ajax-pending','CreditorBatchController@ajaxPendingList')->middleware('is_product:creditor');
	Route::post('creditors/batch/statusupdate','CreditorBatchController@statusUpdate')->middleware('is_product:creditor');

	Route::any('creditors/batch/pending-transaction/{id}','CreditController@pendingList')->middleware('is_product:creditor');
	Route::any('creditors/batch/pending-tranx-detail/{id}','CreditController@pendingListView')->middleware('is_product:creditor');
	Route::get('creditors/batch/pending-ajax-transaction/{id}','CreditController@ajaxPendingList')->middleware('is_product:creditor');
	Route::post('creditors/batch/transmission/statusupdate','CreditController@updateStatus');
	
	Route::post('creditors/batch/pending-transmission/amountupdate','CreditController@updateAmount')->middleware('is_product:creditor')->middleware('is_merchant_admin');;
	
	Route::get('creditors/batch/queued','CreditorBatchController@queued')->middleware('is_product:creditor');
	Route::get('creditors/batch/ajax-queued','CreditorBatchController@ajaxQueuedList')->middleware('is_product:creditor');

	Route::get('creditors/batch/queued-transaction/{id}','CreditController@queuedList')->middleware('is_product:creditor');
	Route::get('creditors/batch/queued-ajax-transaction/{id}','CreditController@ajaxQueuedList')->middleware('is_product:creditor');

	Route::get('creditors/batch/processed','CreditorBatchController@processedList')->middleware('is_product:creditor');
	Route::get('creditors/batch/ajax-processed','CreditorBatchController@ajaxProcessedList')->middleware('is_product:creditor');

	Route::get('creditors/batch/processed-transaction/{id}','CreditController@processedList')->middleware('is_product:creditor');
	Route::get('creditors/batch/processed-ajax-transaction/{id}','CreditController@ajaxProcessedList')->middleware('is_product:creditor');

	//end of creditors routing


	//for usersControllers for maintaning users meant for merchants.
	Route::get('users','UsersController@index')->middleware('is_merchant_admin');
	Route::any('users/create','UsersController@createUser')->middleware('is_merchant_admin');
	Route::get('users/listing/ajax', 'UsersController@ajaxUsersList')->middleware('is_merchant_admin');
	Route::delete('user/delete', 'UsersController@deleteUser')->middleware('is_merchant_admin');
	Route::delete('user/deletemultiple', 'UsersController@deleteMultipleUser')->middleware('is_merchant_admin');
	Route::any('user/update/{id}', 'UsersController@updateUser')->middleware('is_merchant_admin');


	Route::get('collection/statement','CollectionController@transactionStatement');
	//for customerController , for maintaning Customers 
	Route::get('collection/reoccur','ReoccurCustomerController@index')->middleware('is_product:reoccur-collection');
	Route::get('collection/reoccur/customers','ReoccurCustomerController@index')->middleware('is_product:reoccur-collection');
	Route::get('collection/reoccur/customer/ajaxlist', 'ReoccurCustomerController@ajaxUsersList');
	Route::any('collection/reoccur/customer/create','ReoccurCustomerController@create')->middleware('is_product:reoccur-collection');
	Route::delete('collection/reoccur/customer/delete', 'ReoccurCustomerController@deleteCustomer');
	Route::delete('collection/reoccur/customer/deletemultiple', 'ReoccurCustomerController@deleteMultipleCustomers');
	Route::any('collection/reoccur/customer/update/{id}', 'ReoccurCustomerController@updateCustomer');
	Route::any('collection/reoccur/customer/view/{id}', 'ReoccurCustomerController@viewCustomer');

	Route::any('collection/reoccur/customer/transactions/{id}', 'ReoccurCustomerController@transactions');

	Route::any('collection/reoccur/customer/ajax-transactions/{id}', 'ReoccurCustomerController@ajaxTransactions');

	
	Route::get('collection/reoccur/customer/pending-list','ReoccurCustomerController@pendingList')->middleware('is_product:reoccur-collection')->middleware('is_merchant_admin');;
	Route::get('collection/reoccur/customer/ajax-pendinglist', 'ReoccurCustomerController@pendingAjaxUserList');
	Route::post('collection/reoccur/customer/statusupdate', 'ReoccurCustomerController@statusUpdate')->middleware('is_product:reoccur-collection')->middleware('is_merchant_admin');;
	Route::post('collection/reoccur/customer/mul-statusupdate', 'ReoccurCustomerController@mulStatusUpdate')->middleware('is_product:reoccur-collection')->middleware('is_merchant_admin');;

	Route::any('collection/reoccur/customer/pendingupdate/{id}', 'ReoccurCustomerController@updatePendingCustomer')->middleware('is_product:reoccur-collection')->middleware('is_merchant_admin');;
	Route::any('collection/reoccur/customer/pendingview/{id}', 'ReoccurCustomerController@viewPendingCustomer')->middleware('is_product:reoccur-collection')->middleware('is_merchant_admin');;

	Route::get('collection/reoccur/failed','ReoccurCollectionController@failedTransactions');
	Route::get('collection/reoccur/ajax-failed','ReoccurCollectionController@ajaxFailedTranx');

	Route::get('collection/reoccur/disputes','ReoccurCollectionController@disputedTransactions');
	Route::get('collection/reoccur/ajax-disputed','ReoccurCollectionController@ajaxDisputedTranx');

	Route::get('collection/reoccur/reports','ReoccurCollectionController@reports');
	Route::get('collection/reoccur/ajax-reports','ReoccurCollectionController@ajaxReports');
	
	Route::get('collection/reoccur/exportreports','ReoccurCollectionController@exportreport');

	// File upload customers routing
	Route::get('collection/reoccur/customer/upload','ReoccurCustomerController@tempList');
	Route::post('collection/reoccur/customer/import','ReoccurCustomerController@import');
	Route::get('collection/reoccur/customer/samplecsv','ReoccurCustomerController@sampleCsvDownload');
	Route::delete('collection/reoccur/customertmp/delete/{id}', 'ReoccurCustomerController@tempCustomerDelete');
	Route::get('collection/reoccur/customertmp/deletecsv','ReoccurCustomerController@deleteTempList');
	Route::post('collection/reoccur/customertmp/edit','ReoccurCustomerController@editTempCustomer');
	Route::post('collection/reoccur/customertmp/mul-edit','ReoccurCustomerController@editMultipleTempCustomer');


	// Recoorring batches urls

	Route::get('collection/reoccurbatch/approval-list','ReoccurBatchController@index')->middleware('is_product:reoccur-collection');
	Route::get('collection/reoccurbatch/ajax-approval-list','ReoccurBatchController@ajaxApprovalList')->middleware('is_product:reoccur-collection');
	
	Route::get('collection/reoccur/transmission/approval-list/{id}','ReoccurCollectionController@approvalList')->middleware('is_product:reoccur-collection');
	Route::get('collection/reoccur/transmission/ajax-approval-list/{id}','ReoccurCollectionController@ajaxApprovalList')->middleware('is_product:reoccur-collection');


	Route::post('collection/reoccur/transmission/amountupdate','ReoccurCollectionController@updateAmount')->middleware('is_product:reoccur-collection');
	Route::post('collection/reoccur/transmission/statusupdate','ReoccurCollectionController@updateStatus');

	Route::get('collection/reoccurbatch/processed-list','ReoccurBatchController@processedList')->middleware('is_product:reoccur-collection');
	Route::get('collection/reoccurbatch/ajax-processed-list','ReoccurBatchController@ajaxProcessedList')->middleware('is_product:reoccur-collection');
	
	Route::get('collection/reoccur/transmission/processed-list/{id}','ReoccurCollectionController@processedList')->middleware('is_product:reoccur-collection');
	Route::get('collection/reoccur/transmission/ajax-processed-list/{id}','ReoccurCollectionController@ajaxProcessedList')->middleware('is_product:reoccur-collection');

	Route::get('collection/reoccurbatch/submitted-list','ReoccurBatchController@submittedList')->middleware('is_product:reoccur-collection');
	Route::get('collection/reoccurbatch/ajax-submitted-list','ReoccurBatchController@ajaxSubmittedList')->middleware('is_product:reoccur-collection');

	Route::get('collection/reoccur/transmission/submitted-list/{id}','ReoccurCollectionController@submittedList')->middleware('is_product:reoccur-collection');




	// Routes for normal collection
	Route::get('collection/normal','NormalCustomerController@index')->middleware('is_product:normal-collection');
	Route::get('collection/normal/customers','NormalCustomerController@index')->middleware('is_product:normal-collection');
	Route::get('collection/normal/customer/ajaxlist', 'NormalCustomerController@ajaxUsersList');
	Route::any('collection/normal/customer/create','NormalCustomerController@create')->middleware('is_product:normal-collection');
	Route::delete('collection/normal/customer/delete', 'NormalCustomerController@deleteCustomer');
	Route::delete('collection/normal/customer/deletemultiple', 'NormalCustomerController@deleteMultipleCustomers');
	Route::any('collection/normal/customer/update/{id}', 'NormalCustomerController@updateCustomer');
	Route::any('collection/normal/customer/view/{id}', 'NormalCustomerController@viewCustomer');

	Route::any('collection/normal/customer/transactions/{id}', 'NormalCustomerController@transactions');

	Route::any('collection/normal/customer/ajax-transactions/{id}', 'NormalCustomerController@ajaxTransactions');

	Route::any('collection/normal/create-batch', 'NormalCustomerController@createBatch')->middleware('is_product:normal-collection');

	Route::any('collection/normal/update-batch/{id}', 'NormalCustomerController@updateBatch')->middleware('is_product:normal-collection');

	Route::any('collection/normal/listforbatch', 'NormalCustomerController@ajaxlistforbatch')->middleware('is_product:normal-collection');
	Route::post('collection/normal/savebatch', 'NormalCustomerController@savebatch')->middleware('is_product:normal-collection');
	Route::get('collection/normal/samplebatchcsv','NormalCustomerController@samplebatchcsvDownload')->middleware('is_product:normal-collection');
	Route::post('collection/normal/batchimport','NormalCustomerController@batchimport')->middleware('is_product:normal-collection');
	Route::any('collection/normal/savecsvbatch','NormalCustomerController@savecsvbatch')->middleware('is_product:normal-collection');

	


	Route::get('collection/normal/customer/pending-list','NormalCustomerController@pendingList')->middleware('is_product:normal-collection')->middleware('is_merchant_admin');;
	Route::get('collection/normal/customer/ajax-pendinglist', 'NormalCustomerController@pendingAjaxUserList');
	Route::post('collection/normal/customer/statusupdate', 'NormalCustomerController@statusUpdate')->middleware('is_product:normal-collection')->middleware('is_merchant_admin');;
	Route::post('collection/normal/customer/mul-statusupdate', 'NormalCustomerController@mulStatusUpdate')->middleware('is_product:normal-collection')->middleware('is_merchant_admin');;

	Route::any('collection/normal/customer/pendingupdate/{id}', 'NormalCustomerController@updatePendingCustomer');
	Route::any('collection/normal/customer/pendingview/{id}', 'NormalCustomerController@viewPendingCustomer');


	Route::get('collection/normal/failed','NormalCollectionController@failedTransactions');
	Route::get('collection/normal/ajax-failed','NormalCollectionController@ajaxFailedTranx');

	Route::get('collection/normal/disputes','NormalCollectionController@disputedTransactions');
	Route::get('collection/normal/ajax-disputed','NormalCollectionController@ajaxDisputedTranx');

	Route::get('collection/normal/reports','NormalCollectionController@reports');
	Route::get('collection/normal/ajax-reports','NormalCollectionController@ajaxReports');
	
	Route::get('collection/normal/exportreports','NormalCollectionController@exportreport');

	Route::get('collection/normalbatch/pending-transmission/transaction/{id}','NormalCollectionController@approvalList')->middleware('is_product:normal-collection')->middleware('is_merchant_admin');
	Route::get('collection/normal/transmission/ajax-approval-list/{id}','NormalCollectionController@ajaxApprovalList');

	Route::post('collection/normal/transmission/amountupdate','NormalCollectionController@updateAmount')->middleware('is_product:normal-collection')->middleware('is_merchant_admin');
	Route::post('collection/normal/transmission/statusupdate','NormalCollectionController@updateStatus');
	Route::get('collection/normalbatch/processed-transmission/transaction/{id}','NormalCollectionController@processedList')->middleware('is_product:normal-collection')->middleware('is_merchant_admin');
	Route::get('collection/normal/transmission/ajax-processed-list/{id}','NormalCollectionController@ajaxProcessedList');
	Route::get('collection/normalbatch/queued-transmission/transaction/{id}','NormalCollectionController@submittedList')->middleware('is_product:normal-collection')->middleware('is_merchant_admin');

	Route::get('collection/normalbatch/approved-transmission/transaction/{id}','NormalCollectionController@approvedList')->middleware('is_product:normal-collection')->middleware('is_merchant_admin');


	



	
	// File upload normal customers routing
	Route::get('collection/normal/customer/upload','NormalCustomerController@tempList')->middleware('is_product:normal-collection')->middleware('is_merchant_admin');;
	Route::post('collection/normal/customer/import','NormalCustomerController@import')->middleware('is_product:normal-collection')->middleware('is_merchant_admin');;
	Route::get('collection/normal/customer/samplecsv','NormalCustomerController@sampleCsvDownload')->middleware('is_product:normal-collection')->middleware('is_merchant_admin');;
	Route::delete('collection/normal/customertmp/delete/{id}', 'NormalCustomerController@tempCustomerDelete')->middleware('is_product:normal-collection')->middleware('is_merchant_admin');;
	Route::get('collection/normal/customertmp/deletecsv','NormalCustomerController@deleteTempList')->middleware('is_product:normal-collection')->middleware('is_merchant_admin');;
	Route::post('collection/normal/customertmp/edit','NormalCustomerController@editTempCustomer')->middleware('is_product:normal-collection')->middleware('is_merchant_admin');;
	Route::post('collection/normal/customertmp/mul-edit','NormalCustomerController@editMultipleTempCustomer')->middleware('is_product:normal-collection')->middleware('is_merchant_admin');;


	// Recoorring batches urls

	Route::get('collection/normalbatch/pending','NormalBatchController@index');
	Route::get('collection/normalbatch/ajax-approval-list','NormalBatchController@ajaxApprovalList');
	Route::post('collection/normalbatch/statusupdate','NormalBatchController@statusUpdate');
	Route::get('collection/normalbatch/processed-list','NormalBatchController@processedList');
	Route::get('collection/normalbatch/ajax-processed-list','NormalBatchController@ajaxProcessedList');
	

	Route::get('collection/normalbatch/queued','NormalBatchController@submittedList');
	Route::get('collection/normalbatch/ajax-submitted-list','NormalBatchController@ajaxSubmittedList');

	Route::get('collection/normalbatch/approved','NormalBatchController@approvedList');
	Route::get('collection/normalbatch/ajax-approved-list','NormalBatchController@ajaxApprovedList');




	Route::get('collection','ReoccurCustomerController@index')->middleware('is_product:reoccur-collection');

	Route::get('avs/history/realtime','AvsCustomerController@index')->middleware('is_product:avs');
	Route::get('avs/ajax-realtime-list', 'AvsCustomerController@ajaxRealtimeAvsList');
	Route::get('avs/history/batch','AvsCustomerController@batch')->middleware('is_product:avs');
	Route::get('avs/ajax-batch-list', 'AvsCustomerController@ajaxAvsBatchList');
	Route::get('avs/result/{avsId}', 'AvsCustomerController@showResult');
	
	Route::get('avs/history/batch/customer-list/{batchId}','AvsCustomerController@batchCustomerList')->middleware('is_product:avs');
	Route::get('avs/ajax-batch-customer-list/{batchId}', 'AvsCustomerController@ajaxAvsBatchCustomerList');
	
	Route::get('avs/create-realtime','AvsCustomerController@createAvs')->middleware('is_product:avs');
	Route::get('avs/create-batch','AvsCustomerController@tempList')->middleware('is_product:avs');
	Route::post('avs/import','AvsCustomerController@import')->middleware('is_product:avs');
	Route::post('avs/update-temp','AvsCustomerController@updateTempAvs')->middleware('is_product:avs');
	Route::post('avs/update-mul-temp','AvsCustomerController@editMultipleTempAvs')->middleware('is_product:avs');
	Route::delete('avs/delete-temp/{id}','AvsCustomerController@deleteTempAvs');
	Route::get('avs/delete-temp-list','AvsCustomerController@deleteTempList')->middleware('is_product:avs');
	Route::get('avs/samplecsv','AvsCustomerController@sampleCsvDownload')->middleware('is_product:avs');
	Route::post('avs/save-request','AvsCustomerController@saveAvsRequest')->middleware('is_product:avs');
	Route::post('avs/ajax-recheck-avs','AvsCustomerController@recheckAjaxAvs')->middleware('is_product:avs');
	Route::get('avs/history','AvsCustomerController@index')->middleware('is_product:avs');
	Route::get('avs','AvsCustomerController@index')->middleware('is_product:avs');
	Route::get('error-codes','ErrorCodesController@index');


	// Route::get('customers','CustomerController@index');
	// Route::any('customer/create','CustomerController@create');
	// Route::get('customers/listing/ajax', 'CustomerController@ajaxUsersList');
	// Route::delete('customers/delete', 'CustomerController@deleteCustomer');
	// Route::delete('customers/deletemultiple', 'CustomerController@deleteMultipleCustomers');
	// Route::any('customers/update/{id}', 'CustomerController@updateCustomer');
	// Route::post('customer/update/status','CustomerController@updateStatus');
	// Route::post('customers/bulk/approve','CustomerController@approveAll');
	

	// Temporary Customers Routing 
	// Route::get('customers/samplecsv','CustomerController@samplecsvDownload');
	// Route::post('customers/import','CustomerController@import');
	// Route::get('customers/temp/list','CustomerController@tempList');
	// Route::delete('temp/customers/delete/{id}', 'CustomerController@tempCustomerDelete');
	// Route::post('edit/temp/customer','CustomerController@editTempCustomer');
	// Route::post('edit/multiple/temp/customer','CustomerController@editMultipleTempCustomer');
 //    Route::get('customers/delete/tempcsv','CustomerController@deleteTempList');

	//for customertransacrtionController , for maintaning Customers transactions
	// Route::get('customers/transaction','CustomerTransationController@index');
	// Route::get('customers/transaction/listing/ajax', 'CustomerTransationController@ajaxList');
	// Route::post('customers/transaction/update', 'CustomerTransationController@updateAmount');
	// Route::post('customers/transaction/bulk/approve','CustomerTransationController@approveMultiple');
	// Route::post('customers/transaction/approve/all','CustomerTransationController@approveAll');

	Route::get('collection/history/approved/batches','CollectionHistoryController@approvedBatchList');
	Route::get('collection/history/approved/batches/ajax', 'CollectionHistoryController@approvedBatchAjaxList');
	Route::get('collection/history/transactions/{id}', 'CollectionHistoryController@viewTransactions');

	// Collection Routing
	// Route::get('collection/samplecsv','CollectionController@samplecsvDownload');
	// Route::post('collection/import','CollectionController@import');
	// Route::get('collection/list','CollectionController@collectionList');
	// Route::delete('collection/delete/{id}', 'CollectionController@tempCustomerDelete');
	// Route::post('collection/edit','CollectionController@editTempCustomerCollection');
	// Route::post('collection/edit/multiple','CollectionController@editMultipleTempCustomerCollection');
	// Route::get('collection/delete/tempcsv','CollectionController@deleteTempList');
	// Route::get('collection/failed','CollectionController@failedTransactions');

});

Route::get('getBank','HomeController@getBank');