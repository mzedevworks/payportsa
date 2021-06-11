<?php

namespace App\Http\Controllers\Admin;

use App\User;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Helpers\DatatableHelper;
use App\Http\Controllers\Controller;
use App\Model\{PublicHolidays};
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class HolidayController extends Controller
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
        $pagename = "Holidays";
        return view('admin.holidays.list',compact('pagename'));
    }

    public function ajaxHolidayList(Request $request){
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
            array(
                'dbAlias'   => 'public_holidays',
                'db'        => 'holiday_event',
                'dt'        => 1
            ),
            array(
                'dbAlias'   => 'public_holidays',
                'db'        => 'holiday_date',
                'dt'        => 2,
                'formatter' => function( $d, $row ) {
                   return date('d-m-Y',strtotime($row['holiday_date']));
                }
            ),
            array(
                'dt'        => 3,
                'formatter' => function( $d, $row ) {
                   return encrypt($row['id']);
                }
            )
        );
        
        $bindings=[];

        $whereConditions="(1=1)";
        $totalCount = DB::table('public_holidays')
                ->selectRaw('count('.$primaryKey.') totCount')
                ->whereRaw($whereConditions, $bindings)
                ->get()
                ->toArray();

        //$whereConditions.= " and users.is_primary=1";

        $dtWhere=DatatableHelper::filter ( $request, $columns,$bindings);
        if($dtWhere!==""){
            $whereConditions.=" and ".$dtWhere;
        }
        $orderBy=DatatableHelper::order ( $request, $columns );
        $limit=DatatableHelper::limit ( $request, $columns );

       
        $data = DB::table('public_holidays')
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
        
        $totalFilteredCount = DB::table('public_holidays')
                ->selectRaw('count(public_holidays.'.$primaryKey.') totCount, public_holidays.'.$primaryKey)
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        if($request->isMethod('post')){  
            $emailVali = 'required|email|unique:users';
            
            $additionalValidation=[
                "holiday_date"=> [
                            'required',
                            function ($attribute, $value, $fail) use ($request){
                               
                                if (!empty($value) && strtotime($value)< strtotime("+3 day",strtotime(date('Y-m-d')))){
                                    $fail("You should add holiday 3 days in advance");
                                }
                            }
                        ]
            ];
            $validator = $this->validation($request->all(),$additionalValidation);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $publicHoliday = new PublicHolidays();
            $publicHoliday = $this->holidaySave($request,$publicHoliday);
            
            Session::flash('status','Public Holiday Added successfully');
            Session::flash('class','success');
            
            return redirect('admin/setting/holidays');
        }else{
            
            $pagename = "Add Holiday";
            return view('admin.holidays.add',compact('pagename'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateHoliday(Request $request, $id)
    {
        $holidayId   = decrypt($id);
        $publicHoliday = PublicHolidays::find($holidayId);
        if($request->isMethod('post')){
            
            
            $additionalValidation=[
                "holiday_date"=> [
                            'required',
                            function ($attribute, $value, $fail) use ($publicHoliday){
                                if (!empty($value) && strtotime($publicHoliday->holiday_date) != strtotime($value) && strtotime($value)< strtotime("+2 day",strtotime(date('Y-m-d')))){
                                    $fail('Should be future date.');
                                }
                            }
                    ]
            ];
            $validator = $this->validation($request->all(),$additionalValidation);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            
            $publicHoliday = $this->holidaySave($request,$publicHoliday);

            if($publicHoliday->save()){
                Session::flash('status','Public Holiday Updated successfully');
                Session::flash('class','success');
            }else{
                 Session::flash('status','Problem in updating Public Holiday ');
                 Session::flash('class','danger');
            }
            return redirect('admin/setting/holidays');
        }else{
            
            $pagename = "Update Public Holiday";
            return view('admin.holidays.holidayUpdate',compact('pagename','publicHoliday'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    

    public function deleteHoliday(Request $request){
        $requestStatus=['status'=>402,'message'=>'Problem in deleting the record',"type"=>"danger"];
        if($request->isMethod('delete')){
            $holidayId=decrypt($request->holidayId);
                      
            $holidayRes = PublicHolidays::where('id',$holidayId)->first();
            if($holidayRes){
                $holidayRes->delete();
                $requestStatus = ['status'=>201,'message'=>'Holiday Deleted Successfully',"type"=>"success"];
            }
            
        }
        echo json_encode($requestStatus);
        //return redirect('merchant/users');
    }

    public function deleteMultipleHoliday(Request $request){
        if($request->isMethod('delete')){
            $i=0;
            foreach ($request->toDelete as $key => $eachHoliday) {
                $holidayRes = PublicHolidays::where('id',$eachHoliday)->first();
                if($holidayRes){
                    $holidayRes->delete();
                    $i++;
                }
            }
            Session::flash('status',$i.' Holiday Deleted Successfully');
            Session::flash('class','success');
            return redirect('admin/setting/holidays');
            
        }
    }

    private function validation($request,$additionalValidation){

            $validatorArray = [
                "holiday_event"=> 'required',
                "is_reocurr"=> 'required|in:0,1'
            ];
            
            $validationArr = array_merge($validatorArray,$additionalValidation);
            $validator     = \Validator::make($request,$validationArr);
            return $validator;
    }

    private function holidaySave($request,$publicHoliday){
            $publicHoliday->holiday_event          = $request->holiday_event; 
            $publicHoliday->holiday_date       = Helper::convertDate($request->holiday_date,"Y-m-d");
            $publicHoliday->is_reocurr          = $request->is_reocurr; 

            $publicHoliday->save(); 
            return $publicHoliday;
    }

    


}
