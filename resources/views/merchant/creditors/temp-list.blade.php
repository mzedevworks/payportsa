@extends('layouts.app') 

@section('extra_style')
    <!-- DataTables -->
    <link rel="stylesheet" type="text/css" href="{{ asset('css/icons.css') }}">
    <link href="{{ asset('plugins/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('plugins/datatables/buttons.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Responsive datatable examples -->
    <link href="{{ asset('plugins/datatables/responsive.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/bootstrap-editable.css') }}" rel="stylesheet">
@endsection 

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom">
                    @if(count($creditors)==0)
                       <form class="form-sample" method="post" 
                        action="{{ url('merchant/creditors/import') }}" enctype="multipart/form-data" autocomplete="off">
                          @csrf
                         <input type="file" name="file_name">
                         <input type="submit" name="" class="btn btn-common" value="Upload List"></a>
                       </form>
                       <a href="{{ url('merchant/creditors/samplecsv')}}" class="purple-btn">Download Sample csv</a>
                    @endif
                </div>
                <div class="card-body">
                    <div class="errorhtml"></div>
                    @include('elements.message')
                    
                    @if(count($creditors)>0)
                    <button id="saveAll" type="submit" disabled="disabled" onclick="saveAllAction()" class="btn btn-common mb-3 transactionClicked">Save All Records</button>

                    <div class="alert alert-warning" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                              <span aria-hidden="true">×</span>
                            </button>
                          <strong>Warning!</strong> Please update the pending creditors With proper formate then only you will be able to upload next file .
                          <a class="btn btn-common pull-right purple-btn mr-4" href="{{ url('merchant/creditors/delete/tempcsv')}}" class="purple-btn">Discard All</a>
                    </div>
                    <div class="table-responsive" style="overflow-y:scroll; max-height: 600px">
                        <table id="datatable" class="table table-bordered">
                            <thead>
                                <tr>
                                     <th>
                                        <input type="checkbox" name="checkallCustomers" id="checkallCustomers" class="">
                                    </th>
                                    <th>First Name</th>
                                    <th>Last Name</th>
                                    <th>Amount</th>
                                    <th>Account Holder Name</th>
                                    <th>Bank Name</th>
                                    <th>Account Type</th>
                                    <th>Branch Code</th>
                                    <th>Account Number</th>
                                    <th>Email</th>
                                    <th>Address</th>
                                    <th>Contact Number</th>
                                    <th>Employee No.</th>
                                    
                                    
                                    <th>Reference</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($creditors as $key => $employee)
                                    <tr>
                                        @php
                                        $data_employee = json_decode($employee['dataset']);
                                        $error_data = json_decode($employee['errorset']);
                                        @endphp
                                        <td><input type="checkbox" 
                                            name="checkUsers[]" value="{{ encrypt($employee->id) }}" class="transactionCheckboxes" onclick="transactionClicked(this);">
                                        <input type="hidden" name="id" value="{{ encrypt($employee->id) }}"></td>
                                        

                                        <td>
                                        @if(in_array('first_name',$error_data))
                                        <a href="#" data-type="text" data-pk="1" data-name="first_name"
                                        class="editable editable-click editable-open"
                                        >{{$data_employee->first_name}}</a>
                                        @else
                                        {{$data_employee->first_name}}
                                        @endif
                                       </td>

                                        <td>@if(in_array('last_name',$error_data))<a href="#" data-type="text" data-pk="1" data-name="last_name"
                                        class="editable editable-click editable-open"
                                        >{{$data_employee->last_name}}</a>
                                        @else{{ $data_employee->last_name }} @endif
                                        </td>

                                        <td>@if(in_array('salary',$error_data))<a href="#" data-type="text" data-pk="1" data-name="salary"
                                        class="editable editable-click editable-open"
                                        >{{$data_employee->salary}}</a>
                                        @else{{$data_employee->salary }} @endif
                                        </td>
                                        
                                        <td>@if(in_array('account_holder_name',$error_data))<a href="#" data-type="text" data-pk="1" data-name="account_holder_name"
                                        class="editable editable-click editable-open"
                                        >{{$data_employee->account_holder_name}}</a>
                                        @else{{$data_employee->account_holder_name }} @endif
                                        </td>


                                        <td>@if(in_array('bank_name',$error_data))<a href="#" data-type="select" data-pk="1" data-name="bank_name"
                                        class="editable-click editable-open bank_select"
                                        >{{$data_employee->bank_name}}</a>
                                        @else{{$data_employee->bank_name }} @endif
                                        </td>

                                        <td>@if(in_array('account_type',$error_data))<a href="#" data-type="select" data-pk="1" data-name="account_type"
                                        class="editable-click editable-open account_type"
                                        >{{$data_employee->account_type}}</a>
                                        @else{{$data_employee->account_type }} @endif</td>

                                        <td>@if(in_array('branch_code',$error_data))<a href="#" data-type="text" data-pk="1" data-name="branch_code"
                                        class="editable editable-click editable-open branch_code"
                                        >{{$data_employee->branch_code}}</a>
                                        @else{{$data_employee->branch_code }} @endif</td>

                                        <td>@if(in_array('account_number',$error_data))<a href="#" data-type="text" data-pk="1" data-name="account_number"
                                        class="editable editable-click editable-open account_number"
                                        >{{$data_employee->account_number}}</a>
                                        @else{{$data_employee->account_number }} @endif
                                        </td>

                                        <td>@if(in_array('email',$error_data))<a href="#" data-type="text" data-pk="1" data-name="email"
                                        class="editable editable-click editable-open"
                                        >{{$data_employee->email}}</a>
                                        @else{{$data_employee->email }} @endif
                                       </td>

                                        <td>@if(in_array('address',$error_data))<a href="#" data-type="text" data-pk="1" data-name="address"
                                        class="editable editable-click editable-open"
                                        >{{$data_employee->address}}</a>
                                        @else{{$data_employee->address }} @endif
                                        </td>

                                        <td>@if(in_array('contact_number',$error_data))<a href="#" data-type="text" data-pk="1" data-name="contact_number"
                                        class="editable editable-click editable-open contact_number"
                                        >{{$data_employee->contact_number}}</a>
                                        @else{{$data_employee->contact_number }} @endif
                                        </td>

                                        <td>@if(in_array('id_number',$error_data))<a href="#" data-type="text" data-pk="1" data-name="id_number"
                                        class="editable editable-click editable-open"
                                        >{{$data_employee->id_number}}</a>
                                        @else{{$data_employee->id_number }} @endif
                                        </td>

                                        

                                        

                                        <td>@if(in_array('reference',$error_data))<a href="#" data-type="text" data-pk="1" data-name="reference"
                                        class="editable editable-click editable-open reference"
                                        >{{$data_employee->reference}}</a>
                                        @else{{$data_employee->reference }} @endif
                                        </td>

                                        <td>

                                            <div class="float-left">
                                                
                                                <!-- <a href="{{ url('merchant/temp/employees/save/'.encrypt($employee->id))}}" class="btn btn-common">Save</a> -->
                                                <button type="button" class="btn btn-common" onclick="saveAction(this);">Save</button>
                                                <form method="POST" action="{{ url('merchant/temp/creditors/delete/'.encrypt($employee->id))}}" class="btn bg-transparent">
                                                    {{ csrf_field() }}
                                                    {{ method_field('DELETE') }}

                                                    <button type="submit" onclick="return confirm('Are you sure to delete')" class="btn bg-transparent"><i class="lni-trash" aria-hidden="true"></i></button>
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

<script src="{{ asset('js/bootstrap-editable.min.js') }}"></script>
<script src="{{ asset('js/bootstrap-editable.js') }}"></script>
<script type="text/javascript">
    $.fn.editable.defaults.mode = 'inline';
    var account_type='{{ Helper::getAccountType() }}';
    account_type=JSON.parse(account_type.replace(/&quot;/g,'"'));
    var bank = '{{ Helper::getBankDetails() }}';
    var bank = JSON.parse(bank.replace(/&quot;/g,'"'));
    var bank_name = [];
    var selectedRow = [];
    var selectedTransactions=[];
    $.each(bank,function(key,value){
        bank_name.push(value.bank_name);
    });
      
    $(document).ready(function() {
        $('.account_type').editable({
            'mode'  : 'inline',
            'source': function() {
                return account_type;
            },
        });
        $('.editable').editable();
           
        $('.bank_select').editable({
                'mode'  : 'inline',
                'source': function() {
                    return bank_name;
                },
        });
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
            }
        });
    });

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
            url    : '{{ url("merchant/creditors/save-multiple/temp") }}',
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
            url    : '{{ url("merchant/creditors/save/temp") }}',
            method : 'post',
            data   : { data : dataString },
            success : function(res) {
                if(jQuery.isEmptyObject(res.errors)){
                    $('.errorhtml').html('');
                    window.location.reload(); 
                }else{
                    var errorhtml = "<div class='alert alert-danger'><strong>Following Error occured!!</strong><ul>";
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

    function getData(tableRow){
        var data={
                'id' : $(tableRow).find("td").eq(0).find("[name='id']").val(),
                'first_name'        : retriveData($(tableRow),1),
                'last_name'         : retriveData($(tableRow),2),
                'salary'            : retriveData($(tableRow),3),
                'account_holder_name': retriveData($(tableRow),4),
                'bank_name'         : retriveData($(tableRow),5),
                'account_type'      : retriveData($(tableRow),6),
                'branch_code'       : retriveData($(tableRow),7),
                'account_number'    : retriveData($(tableRow),8),
                'email'             : retriveData($(tableRow),9),
                'address'           : retriveData($(tableRow),10),
                'contact_number'    : retriveData($(tableRow),11),
                'id_number'         : retriveData($(tableRow),12),
                'reference'         : retriveData($(tableRow),13),
        };
        return data;
    }

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
</script>
@endsection