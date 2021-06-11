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
        <div class="col-12">
            <div class="card">
                @if(count($customers)==0)
                <div class="card-header border-bottom">
                    <form class="form-sample" method="post" 
                        action="{{ url('merchant/collection/normal/customer/import') }}" enctype="multipart/form-data" autocomplete="off">
                          @csrf
                         <input type="file" name="file_name">
                         <input type="submit" name="" class="btn btn-common" value="Upload List"></a></br>
                         <a href="{{ url('merchant/collection/normal/customer/samplecsv')}}" class="purple-btn">Download Sample csv</a>
                       </form>
                </div>
                @endif
                <div class="card-body">
                    <div class="errorhtml"></div>
                    @include('elements.message')
                    @if(count($customers)>0)
                    
                    <button id="saveAll" type="submit" disabled="disabled" onclick="saveAllAction()" class="btn btn-common mb-3 transactionClicked">Save All Records</button>
                    
                    <a class="btn btn-common purple-btn mr-4 mb-3" href="{{ url('merchant/collection/normal/customertmp/deletecsv')}}">Discard All Records</a>

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
                                    <th>Mandate Id</th>
                                    <th>First Name</th>
                                    <th>Last Name</th>
                                    <th>Email</th>
                                    <th>Contact Number</th>
                                    <th>Id Number</th>
                                    <th>Address Line One</th>
                                    <th>Address Line Two</th>
                                    <th>suburb</th>
                                    <th>City</th>
                                    <th>Province</th>
                                    
                                    <th>Bank Name</th>
                                    <th>Account Type</th>
                                    <th>Branch Code</th>
                                    <th>Acount Holder Name</th>
                                    <th>Account Number</th>
                                    
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($customers as $key => $customer)
                                    <tr>
                                        @php
                                        $data_customer = json_decode($customer['dataset']);
                                        $error_data = json_decode($customer['errorset']);
                                        @endphp
                                        <td>
                                            <input type="checkbox" 
                                            name="checkUsers[]" value="{{ encrypt($customer->id) }}" class="transactionCheckboxes" onclick="transactionClicked(this);">
                                        
                                            <input type="hidden" name="id" value="{{ encrypt($customer->id) }}"></td>
                                        <td>
                                            
                                            @if(in_array('mandate_id',$error_data) || in_array($data_customer->mandate_id,$mandateArray))
                                            
                                                <a href="#" data-type="text" data-pk="1" data-name="mandate_id" class="editable editable-click editable-open">
                                                    {{ property_exists($data_customer, "mandate_id") ? $data_customer->mandate_id : '' }}
                                                </a>
                                            @else
                                                {{ property_exists($data_customer, "mandate_id") ? $data_customer->mandate_id : '' }} 
                                            @endif
                                        </td>

                                        <td>
                                            @if(in_array('first_name',$error_data))
                                                <a href="#" data-type="text" data-pk="1" data-name="first_name" class="editable editable-click editable-open">
                                                    {{ property_exists($data_customer, "first_name") ? $data_customer->first_name : '' }}
                                                </a>
                                            @else
                                                {{ property_exists($data_customer, "first_name") ? $data_customer->first_name : '' }}
                                            @endif
                                       </td>

                                        <td>
                                            @if(in_array('last_name',$error_data))
                                                <a href="#" data-type="text" data-pk="1" data-name="last_name" class="editable editable-click editable-open">
                                                    {{ property_exists($data_customer, "last_name") ? $data_customer->last_name : '' }}</a>
                                            @else
                                                {{ property_exists($data_customer, "last_name") ? $data_customer->last_name : '' }} 
                                            @endif
                                        </td>

                                        <td>
                                            @if(in_array('email',$error_data))
                                                <a href="#" data-type="text" data-pk="1" data-name="email" class="editable editable-click editable-open" >
                                                    {{ property_exists($data_customer, "email") ? $data_customer->email : '' }}
                                                </a>
                                            @else
                                                {{ property_exists($data_customer, "email") ? $data_customer->email : '' }} 
                                            @endif
                                       </td>

                                        <td>
                                            @if(in_array('contact_number',$error_data))
                                                <a href="#" data-type="text" data-pk="1" data-name="contact_number" class="editable editable-click editable-open contact_number">
                                                    {{ property_exists($data_customer, "contact_number") ? $data_customer->contact_number : '' }}</a>
                                            @else
                                                {{ property_exists($data_customer, "contact_number") ? $data_customer->contact_number : '' }} 
                                            @endif
                                        </td>

                                        <td>
                                            @if(in_array('id_number',$error_data))
                                                <a href="#" data-type="text" data-pk="1" data-name="id_number" class="editable editable-click editable-open">
                                                    {{ property_exists($data_customer, "id_number") ? $data_customer->id_number : '' }}
                                                </a>
                                            @else
                                                {{ property_exists($data_customer, "id_number") ? $data_customer->id_number : '' }}
                                            @endif
                                        </td>

                                        <td>
                                            @if(in_array('address_one',$error_data))
                                                <a href="#" data-type="text" data-pk="1" data-name="address_one" class="editable editable-click editable-open" >
                                                    {{ property_exists($data_customer, "address_one") ? $data_customer->address_one : '' }}
                                                </a>
                                            @else
                                                {{ property_exists($data_customer, "address_one") ? $data_customer->address_one : '' }} 
                                            @endif
                                        </td>

                                        <td>
                                            @if(in_array('address_line_two',$error_data))
                                                <a href="#" data-type="text" data-pk="1" data-name="address_line_two" class="editable editable-click editable-open">
                                                    {{ property_exists($data_customer, "address_line_two") ? $data_customer->address_line_two : '' }}
                                                </a>
                                            @else
                                                {{ property_exists($data_customer, "address_line_two") ? $data_customer->address_line_two : '' }} 
                                            @endif
                                        </td>

                                       <td>
                                            @if(in_array('suburb',$error_data))
                                                <a href="#" data-type="text" data-pk="1" data-name="suburb" class="editable editable-click editable-open">
                                                    {{ property_exists($data_customer, "suburb") ? $data_customer->suburb : '' }}
                                                </a>
                                            @else
                                                {{ property_exists($data_customer, "suburb") ? $data_customer->suburb : '' }} 
                                            @endif
                                        </td>

                                        <td>
                                            @if(in_array('city',$error_data))
                                                <a href="#" data-type="text" data-pk="1" data-name="city"class="editable editable-click editable-open">
                                                    {{ property_exists($data_customer, "city") ? $data_customer->city : '' }}
                                                </a>
                                            @else
                                                {{ property_exists($data_customer, "city") ? $data_customer->city : '' }} 
                                            @endif
                                        </td>

                                        <td>
                                            @if(in_array('province',$error_data))
                                                <a href="#" data-type="text" data-pk="1" data-name="province"class="editable editable-click editable-open">
                                                    {{ property_exists($data_customer, "province") ? $data_customer->province : '' }}
                                                </a>
                                            @else
                                                {{ property_exists($data_customer, "province") ? $data_customer->province : '' }}
                                            @endif
                                        </td>

                                        


                                        <td>
                                            @if(in_array('bank_name',$error_data))
                                                <a href="#" data-type="select" data-pk="1" data-name="bank_name" class="editable-click editable-open bank_select">
                                                    {{ property_exists($data_customer, "bank_name") ? $data_customer->bank_name : '' }}</a>
                                            @else
                                                {{ property_exists($data_customer, "bank_name") ? $data_customer->bank_name : '' }} 
                                            @endif
                                        </td>

                                        <td>
                                            @if(in_array('account_type',$error_data))
                                                <a href="#" data-type="select" data-pk="1" data-name="account_type" class="editable-click editable-open account_type">
                                                    {{ property_exists($data_customer, "account_type") ? $data_customer->account_type : '' }}
                                            </a>
                                            @else
                                                {{ property_exists($data_customer, "account_type") ? $data_customer->account_type : '' }} 
                                            @endif
                                        </td>

                                        <td>
                                            @if(in_array('branch_code',$error_data))
                                                <a href="#" data-type="text" data-pk="1" data-name="branch_code" class="editable editable-click editable-open branch_code">
                                                    {{ property_exists($data_customer, "branch_code") ? $data_customer->branch_code : '' }}
                                                </a>
                                            @else
                                                {{ property_exists($data_customer, "branch_code") ? $data_customer->branch_code : '' }} 
                                            @endif
                                        </td>

                                        <td>@if(in_array('account_holder_name',$error_data))<a href="#" data-type="text" data-pk="1" data-name="account_holder_name"
                                        class="editable editable-click editable-open"
                                        >{{ property_exists($data_customer, "account_holder_name") ? $data_customer->account_holder_name : '' }}</a>
                                        @else{{ property_exists($data_customer, "account_holder_name") ? $data_customer->account_holder_name : '' }} @endif
                                        </td>

                                        
                                        <td>@if(in_array('account_number',$error_data))<a href="#" data-type="text" data-pk="1" data-name="account_number"
                                        class="editable editable-click editable-open account_number"
                                        >{{ property_exists($data_customer, "account_number") ? $data_customer->account_number : '' }}</a>
                                        @else{{ property_exists($data_customer, "account_number") ? $data_customer->account_number : '' }}@endif
                                        </td>

                                       

                                        

                                        
                                        <td>

                                            <div class="float-left">
                                                
                                                <!-- <a href="{{ url('merchant/temp/$customers/save/'.encrypt($customer->id))}}" class="btn btn-common">Save</a> -->
                                                <button type="button" class="btn btn-common" onclick="saveAction(this);">Save</button>
                                                <form method="POST" action="{{ url('merchant/collection/normal/customertmp/delete/'.encrypt($customer->id))}}" id="frmdelete{{$customer->id}}" class="btn bg-transparent">
                                                    {{ csrf_field() }}
                                                    {{ method_field('DELETE') }}

                                                    <button type="button" onclick="confirmFormSubmit('Are you sure, you want to delete!','frmdelete{{$customer->id}}')" class="btn bg-transparent"><i class="lni-trash" aria-hidden="true"></i></button>
                                                </form>
                                          </div>
                                        </td>
                                    </tr>
                                @endforeach
                                
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
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
    function confirmFormSubmit(message,formId){
        confirmDialog(message, (ans) => {
          if (ans) {
            $("#"+formId).submit();
            }
         });
    }
    var account_type = [{'value': 'cheque', 'text': 'Cheque'}, {'value': 'saving', 'text': 'Saving'}];
    
    
    var bank = '{{ Helper::getBankDetails() }}';
    var bank = JSON.parse(bank.replace(/&quot;/g,'"'));
    var bank_name = [];
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

        $.each(bank,function(key,value){
            bank_name.push(value.bank_name);
        });
       
        $('.editable').editable();
        $('.bank_select').editable({
            'mode'  : 'inline',
            'source': function() {
                return bank_name;
            },
        });
        
        
        
        $('.account_type').editable({
            'mode'  : 'inline',
            'source': function() {
                return account_type;
            },
        });

        /*$('.collection_date').editable({
            format: 'YYYY-MM-DD',    
            viewformat: 'YYYY-MM-DD',    
            template: 'YYYY / MMMM / D',    
            combodate: {
                minYear: (new Date).getFullYear(),
                maxYear: 2050,
                minuteStep: 1
            }
        });*/

        
       
        $('#datatable').DataTable( {
            "fnDrawCallback": function( oSettings ) {
                $('.editable').editable();
                $('.bank_select').editable({
                    'mode'  : 'inline',
                    'source': function() {
                        return bank_name;
                    },
                });
                
                $('.account_type').editable({
                    'mode'  : 'inline',
                    'source': function() {
                        return account_type;
                    },
                });
                
                
                
                /*$('.collection_date').editable({
                        format: 'YYYY-MM-DD',    
                        viewformat: 'YYYY-MM-DD',    
                        template: 'YYYY / MMMM / D',    
                        combodate: {
                                minYear: (new Date).getFullYear(),
                                maxYear: 2050,
                                minuteStep: 1
                        }
                });*/

                

                

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
            'mandate_id'              : retriveData($(tableRow),1),
            'first_name'              : retriveData($(tableRow),2),
            'last_name'               : retriveData($(tableRow),3),
            'email'                   : retriveData($(tableRow),4),
            'contact_number'          : retriveData($(tableRow),5),
            'id_number'               : retriveData($(tableRow),6),
            'address_one'             : retriveData($(tableRow),7),
            'address_line_two'        : retriveData($(tableRow),8),
            'suburb'                  : retriveData($(tableRow),9),
            'city'                    : retriveData($(tableRow),10),
            'province'                : retriveData($(tableRow),11),
            'bank_name'               : retriveData($(tableRow),12),
            'account_type'            : retriveData($(tableRow),13),
            'branch_code'             : retriveData($(tableRow),14),
            'account_holder_name'     : retriveData($(tableRow),15),
            'account_number'          : retriveData($(tableRow),16),
            // 'once_off_amount'         : retriveData($(tableRow),17),
            // 'collection_date'         : retriveData($(tableRow),18)
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
            url    : '{{ url("merchant/collection/normal/customertmp/mul-edit") }}',
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
            url    : '{{ url("merchant/collection/normal/customertmp/edit") }}',
            method : 'post',
            data   : {  data : dataString },
            success : function(res) {
                
                if(jQuery.isEmptyObject(res.errors)){
                    window.location.reload();
                }else{
                    var errorhtml = "<div class='alert alert-danger'><strong>Some Validation Failed!!</strong><ul>";
                    $.each(res.errors,function(key,value){
                        console.log(key);
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