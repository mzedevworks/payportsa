<?php

namespace App\Http\Controllers\Merchant;

use App\User;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\DatatableHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

use App\Model\{Firm,BankDetails,Role,CompanyInformation,Employees};


class UsersController extends Controller
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
        
        $pagename  = "Users";
        //$employees = Employees::where('merchant_id',auth()->user()->id)->where('is_deleted',0)->get();
        return view('merchant.users.list',compact('pagename'));
    }

    public function ajaxUsersList(Request $request){
        //print_r($request['columns']);
        // Table's primary key
        $primaryKey = 'id';
         
        // Array of database columns which should be read and sent back to DataTables.
        // The `db` parameter represents the column name in the database, while the `dt`
        // parameter represents the DataTables column identifier. In this case simple
        // indexes
        $columns = array(
            array( 'dt' => 0,
                'formatter' => function( $d, $row ) {
                    //return encrypt($row['id']);
                    return $row['id'];
                }
            ),
            array( 'db' => 'first_name', 'dt' => 1 ),
            array( 'db' => 'last_name',  'dt' => 2 ),
            array( 'db' => 'email',   'dt' => 3 ),
            array( 'db' => 'contact_number',     'dt' => 4 ),
            array(
                'dbAlias' => 'roles',
                'db'        => 'name',
                'feildAlias'=>'role_name',
                'dt'        => 5
            ),
            array(
                'db'        => 'status',
                'dt'        => 6,
                'formatter' => function( $d, $row ) {
                    return Helper::getUserStatusTitle($d);
                }
            ),
            array(
                
                'dt'        => 7,
                'formatter' => function( $d, $row ) {
                    // $str='<div class="float-left">
                    //         <a href="'.url('merchant/user/update/'.encrypt($row["id"])).'" class="btn bg-transparent"><i class="lni-pencil" aria-hidden="true"></i></a>
                            
                    //         <form method="POST" action="'.url('merchant/user/delete/'.encrypt($row["id"])).'" class="btn bg-transparent">
                    //             '.csrf_field().''.
                    //             method_field('DELETE').'
                    //                 <button type="submit" onclick="return confirm(\'Are you sure to delete\')" class="btn bg-transparent"><i class="lni-trash" aria-hidden="true"></i></button>
                    //         </form>
                    //     </div>';
                    // return $str;
                    return encrypt($row['id']);
                }
            )
        );
        $firmId=Auth()->user()->firm_id;
        $bindings=[3,4,$firmId,1];
        $whereConditions="role_id in (?,?) and firm_id=? and (is_deleted!=? or is_deleted is null)";
        $totalCount = DB::table('users')
                ->selectRaw('count('.$primaryKey.') totCount')
                ->whereRaw($whereConditions, $bindings)
                ->get()
                ->toArray();



        $dtWhere=DatatableHelper::filter ( $request, $columns,$bindings);
        if($dtWhere!==""){
            $whereConditions.=" and ".$dtWhere;
        }
        $orderBy=DatatableHelper::order ( $request, $columns );
        $limit=DatatableHelper::limit ( $request, $columns );

        
        $data = DB::table('users')
                ->selectRaw('users.*,roles.name as role_name')
                ->leftJoin('roles', function ($join) {
                    $join->on('users.role_id', '=', 'roles.id');
                         //->where('contacts.user_id', '>', 5);
                })
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
        
        $totalFilteredCount = DB::table('users')
                ->selectRaw('count(users.'.$primaryKey.') totCount,roles.name as role_name, users.'.$primaryKey)
                ->leftJoin('roles', function ($join) {
                    $join->on('users.role_id', '=', 'roles.id');
                         //->where('contacts.user_id', '>', 5);
                })
                ->whereRaw($whereConditions, $bindings)
                ->get()
                ->toArray();
                
        
        echo json_encode(
            array(
                    "draw" => isset ( $request['draw'] ) ?
                        intval( $request['draw'] ) :
                        0,
                    "recordsTotal"=> intval( $totalCount[0]->totCount ),
                    "recordsFiltered" => intval( $totalFilteredCount[0]->totCount ),
                    "data" => DatatableHelper::data_output( $columns, $data )
                )
        );
        die();
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function createUser(Request $request)
    {
        $pagename = "Add User";
        $roles      = Role::whereIn('id', [3, 4])->get();
        if($request->isMethod('post')){
            $validator = Validator::make($request->all(), [
                "first_name"           => 'required', 
                "last_name"            => 'required', 
                "email"                => 'required|email|unique:users', 
                "contact_number"       => 'required|regex:/[0-9]+/', 
                "role_id"              => 'required|regex:/[0-9]+/|exists:roles,id',
                "status"               => 'required|in:0,1' 
            ]);
            if ($validator->fails()){
                return Redirect::to('merchant/users/create')->withErrors($validator)->withInput();
            }

            $password               = str_random(8);
            $hashed_random_password = Hash::make($password);
        
            $user = new User();
            
            $user->first_name           =  $request->first_name;
            $user->last_name            =  $request->last_name; 
            $user->email                =  $request->email; 
            $user->contact_number       =  $request->contact_number;
            $user->status               =  $request->status;
            $user->role_id              =  $request->role_id;
            $user->password             =  $hashed_random_password;
            $user->firm_id             =  Auth()->user()->firm_id;
            
            $user->save();

            $data = [
                'template'  => 'payportUserInvite',
                'password'  => $password,
                'subject'   => "Your account is created.",
                'to'=>$user
            ];         
            
            if($user->save()){
                $status = Helper::sendInviteMail($data);
                if($status===true){
                    Session::flash('status','User created successfully');
                    Session::flash('class','success');

                }else{
                    Session::flash('status','User Added successfully but problem in sending an email');
                    Session::flash('class','danger');
                }
            }else{
                 Session::flash('status','Unable to create User! Please try again later');
                 Session::flash('class','danger');
            }
            return redirect('merchant/users');
        }

        return view('merchant.users.createUser',compact('pagename','roles'));
    }

    public function updateUser(Request $request){
        $userId=decrypt($request->id);
        $pagename = "Update User";

        $userStatus=config('constants.userStatus');
        $roles = Role::whereIn('id', [3, 4])->get();

        if($userId){
            $userRes = User::where('id',$userId)->whereIn('role_id', [3,4])->first();

            if($request->isMethod('post')){
                $validator = Validator::make($request->all(), [
                    "first_name"           => 'required', 
                    "last_name"            => 'required', 
                    "email"                => [
                                                'required',
                                                'email',
                                                Rule::unique('users')->ignore($userRes->id)
                                            ],
                    "contact_number"       => 'required|numeric', 
                    "role_id"              => [
                                                'required',
                                                'regex:/[0-9]+/',
                                                Rule::exists('roles','id')->where(function ($query) {
                                                    return $query->whereIn('id', [3,4]);
                                                })
                                              ],
                    "status"               => 'required|regex:/[0-9]+/' 
                ]);
                if ($validator->fails())
                {
                    return Redirect::to('merchant/user/update/'.encrypt($userId))->withErrors($validator)->withInput();;
                }

                $userRes->first_name           =  $request->first_name;
                $userRes->last_name            =  $request->last_name; 
                $userRes->email                =  $request->email; 
                $userRes->contact_number       =  $request->contact_number;
                $userRes->status               =  $request->status;
                $userRes->role_id              =  $request->role_id;

                if($userRes->save()){
                    Session::flash('status','User Updated successfully');
                    Session::flash('class','success');
                }else{
                     Session::flash('status','Unable to Update User! Please try again later');
                     Session::flash('class','danger');
                }
                return redirect('merchant/users');
            }
        }else{
            Session::flash('status','Problem in fetching the record');
            Session::flash('class','danger');
            return redirect('merchant/users');
        }
        return view('merchant.users.userUpdate',compact('roles','pagename','userStatus','userRes'));
    }

    public function deleteUser(Request $request){
        $requestStatus=['status'=>402,'message'=>'Problem in deleting the record',"type"=>"danger"];
        if($request->isMethod('delete')){
            $userId=decrypt($request->userId);
                      
            $userRes = User::where('id',$userId)->whereIn('role_id', [3,4])->where('is_primary','!=',1)->first();
            if($userRes){
                $userRes->is_deleted = 1;
                $userRes->deleted_by = auth()->user()->id;
                $userRes->deleted_at = date("Y-m-d H:i:s");
                
                if ($userRes->save()) {
                    $requestStatus=['status'=>201,'message'=>'User Deleted Successfully',"type"=>"success"];
                    
                }    
            }
            
        }
        echo json_encode($requestStatus);
        //return redirect('merchant/users');
    }

    public function deleteMultipleUser(Request $request){
        if($request->isMethod('delete')){
            $i=0;
            foreach ($request->toDelete as $key => $eachUser) {
                //$userId=decrypt($eachUser);
                $userId=$eachUser;
                      
                $userRes = User::where('id',$userId)->whereIn('role_id', [3,4])->where('is_primary','!=',1)->first();
                if($userRes){
                    $userRes->is_deleted = 1;
                    $userRes->deleted_by = auth()->user()->id;
                    $userRes->deleted_at = date("Y-m-d H:i:s");
                    
                    if ($userRes->save()) {
                        $i++;
                    }    
                }
            }

            Session::flash('status',$i.' Users Deleted Successfully');
            Session::flash('class','success');
            return redirect('merchant/users');
            
        }
    }
}
