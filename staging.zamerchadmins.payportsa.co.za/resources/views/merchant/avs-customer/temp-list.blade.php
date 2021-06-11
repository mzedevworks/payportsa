@extends('layouts.app') 

@section('extra_style')
    <!-- DataTables -->
    <link rel="stylesheet" type="text/css" href="{{ asset('css/icons.css') }}">
    <link href="{{ asset('plugins/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('plugins/datatables/buttons.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Responsive datatable examples -->
    <link href="{{ asset('plugins/datatables/responsive.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/bootstrap-editable.css') }}" rel="stylesheet">
    <style type="text/css">
        .collection_date_info .editable-container.editable-inline{
            width: 370px;
        }
        .collection_end_date_info .editable-container.editable-inline{
            width: 370px;
        }
    </style>
@endsection 

@section('content')
@php
$service = Config::get('constants.serviceType');
//dd($customers);
@endphp

<div class="container-fluid">
    <div class="row">
        <div class="col-12 grid-margin">
            <div class="card">
                @if(count($avsEnquiries)==0)
                <div class="card-header border-bottom">
                    <h4 class="card-title">Create AVS Batch</h4>
                </div>
          
                <div class="card-body">
                    <form class="form-sample" method="post" 
                        action="{{ url('merchant/avs/import') }}" enctype="multipart/form-data" autocomplete="off">
                          @csrf
                        <div class="row">
                            <div class="col-md-12">
                              <div class="form-group row">
                                <label class="col-sm-3">Beneficiary Type *</label>
                              
                                <div class="col-sm-9">
                                    <div class="custom-control custom-radio radio custom-control-inline">

                                      <input type="radio" class="custom-control-input" id="avs_type_individual" name="avs_type" value="individual" {{ (old('avs_type')=='individual') ? 'checked': ''}}>
                                      <label class="custom-control-label" for="avs_type_individual">Individual</label>
                                    </div>
                                  
                                    <div class="custom-control custom-radio radio custom-control-inline">
                                      
                                      <input type="radio" class="custom-control-input" id="avs_type_business" name="avs_type" value="business" {{ (old('avs_type')=='business') ? 'checked': ''}}>
                                      <label class="custom-control-label" for="avs_type_business">Business</label>
                                    </div>
                                    <p class="error" id="error_avs_type">{{$errors->first('avs_type')}}</p>
                                </div>
                                  
                              </div>
                            </div>
                            <div class="col-md-12">
                              <div class="form-group row {{$errors->first('file_name')?'has-error':''}}">
                                <label class="col-sm-3 col-form-label">CSV*</label>
                                <div class="col-sm-9">
                                  <input type="file" name="file_name">
                                  <p class="error" >{{$errors->first('file_name')}}</p>
                                  <a href="{{ url('merchant/avs/samplecsv')}}" class="purple-btn">Download Sample csv</a>
                                </div>
                              </div>
                            </div>
                            
                            <div class="col-md-12 ml-3">
                              <div class="form-group row {{$errors->first('consent')?'has-error':''}}">
                                <div class="custom-control custom-checkbox checkbox custom-control-inline">
                                    <input type="checkbox" class="custom-control-input" name="concent_check" id="concent_check" value="yes" onclick="concentUpdate();" {{ (old('concent_check')=='yes') ? 'checked': ''}}>
                                    <label class="custom-control-label" for="concent_check">I have obtained consent from the individual beneficiary or entity on use of ID number or Company registration number for the purpose of this account verification</label><br/>
                                    
                                  </div>
                                  <p class="error col-sm-12" id="error_consent">{{ $errors->first('concent_check')}}</p>
                              </div>
                            </div>
                        </div>
                        <button id="concentSubmitBtn" type="submit" class="btn btn-common mr-3" onclick="submitAvs();" >Submit</button>
                        <a href="{{ url('avs/history/batch') }}" class="btn btn-light">Cancel</a>
                    </form>
                </div>
                @else
                <div class="card-header border-bottom">
                    <button id="saveAll" type="submit" disabled="disabled" onclick="saveAllAction()" class="btn btn-common mb-3 transactionClicked">Save All Records</button>
                    
                    <a class="btn btn-common purple-btn mr-4 mb-3" href="{{ url('merchant/avs/delete-temp-list')}}">Discard All Records</a>
                </div>
                 <div class="card-body">
                    <div class="errorhtml"></div>
                    @include('elements.message')
                    <div class="alert alert-warning" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                        
                        <strong>Warning!</strong> 
                          Please update the pending customers With proper format then only you will be able to upload next file .
                          
                    </div>
                    <div class="table-responsive" style="overflow-y:scroll; max-height: 600px">
                        <table id="datatable" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" name="checkallCustomers" id="checkallCustomers" class="">
                                    </th>
                                    <th>Id/Registration Number</th>
                                    <th>Initials</th>
                                    <th>Company/SurName</th>
                                    <th>Bank</th>
                                    <th>Branch</th>
                                    <th>Account Number</th>
                                    <th>Account Type</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($avsEnquiries as $key => $eachEnquiry)
                                    <tr>
                                        @php
                                        $data_customer = json_decode($eachEnquiry['dataset']);
                                        $error_data = json_decode($eachEnquiry['errorset']);
                                        @endphp
                                        <td>
                                            <input type="checkbox" 
                                            name="checkUsers[]" value="{{ encrypt($eachEnquiry->id) }}" class="transactionCheckboxes" onclick="transactionClicked(this);">
                                        
                                            <input type="hidden" name="id" value="{{ encrypt($eachEnquiry->id) }}"/>
                                        </td>
                                        

                                        <td>
                                            @if(in_array('beneficiary_id_number',$error_data))
                                                <a href="#" data-type="text" data-pk="1" data-name="beneficiary_id_number" class="editable editable-click editable-open">
                                                    {{ array_key_exists("beneficiary_id_number",$data_customer) ? $data_customer->beneficiary_id_number : '' }}
                                                </a>
                                            @else
                                                {{ array_key_exists("beneficiary_id_number",$data_customer) ? $data_customer->beneficiary_id_number : '' }}
                                            @endif
                                       </td>

                                        <td>
                                            @if(in_array('beneficiary_initial',$error_data))
                                                <a href="#" data-type="text" data-pk="1" data-name="beneficiary_initial" class="editable editable-click editable-open">
                                                    {{ array_key_exists("beneficiary_initial",$data_customer) ? $data_customer->beneficiary_initial : '' }}</a>
                                            @else
                                                {{ array_key_exists("beneficiary_initial",$data_customer) ? $data_customer->beneficiary_initial : '' }} 
                                            @endif
                                        </td>

                                        <td>
                                            @if(in_array('beneficiary_last_name',$error_data))
                                                <a href="#" data-type="text" data-pk="1" data-name="beneficiary_last_name" class="editable editable-click editable-open" >
                                                    {{ array_key_exists("beneficiary_last_name",$data_customer) ? $data_customer->beneficiary_last_name : '' }}
                                                </a>
                                            @else
                                                {{ array_key_exists("beneficiary_last_name",$data_customer) ? $data_customer->beneficiary_last_name : '' }} 
                                            @endif
                                       </td>

                                        <td>
                                            @if(in_array('bank_name',$error_data))
                                                <a href="#" data-type="text" data-pk="1" data-name="bank_name" class="editable editable-click editable-open bank_name">
                                                    {{ array_key_exists("bank_name",$data_customer) ? $data_customer->bank_name : '' }}</a>
                                            @else
                                                {{ array_key_exists("bank_name",$data_customer) ? $data_customer->bank_name : '' }} 
                                            @endif
                                        </td>

                                        <td>
                                            @if(in_array('branch_code',$error_data))
                                                <a href="#" data-type="text" data-pk="1" data-name="branch_code" class="editable editable-click editable-open">
                                                    {{ array_key_exists("branch_code",$data_customer) ? $data_customer->branch_code : '' }}
                                                </a>
                                            @else
                                                {{ array_key_exists("branch_code",$data_customer) ? $data_customer->branch_code : '' }}
                                            @endif
                                        </td>

                                        <td>
                                            @if(in_array('bank_account_number',$error_data))
                                                <a href="#" data-type="text" data-pk="1" data-name="bank_account_number" class="editable editable-click editable-open" >
                                                    {{ array_key_exists("bank_account_number",$data_customer) ? $data_customer->bank_account_number : '' }}
                                                </a>
                                            @else
                                                {{ array_key_exists("bank_account_number",$data_customer) ? $data_customer->bank_account_number : '' }} 
                                            @endif
                                        </td>
                                        <td>
                                            
                                            @if(in_array('bank_account_type',$error_data))
                                                <a href="#" data-type="select" data-pk="1" data-name="bank_account_type" class="editable-click editable-open bank_account_type">
                                                    {{ array_key_exists("bank_account_type",$data_customer) ? $data_customer->bank_account_type : '' }}
                                                </a>
                                            @else
                                                {{ array_key_exists("bank_account_type",$data_customer) ? $data_customer->bank_account_type : '' }} 
                                            @endif
                                        </td>
                                        <td>

                                            <div class="float-left">
                                                
                                               
                                                <button type="button" class="btn btn-common" onclick="saveAction(this);">Save</button>
                                                <form method="POST" action="{{ url('merchant/avs/delete-temp/'.encrypt($eachEnquiry->id))}}" id="frmdelete{{$eachEnquiry->id}}" class="btn bg-transparent">
                                                    {{ csrf_field() }}
                                                    {{ method_field('DELETE') }}

                                                    <button type="button" onclick="confirmFormSubmit('Are you sure, you want to delete!','frmdelete{{$eachEnquiry->id}}')" class="btn bg-transparent"><i class="lni-trash" aria-hidden="true"></i></button>
                                                </form>
                                          </div>
                                        </td>
                                    </tr>
                                @endforeach
                                
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>
        </div>
        <!-- end col -->
    </div>
    <!-- end row -->
@endsection 

@section('extra_script')
<!-- Required datatable js -->
<script src="{{ asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/dataTables.bootstrap4.min.js') }}"></script>
<!-- Buttons examples -->
<script src="{{ asset('plugins/datatables/dataTables.buttons.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/buttons.bootstrap4.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/jszip.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/vfs_fonts.js') }}"></script>
<script src="{{ asset('plugins/datatables/buttons.html5.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/buttons.print.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/buttons.colVis.min.js') }}"></script>
<!-- Responsive examples -->
<script src="{{ asset('plugins/datatables/dataTables.responsive.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/responsive.bootstrap4.min.js') }}"></script>

<!-- Datatable init js -->
<!-- <script src="{{ asset('js/datatables.init.js') }}"></script> -->
<script src="{{ asset('js/moment.min.js') }}"></script>
<script src="{{ asset('js/bootstrap-editable.min.js') }}"></script>
<script src="{{ asset('js/bootstrap-editable.js') }}"></script>
<script type="text/javascript">
    function concentUpdate(){
        if($("#concent_check").prop("checked") == true){
            $("#concentSubmitBtn").attr("disabled",false);
        }else if($("#concent_check").prop("checked") == false){
            $("#concentSubmitBtn").attr("disabled",true);
        }
    }
    function confirmFormSubmit(message,formId){
        confirmDialog(message, (ans) => {
          if (ans) {
            $("#"+formId).submit();
            }
         });
    }
    //var account_type = [{'value': 'cheque', 'text': 'Cheque'}, {'value': 'saving', 'text': 'Saving'}];
    var account_type='{{ Helper::getAccountType() }}';
    account_type=JSON.parse(account_type.replace(/&quot;/g,'"'));
    
    
    var selectedRow = [];
    var selectedTransactions=[];
    
    
    $(document).ready(function() {
        $.fn.editable.defaults.mode = 'inline';
        $('.transactionCheckboxes').prop('checked',false);
        $('#checkallCustomers').prop('checked',false);
        $("#checkallCustomers").click(function(){
            if($(this).prop("checked") == true){
                $(".transactionCheckboxes").each(function() {
                    $( this ).prop("checked",true);
                    var elemVal=this.value;
                    //var elemVal=this.value;
                    var arrIndex=selectedTransactions.indexOf(elemVal);
                    if(arrIndex===-1){
                        selectedTransactions.push(elemVal);
                    }
                });
            }else if($(this).prop("checked") == false){
                $(".transactionCheckboxes").each(function() {
                    $( this ).prop("checked",false);
                    var elemVal=this.value;
                    //var elemVal=this.value;
                    var arrIndex=selectedTransactions.indexOf(elemVal);
                    if(arrIndex!==-1){
                        selectedTransactions.splice(arrIndex, 1);
                    }
                });
            }
            createFormFeild();
        });

        
       
        $('.editable').editable();
        
        
        
        console.log(account_type);
        $('.bank_account_type').editable({
            'mode'  : 'inline',
            'source': function() {
                return account_type;
            },
        });

        
        
       
        $('#datatable').DataTable( {
            "fnDrawCallback": function( oSettings ) {
                $('.editable').editable();
                
                
                $('.bank_account_type').editable({
                    'mode'  : 'inline',
                    'source': function() {
                        return account_type;
                    },
                });
            }
        });
    });

    function retriveData(tableRow,tdIndex){
        
        if(tableRow.find("td").eq(tdIndex).find("a").length>0){
            var data=tableRow.find("td").eq(tdIndex).find("a").text().trim();
        }else{
            var data=tableRow.find("td").eq(tdIndex).text().trim();;
        }
        if(data=="Empty"){
            data = '';
        }
        return data;   
    }

    function transactionClicked(elem){
        var elemVal= elem.value;
        var arrIndex=selectedTransactions.indexOf(elemVal);
        if($(elem).prop("checked") == true){
            if(arrIndex===-1){
                
                selectedTransactions.push(elemVal);
            }
        }else if($(elem).prop("checked") == false){
            if(arrIndex!==-1){
                selectedTransactions.splice(arrIndex, 1);
            }
        }

        if($("[class='transactionCheckboxes']:not(:checked)").length===0){
            $("#saveAll").prop("checked",true);
        }else{
            $("#saveAll").prop("checked",false);
        }
        createFormFeild();
    }

    function createFormFeild(){
        if(selectedTransactions.length>0){
            $("#saveAll").prop("disabled",false);
        }else{
            $("#saveAll").prop("disabled",true);
        }

    }

    function getData(tableRow){
        var data={
            'id'                      : $(tableRow).find("td").eq(0).find("[name='id']").val(),
            'beneficiary_id_number'   : retriveData($(tableRow),1),
            'beneficiary_initial'     : retriveData($(tableRow),2),
            'beneficiary_last_name'   : retriveData($(tableRow),3),
            'bank_name'               : retriveData($(tableRow),4),
            'branch_code'             : retriveData($(tableRow),5),
            'bank_account_number'     : retriveData($(tableRow),6),
            'bank_account_type'       : retriveData($(tableRow),7),
        };
        return data;
    }

    function saveAllAction(){
        var dataArray =[];
        $(".transactionCheckboxes").each(function() {

                if($(this).prop("checked")==true){

                    var elemVal=parseInt($(this).value);
                    selectedTransactions.push(elemVal);
                    var tableRow       = $(this).parents("tr");
                    //var selectedRow = rowValues(elemVal,tableRow);
                    var data = getData(tableRow);
                    dataArray.push(data);
                }
        });
        var dataString = JSON.stringify(dataArray);
        $.ajax({
            //url    : '{{ url("merchant/edit/multiple/temp/customer") }}',
            url    : '{{ url("merchant/avs/update-mul-temp") }}',
            method : 'post',
            data   : { data : dataString },
            success : function(res) {
                window.location.reload();
            }
        });    
    }
    function saveAction(elem){

        var tableRow       = $(elem).parents("tr");
        var data           = getData(tableRow);
        var dataString     = JSON.stringify(data);
        $.ajax({
            url    : '{{ url("merchant/avs/update-temp") }}',
            method : 'post',
            data   : {  data : dataString },
            success : function(res) {
                
                if(jQuery.isEmptyObject(res.errors)){
                    window.location.reload();
                }else{
                    var errorhtml = "<div class='alert alert-danger'><strong>Some Validation Failed!!</strong><ul>";
                    $.each(res.errors,function(key,value){
                        
                        $.each(value,function(index,error){
                            errorhtml += '<li>'+error+'</li>';
                        });
                    });
                    errorhtml += '</ul></div>';
                    $('.errorhtml').html(errorhtml);
                }
            }
        });

    }
</script>
@endsection