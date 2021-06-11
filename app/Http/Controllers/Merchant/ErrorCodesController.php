<?php

namespace App\Http\Controllers\Merchant;

use App\Employee;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use Response;
use Illuminate\Validation\Rule;
use App\Helpers\DatatableHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use App\Model\{Firm};

class ErrorCodesController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $pagename  = "Error Codes";
        $errorCodes=[
            [
                'code'=>'002', 
                'reason'=>'Not provided for',
                'desc'=>'There were insufficient funds in this account. Please contact the customer to make arrangements for alternative payment or arrange to process a double payment on the next batch run.'
            ],
            [
                'code'=>'003', 
                'reason'=>'Debits not allowed to this account',
                'desc'=>'The customers account is a special savings account and cannot be debited. Kindly request customer to provide alternative banking details account details or change the account to a Transmission Account.'
            ],
            [
                'code'=>'004', 
                'reason'=>'Payment Stopped (by a/c holder)',
                'desc'=>'Your customer has requested that payment be stopped and the debit order be reversed. Please contact your customer to query the Stop Payment. No further debit order instruction will run against this account until Stop Payment has been lifted.'
            ],
            [
                'code'=>'006', 
                'reason'=>'Account Frozen (as in divorce, etc)',
                'desc'=>'Your customers account has been frozen due to legal proceedings Please contact your customer to obtain alternative banking details. No further debit orders will be run against this account until legal proceedings have been completed.'
            ],
            [
                'code'=>'008', 
                'reason'=>'Account in sequestration (private individual)',
                'desc'=>'Your customers account has been frozen due to legal proceedings Please contact your customer to obtain alternative banking details as no further debit orders will be run against this account.'
            ],
            [
                'code'=>'010', 
                'reason'=>'Account in liquidation (company)',
                'desc'=>'Your customers account has been frozen due to legal proceedings Please contact your customer to obtain alternative banking details as no further debit orders will be run against this account.'
            ],
            [
                'code'=>'012', 
                'reason'=>'Account closed (with no forwarding details)',
                'desc'=>'Your customers account has been closed. Kindly contact your customer to obtain alternative banking details as no further debit orders can be run against this account.'
            ],
            [
                'code'=>'014', 
                'reason'=>'Account transferred (within banking group)',
                'desc'=>'Account transferred (within banking group)'
            ],
            [
                'code'=>'145', 
                'reason'=>'Account failed final validation',
                'desc'=>'This is a credit payment error when an account fails the final validation but still exists in the system with the incorrect details.'
            ],
            [
                'code'=>'016', 
                'reason'=>'Account transferred (to another banking group)',
                'desc'=>'Account transferred (to another banking group)'
            ],
            [
                'code'=>'018', 
                'reason'=>'Account holder deceased',
                'desc'=>'You customers account has been closed as the account holder is deceased. Please obtain alternative banking details if the debit is to continue or contact the customers attorneys for settlement'
            ],
            [
                'code'=>'022', 
                'reason'=>'Account effects not cleared',
                'desc'=>'Your customers account had funds but these were not cleared in time for the debit order to be processed. Please contact you customer to arrange manual payment or confirm when your debit order can be resubmitted'
            ],
            [
                'code'=>'026', 
                'reason'=>'No such account',
                'desc'=>'Your customers account may be invalid or has been closed. Please contact your customer to obtain alternative banking details.'
            ],
            [
                'code'=>'028', 
                'reason'=>'Recall/Withdrawal',
                'desc'=>'Your customer has requested a withdrawal of the debit order entry. Please contact your customer to clear up the dispute and re-submit if necessary.'
            ],
            [
                'code'=>'030', 
                'reason'=>'No authority to debit / credit',
                'desc'=>'Your customer has stopped payment of the debit order, allegedly due to you not having authority to debit the account. Please contact you r customer to resolve the dispute as no further debit orders will be submitted on this account until stop payment is lifted.'
            ],
            [
                'code'=>'032', 
                'reason'=>"Debit in contravention of payer's authority",
                'desc'=>'Your customer has stopped payment of the debit order which allegedly is in contravention of the agreement entered into. Please contact your customer to resolve the dispute. No further debit orders can be submitted on this account until stop payment is lifted.'
            ],
            [
                'code'=>'034', 
                'reason'=>'Authorisation cancelled',
                'desc'=>'Your customer has stopped payment on the debit order, and cancelled the authorisation. Please contact your customer to resolve the dispute as no further debit orders will be submitted on this account until stop payment is lifted.'
            ],
            [
                'code'=>'036', 
                'reason'=>'Previously stopped via stop payment advice',
                'desc'=>'Your customer has previously stopped payment of the debit order due to a dispute. Please contact your customer to resolve the dispute as no further debit orders will be submitted on this account until stop payment is lifted.'
            ],
            [
                'code'=>'050', 
                'reason'=>'Account Number Invalid',
                'desc'=>'Your customers account is invalid. Please contact your customer to obtain alternative banking details.'
            ],
            [
                'code'=>'051', 
                'reason'=>'Bank Recall',
                'desc'=>'The bank has requested a recall on this debit order transaction entry. Please contact your customer to arrange settlement and resubmit if necessary.'
            ],
            [
                'code'=>'056', 
                'reason'=>'Not FICA compliant',
                'desc'=>'This account is not FICA compliant and the bank will not allow any further transactions to this account until your customer complies to the requirements. Please make alternative arrangements for payment with your client.'
                ]
        ];
        return view('merchant.error-codes.list',compact('pagename','errorCodes'));
    }

    
}
