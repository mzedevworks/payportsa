<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Support\Facades\Password;
use App\User;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $this->validateEmail($request);
        $user = User::where('email',$request->email)->first();
        if(isset($user) && $user!=''){
            //if($user->role_id==1){
                // We will send the password reset link to this user. Once we have attempted
                // to send the link, we will examine the response then see the message we
                // need to show to the user. Finally, we'll send out a proper response.
                $response = $this->broker()->sendResetLink(
                    $request->only('email')
                );

                return $response == Password::RESET_LINK_SENT
                            ? $this->sendResetLinkResponse($request, $response)
                            : $this->sendResetLinkFailedResponse($request, $response);
            // }else{
            //     return back()->with(['status' =>'Admin will provide your password reset link','class' => 'success']);
            // }
        }else{
            return back()->with(['status' =>'Sorry, We can not find user with this email','class' => 'danger']);
        }
    }

    protected function sendResetLinkResponse(Request $request, $response)
    {
        return back()->with(['status' =>  trans($response),'class'=>'success']);
    }
}
