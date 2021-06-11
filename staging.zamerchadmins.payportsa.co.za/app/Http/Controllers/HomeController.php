<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use App\Model\{BankDetails,Customer,CustomerTransaction,Collections,Sequence,Batch,CompanyBankInfo,TransmissionTable,PublicHolidays,TransmissionRecords,TransRepliedErrors,TransmissionRepliedErrors};
use App\Helpers\Helper;
use Illuminate\Support\Facades\Mail;
use phpseclib\Net\SFTP;

class HomeController extends Controller
{

    static $usersequencenumber = '';
    public $transactions=null;
    public $transmissionRecord=null;
    public $lastContraSquenceNumber="000000";
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth')->except(['collection','downloadreply','readdata']);
        
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        
        if(auth()->user()->role_id==1){
              return redirect('admin/dashboard');
        }else{
            if(auth()->user()->role_id==3 || auth()->user()->role_id==4){
              return redirect('merchant/dashboard');
            }
        }
    }

    public function changePassword(Request $request){
        if($request->isMethod('get')){
               $pagename = "Change password";
               return view('password-reset',compact('pagename'));
        }else{
            $validator = \Validator::make($request->all(), [
                "old_password"         => 'required',  
                "password"             => 'required|string|min:6|confirmed',
                "password_confirmation" => 'required|string|min:6'
            ]);
            if ($validator->fails()){
                return redirect()->back()->withErrors($validator)->withInput();;
            }

            if(Hash::check($request->old_password, auth()->user()->password))
            {           
                $user           = User::find(auth()->user()->id);
                $user->password = Hash::make($request->password);
                $user->is_passsword_changed=1;
                $user->save();
                Session::flash('status','Password Updated successfully.');
                Session::flash ('class', 'success');
                return redirect()->back();
            }
            else
            {   
                Session::flash('status', 'old password does not match');
                Session::flash('class', 'danger');
                return redirect()->back();
            }
        }
    }

    public function getBank(Request $request){
       $id = $request->bank_id;
       $bankDetails = BankDetails::find($id);
       if(isset($bankDetails)){
            return $bankDetails;
       }else{
            return 0;
       }
    }

    public function downloadreply(){
        $remoreDir="/transferzone/PayportSA/outgoing/User.php";
        $localDir=public_path("files/collections/incoming");
        $sftp=Helper::getSftp();
        if($sftp){
            
            if($sftp->get($remoreDir, $localDir.'/User.php')){
                echo " ho gya";
                //$sftp->delete($remoreDir, false); //delete file from FTP
            }else{
                echo "ni ho paya";
            }
        }else{
            echo "not-connected";
        }
    }

    function readdata(){
        echo phpinfo();
    }


}
