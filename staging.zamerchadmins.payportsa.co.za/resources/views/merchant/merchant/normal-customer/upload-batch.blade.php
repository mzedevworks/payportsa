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
                    
                </div>
                <div class="card-body">
                    
                       <form class="form-sample" method="post" 
                        action="{{ url('merchant/collection/normal/batchimport') }}" enctype="multipart/form-data">
                          @csrf
                          <input type="hidden" name="actionType" value="approve"/>
                        <input type="hidden" name="batch_name" value="{{$postData['batch_name']}}"/>
                        <input type="hidden" name="service_type" value="{{$postData['service_type']}}"/>
                        <input type="hidden" name="collection_date" value="{{$postData['collection_date']}}"/>
                        <input type="hidden" name="customer_selection" value="{{$postData['customer_selection']}}"/>
                         <input type="file" name="file_name">
                         <input type="submit" name="" class="btn btn-common" value="Upload List"></a>
                       </form>
                       <a href="{{ url('merchant/collection/normal/samplebatchcsv')}}" class="purple-btn">Download Sample csv</a>
                    
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
            url    : '{{ url("merchant/employees/save-multiple/temp") }}',
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
            url    : '{{ url("merchant/employees/save/temp") }}',
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