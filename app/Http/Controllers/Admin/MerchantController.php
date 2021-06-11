<?php

namespace App\Http\Controllers\Admin;

use Hash;
use App\User;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Model\{Firm,BankDetails,Role,CompanyInformation};
use App\Helpers\DatatableHelper;
use Illuminate\Support\Facades\DB;

class MerchantController extends Controller
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
        $merchants = User::whereIn('role_id',[3,4])->where('is_deleted',0)->get();
        return view('admin.merchants.list',compact('merchants','pagename'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if($request->isMethod('post')){
            $pagename     = "Create Merchant";
            $emailVali    = 'required|email|unique:users';
            $passwordVali = 'required|string|min:6|confirmed';
            $validator    = $this->validation($request,$emailVali,$passwordVali);
            
            if ($validator->fails()){
                return redirect()->back()->withErrors($validator)->withInput();;
            }

            $user = new User();
            $user = $this->userSave($request,$user);
            $user->password             =  Hash::make($request->password);
            
            if($user->save()){
                $fromEmail     = CompanyInformation::findOrFail(1)->email;
                $fromName      = CompanyInformation::findOrFail(1)->company_name;

                $data = [
                    'template'           => 'welcome',
                    'subject'            => "Merchant account is created.",
                    'to'                 => $user,
                    'from_email'         => $fromEmail,
                    'from_name'          => $fromName
                ]; 
                $status = Helper::sendInviteMail($data);
                if($status===true){
                    Session::flash('status','Merchant created successfully');
                    Session::flash('class','success');

                }else{
                    Session::flash('status','Merchant Added successfully but problem in sending an email');
                    Session::flash('class','danger');
                }
            }else{
                 Session::flash('status','Unable to create User! Please try again later');
                 Session::flash('class','danger');
            }
            return redirect('admin/merchants');
        }else{
            $pagename = "Add User";
            $firms    = Firm::where('is_deleted','!=',1)->get();
            $roles    = Role::whereIn('id', [3,4])->get();
            return view('admin.merchants.add',compact('pagename','firms','roles'));
        }
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
            array(
                'dbAlias'    => 'firms',
                'db'         => 'business_name',
                'feildAlias' => 'business_name',
                'dt'         => 3
            ),
            array(
                'dbAlias'   => 'roles',
                'db'        => 'name',
                'feildAlias'=>'role_name',
                'dt'        => 4
            ),
            array( 'db' => 'email',  'dt' => 5),
            array( 'db' => 'contact_number',  'dt' => 6),
            array(
                'db'        => 'status',
                'dt'        => 7,
                'formatter' => function( $d, $row ) {
                    return Helper::getUserStatusTitle($d);
                }
            ),
            array(
                
                'dt'        => 8,
                'formatter' => function( $d, $row ) {
                    return encrypt($row['id']);
                }
            )
        );

        $bindings=[3,4,1,1];
        $whereConditions ="role_id in (?,?) and (users.is_deleted!=? or users.is_deleted is null) and (firms.is_deleted!=? or firms.is_deleted is null)";
        
        $totalCount = DB::table('users')
                ->selectRaw('count(users.'.$primaryKey.') totCount')
                ->leftJoin('firms', function ($join) {
                    $join->on('firms.id', '=', 'users.firm_id');
                })
                ->whereRaw($whereConditions, $bindings)
                ->get()
                ->toArray();

        //$whereConditions = "role_id in (?,?) and (users.is_deleted!=? or users.is_deleted is null)";

        $dtWhere=DatatableHelper::filter ( $request, $columns,$bindings);
        if($dtWhere!==""){
            $whereConditions.=" and ".$dtWhere;
        }
        $orderBy=DatatableHelper::order ( $request, $columns );
        $limit=DatatableHelper::limit ( $request, $columns );

        
        
        $data = DB::table('users')
                ->selectRaw('users.*,roles.name as role_name,firms.business_name')
                ->leftJoin('roles', function ($join) {
                    $join->on('users.role_id', '=', 'roles.id');
                })
                ->leftJoin('firms', function ($join) {
                    $join->on('firms.id', '=', 'users.firm_id');
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
                })
                ->leftJoin('firms', function ($join) {
                    $join->on('firms.id', '=', 'users.firm_id');
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if($request->isMethod('post')){
            $pagename = "Update User";
            $user = User::find(decrypt($id));
            
            $emailVali    = 'required|email|unique:users,email,'.$user->email.',email';
            $passwordVali = '';
            $validator    = $this->validation($request,$emailVali,$passwordVali);
            if ($validator->fails()){
                return redirect()->back()->withErrors($validator)->withInput();;
            }
            $user = $this->userSave($request,$user);
            if($user->save()){
                Session::flash('status','Merchant Updated successfully');
                Session::flash('class','success');
            }else{
                Session::flash('status','Unable to create User! Please try again later');
                Session::flash('class','danger');
            }
            return redirect('admin/merchants');
        }else{
            $merchant = User::find(decrypt($id));
            $roles = Role::whereIn('id', [3,4])->get();
            $pagename = "Update User";
            return view('admin.merchants.userUpdate',compact('pagename','roles','merchant'));
        }
    }

    public function deleteUser(Request $request){
        $requestStatus=['status'=>402,'message'=>'Problem in deleting the record',"type"=>"danger"];
        if($request->isMethod('delete')){

            $userId=decrypt($request->userId);
                      
            $userRes = User::where('id',$userId)->first();
            if($userRes){
                if($userRes->is_primary==1){
                    $requestStatus=['status'=>201,'message'=>'You cannot delete primary user',"type"=>"danger"];
                }else{
                    $userRes->is_deleted = 1;
                    $userRes->deleted_by = auth()->user()->id;
                    $userRes->deleted_at = date("Y-m-d H:i:s");
                    
                    if ($userRes->save()) {
                        $requestStatus=['status'=>201,'message'=>'User Deleted Successfully',"type"=>"success"];
                    }    
                }
                
            }
            
        }
        echo json_encode($requestStatus);
    }

    public function deleteMultipleUsers(Request $request){
        if($request->isMethod('delete')){

            $i=0;
            foreach ($request->toDelete as $key => $eachUser) {
                
                $userId=$eachUser;
                $userRes = User::where('id',$userId)->first();

                if($userRes && $userRes->is_primary!=1){

                    $userRes->is_deleted = 1;
                    $userRes->deleted_by = auth()->user()->id;
                    $userRes->deleted_at = date("Y-m-d H:i:s");
                    
                    if ($userRes->save()) {
                        $i++;
                    }    
                }
            }

            if($i>0){
                Session::flash('status',$i.' Users Deleted Successfully');
                Session::flash('class','success');
            }
            
            return redirect('admin/merchants');
            
        }
    }

    public function loginAsMerchant(Request $request){

        if(auth()->user()->role_id==1){
            Session::put('admin_id', Auth::id());
            $id  = decrypt($request->id);
            $user = User::find($id);
            if(isset($user) && $user!=''){
                Auth::logout();
                $user = Auth::loginUsingId($id);
            }else{
               Session::flash('status','Sorry Your request Can not be processed');
               Session::flash('class','danger');
            }
            return redirect('home');
        }else{
            Session::flash('status','Sorry Your request Can not be processed');
            Session::flash('class','danger'); 
            return redirect()->back();
        }
    }

    private function validation($request,$emailVali,$passwordVali){
            $validator = \Validator::make($request->all(), [
                "first_name"           => 'required', 
                "last_name"            => 'required', 
                "email"                => $emailVali,
                "contact_number"       => 'required|regex:/[0-9]+/|digits:10', 
                "role_id"              => [
                                            'required',
                                            'regex:/[0-9]+/',
                                            Rule::exists('roles','id')->where(function ($query) {
                                                return $query->whereIn('id', [3,4]);
                                            })
                                          ],
                "status"               => 'required|in:0,1' ,
                "firm_id"              => [
                                                'required',
                                                'regex:/[0-9]+/',
                                                Rule::exists('firms','id')->where(function ($query) {
                                                    return $query->where('is_deleted', 0);
                                                })
                                            ],
                "password"             =>  $passwordVali
            ],[
                "firm_id.required"     => 'Please select firm',
                "role_id.required"     => 'Please select role'
            ]);
            return $validator;
    }

    private function userSave($request,$user){

            $user->first_name           =  $request->first_name;
            $user->last_name            =  $request->last_name; 
            $user->email                =  $request->email; 
            $user->contact_number       =  $request->contact_number;
            $user->status               =  $request->status;
            $user->role_id              =  $request->role_id;
            $user->firm_id              =  $request->firm_id;   
            return $user;
    }
}
