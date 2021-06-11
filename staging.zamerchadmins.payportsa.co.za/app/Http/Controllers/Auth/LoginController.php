<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use App\Model\{UserLoginLog};
class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();

        return $this->loggedOut($request) ?: redirect('/login');
    }

    public function login(Request $request)
    {
        
        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }

        if ($this->guard()->validate($this->credentials($request))) {
            $user = $this->guard()->getLastAttempted();
            

            //check that if user is deleted or status is in-active
            if($user->is_deleted===1 || $user->status===0 ){
              return redirect('login')->withErrors('You are currently not active to login');
            }

            //check that if user is deleted or status is in-active
            if(!is_null($user->firm_id) && ($user->firm->is_deleted===1 || $user->firm->status===0 )){
              return redirect('login')->withErrors('Firm with whome you are linked is de-activated or deleted.');
            }

            if(!is_null($user->firm_id) && $user->firm->is_deleted){

            }
        }
        if ($this->attemptLogin($request)) {
            
            //Auth::logoutOtherDevices($request->password);
            
            return $this->sendLoginResponse($request);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

        function authenticated(Request $request, $user)
        {
            
                $agent = new Agent();
                $userLoginLog=new UserLoginLog();
                
                $userLoginLog->created_at=$user->last_login_at = date('Y-m-d H:i:s');
                $userLoginLog->user_ip=$user->last_login_ip = \Request::ip();// request()->ip();
                //$request->getClientIp();
                $user->save();

                $userLoginLog->browser_agent_str=$request->server('HTTP_USER_AGENT');
                
                $browser = $agent->browser();
                $userLoginLog->browser_name=$browser;
                
                $userLoginLog->browser_version = $agent->version($browser);
               
                $userLoginLog->platform_name = $agent->platform();
                $userLoginLog->platform_version = $agent->version($userLoginLog->platform_name);
                $deviceType="";
                if($agent->isDesktop()){
                    $deviceType="desktop";
                }elseif ($agent->isPhone()) {
                    $deviceType="phone";
                }elseif ($agent->isRobot()) {
                    $deviceType="robot";
                }
                $userLoginLog->user_id=$user->id;
                $userLoginLog->device_type=$deviceType;
                $userLoginLog->device_name = $agent->device();
                $userLoginLog->save();


            
        }
}
