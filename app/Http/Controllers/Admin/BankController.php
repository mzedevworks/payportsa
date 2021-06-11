<?php

namespace App\Http\Controllers\Admin;

use App\User;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Helpers\DatatableHelper;
use App\Http\Controllers\Controller;
use App\Model\{BankDetails};
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class BankController extends Controller
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

        $pagename = "Banks";
        return view('admin.banks.list',compact('pagename'));
    }

    public function ajaxBanksList(Request $request){
        //print_r($request['columns']);
        // Table's primary key
        $primaryKey = 'id';
         
        // Array of database columns which should be read and sent back to DataTables.
        // The `db` parameter represents the column name in the database, while the `dt`
        // parameter represents the DataTables column identifier. In this case simple
        // indexes
        $columns = array(
            array( 'db' => 'bank_name','dt' => 0 ),
            array( 'db' => 'branch_code','dt' => 1 ),
            array( 'dt' => 2,
                    'db' => 'is_savings'
                ),
            array( 'dt' => 3,
                    'db' => 'is_cheque'
                ),
            
            array( 'dt' => 4,
                    'db' => 'is_realtime_avs'
                ),
            array(
                'dt'        => 5,
                'db' => 'is_batch_avs'
            ),
            array(
                'dt'        => 6,
                'db' => 'is_active'
            ),
            array(
                'dt'        => 7,
                'formatter' => function( $d, $row ) {
                   return encrypt($row['id']);
                }
            )

        );
        
        $bindings=[];

        $whereConditions="id > 0 ";
        $totalCount = DB::table('bank_details')
                ->selectRaw('count(bank_details.'.$primaryKey.') totCount')
                
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

       
        $data = DB::table('bank_details')
                ->selectRaw('bank_details.*')
                ->whereRaw($whereConditions, $bindings)
                ->orderByRaw($orderBy)
                ->offset(intval($request['start']))
                ->limit(intval($request['length']))
                ->get()
                ->toArray();
        
        $totalFilteredCount = DB::table('bank_details')
                ->selectRaw('count(bank_details.'.$primaryKey.') totCount, bank_details.'.$primaryKey)
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
            
            $additionalValidation=[
                                    "bank_name"     => 'required|no_special_char',
                                    "branch_code"   => 'required|without_spaces|no_special_char',
                                    "is_active"     =>  'required|in:yes,no',
                                    "is_savings"    => 'required|in:yes,no',
                                    "is_cheque"     => 'required|in:yes,no',
                                    "is_realtime_avs"    => 'required|in:yes,no',
                                    "is_batch_avs"     => 'required|in:yes,no',

                                ];
            $validator = $this->validation($request->all(),$additionalValidation);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $bankDetails = new BankDetails();
            $bankDetails = $this->bankSave($request,$bankDetails);
            
            
            if($bankDetails->save()){
                Session::flash('status','Bank Added successfully');
                Session::flash('class','success');
                
            }else{
                 Session::flash('status','Problem in saving bank');
                 Session::flash('class','danger');
            }
            
            return redirect('admin/banks');
            
        }else{
            $pagename = "Add Bank";
            return view('admin.banks.add',compact('pagename','request'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateBank(Request $request, $id)
    {
        if($request->isMethod('post')){
            $bankId   = decrypt($id);
            
            $additionalValidation=[
                                    "bank_name"     => 'required|no_special_char',
                                    "branch_code"   => 'required|without_spaces|no_special_char',
                                    "is_active"     =>  'required|in:yes,no',
                                    "is_savings"    => 'required|in:yes,no',
                                    "is_cheque"     => 'required|in:yes,no',
                                    "is_realtime_avs"    => 'required|in:yes,no',
                                    "is_batch_avs"     => 'required|in:yes,no',
                                ];
            $validator = $this->validation($request->all(),$additionalValidation);
            
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $bankDetails = BankDetails::find($bankId);
            $bankDetails = $this->bankSave($request,$bankDetails);

            if($bankDetails->save()){
                Session::flash('status','Bank Added successfully');
                Session::flash('class','success');
                
            }else{
                 Session::flash('status','Problem in saving bank');
                 Session::flash('class','danger');
            }
            
            return redirect('admin/banks');
        }else{
            $bankDetails = BankDetails::find(decrypt($id));
            $pagename = "Update Bank";
            return view('admin.banks.bankUpdate',compact('pagename','bankDetails'));
        }
    }


    private function validation($request,$additionalValidation){
            
            //"avs_bank_code"=>"regex:/[0-9]+/"
            
            //$validationArr = array_merge($validatorArray,$paymentValidation,$additionalValidation);
            $validator     = \Validator::make($request,$additionalValidation ,[
                "without_spaces"=>"Should not have any space",
                "required"=>"This field is required",
                "avs_bank_code.regex"=>"Should be number only"
            ]);
            $validator->sometimes(['avs_bank_code'], 'regex:/[0-9]+/', function ($input) {
                return !empty($input->avs_bank_code);
            });
            $validator->sometimes(['avs_bank_code'], 'required|without_spaces', function ($input) {
                return $input->is_realtime_avs=='yes';
            });
            return $validator;
    }

    private function bankSave($request,$bankDetail){

            $bankDetail->branch_code            = $request->branch_code; 
            $bankDetail->is_active            = $request->is_active; 
            $bankDetail->is_savings            = $request->is_savings; 
            $bankDetail->is_cheque                = $request->is_cheque; 
            $bankDetail->bank_name              = $request->bank_name; 
            $bankDetail->avs_bank_code                = $request->avs_bank_code; 
            $bankDetail->is_realtime_avs              = $request->is_realtime_avs; 
            $bankDetail->is_batch_avs              = $request->is_batch_avs; 
            return $bankDetail;
    }

    

   
    

    

}
