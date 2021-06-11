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
                    <form method="POST" action="{{url('merchant/customers/transaction/bulk/approve')}}" class="btn bg-transparent">
                        {{csrf_field()}}
                        {{method_field('POST')}}
                        <div id="feildsToApprove">
                            
                        </div>
                        <button id="approveAllFormBtn" type="submit" disabled="disabled" onclick="return confirm('Please be aware that on confirmation this customer debit order will be queued for debiting on the specified debit date. Do you confirm?')" class="btn btn-common mr-3" class="btn btn-common mr-3">Approve</button>
                    </form>
                    <form method="POST" action="{{ url('merchant/customers/transaction/approve/all')}}" class="btn bg-transparent">
                        {{csrf_field()}}
                        {{method_field('POST')}}
                        <button id="approveAllBtn" disabled="disabled" type="submit" onclick="return confirm('Please be aware that on confirmation this customer debit order will be queued for debiting on the specified debit date. Do you confirm?')" class="btn btn-common mr-3"></i> Process to Approve All</button>
                    </form>
                </div>
                <div class="card-body">
                    @include('elements.message')
                    <div class="table-responsive">
                        <table id="customers-datatable" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" name="checkallCustomers" id="checkallCustomers" class="">
                                    </th>
                                    <th>Reference</th>
                                    <th>Name</th>
                                    <th>Surname</th>
                                    <th>Account Number</th>
                                    <th>Account Branch</th>
                                    <th>Payment Date</th>
                                    <th>Recurring Amount</th>
                                    <th>OnceOff Amount</th>
                                    <th>Service Type</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            
                        </table>
                    </div>
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
<script src="{{ asset('plugins/datatables/pdfmake.min.js') }}"></script>
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
var myCustomerTransactionTable="";
var selectedTransactions=[];
function showCheckbox(data, type, row, meta){
    var checkedStr="";
    if(selectedTransactions.indexOf(data)!==-1){

        checkedStr="checked='checked'";
    }
    var format='<input type="checkbox" onclick="transactionClicked(this);" name="checkUsers[]" id="checkUsers_'+data+'" value="'+data+'" class="transactionCheckboxes" '+checkedStr+'>';
   return format;
}
function generateAction(data, type, row, meta){

    //var str ='<button type="button" class="btn btn-common" onclick="saveAction(this);">Save</button>'
    var str ='<button type="button" onclick="updateRecord(\''+data+'\',this);" class="btn btn-common">Update</button>'
    return str;
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
function updateRecord(dataId,elem){

        var tableRow         = $(elem).parents("tr");
        var once_off_amount  = retriveData($(tableRow),8);
        var recurring_amount = retriveData($(tableRow),7);
        $.ajax({
                type    : 'POST',
                url     : "{{url('merchant/customers/transaction/update/')}}",
                data    : { id : dataId , once_off_amount : once_off_amount, recurring_amount: recurring_amount },
                success : function(data){
                    data=JSON.parse(data);
                    $.notify('<strong>'+data.message+'</strong>', {
                        'type': data.type,
                        offset: {
                          x:20,
                          y:100,
                        },
                        allow_dismiss: true,
                        newest_on_top: false,
                    });
                },
                error:function(){
                  $.notify('<strong>Something went wrong , Try Again later!</strong>', {
                        'type': "danger",
                        offset: {
                          x:20,
                          y:100,
                        },
                        allow_dismiss: true,
                        newest_on_top: false,
                    });  
                },complete:function(){
                    var info = myCustomerTransactionTable.page.info();
                    if (info.page > 0) {
                        // when we are in the second page or above
                        if (info.recordsTotal-1 > info.page*info.length) {
                            // after removing 1 from the total, there are still more elements
                            // than the previous page capacity 
                            myCustomerTransactionTable.draw( false )
                        } else {
                            // there are less elements, so we navigate to the previous page
                            myCustomerTransactionTable.page( 'previous' ).draw( 'page' )
                        }
                    }else{
                        myCustomerTransactionTable.draw( false )
                    }
                }
            });
    
}
function transactionClicked(elem){
    var elemVal=parseInt(elem.value);
    //var elemVal=elem.value;
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
        $("#checkallCustomers").prop("checked",true);
    }else{
        $("#checkallCustomers").prop("checked",false);
    }
    createFormFeild();
}

function createFormFeild(){
    
    $("#feildsToApprove").html("");

    selectedTransactions.forEach(function(item, index) {
      $("#feildsToApprove").append("<input type='hidden' name='toUpdate[]' value='"+item+"'>");
    });
    if(selectedTransactions.length>0){
        $("#approveAllFormBtn").prop("disabled",false);
    }else{
        $("#approveAllFormBtn").prop("disabled",true);
    }

}
$(document).ready(function() {
    $("#checkallCustomers").click(function(){
        if($(this).prop("checked") == true){
            $(".transactionCheckboxes").each(function() {
                $( this ).prop("checked",true);
                var elemVal=parseInt(this.value);
                //var elemVal=this.value;
                var arrIndex=selectedTransactions.indexOf(elemVal);
                if(arrIndex===-1){
                    selectedTransactions.push(elemVal);
                }
            });
        }else if($(this).prop("checked") == false){
            $(".transactionCheckboxes").each(function() {
                $( this ).prop("checked",false);
                var elemVal=parseInt(this.value);
                //var elemVal=this.value;
                var arrIndex=selectedTransactions.indexOf(elemVal);
                if(arrIndex!==-1){
                    selectedTransactions.splice(arrIndex, 1);
                }
            });
        }
        createFormFeild();
    });

    myCustomerTransactionTable=$('#customers-datatable').DataTable( {
        "lengthMenu": [ [25, 50, 100,200,500,1000, -1], [25, 50, 100,200,500,1000, "All"] ],
        serverSide: true,
        searching: true,
        ajax: {
            url: "{{ url('merchant/customers/transaction/listing/ajax')}}",
        },
        //dom: "rtiS",
        "columns": [
            {
                "data":0,
                "render": showCheckbox
            },
            {"data":1},
            {"data":2},
            {"data":3},
            {"data":4},
            {"data":5},
            {"data":6},
            {"data":7},
            {"data":8},
            {"data":9},
            {
                "data"   : 10,
                "render" : generateAction
            },
        ],
        columnDefs: [ 
            { orderable: false, targets: [0,10] },
            { searchable: false, targets: [0,10] }
        ],
        "order": [[1, 'asc']],
        deferRender: true,
        "fnDrawCallback": function( oSettings ) {
            if($("[class='transactionCheckboxes']:not(:checked)").length===0){
                $("#checkallCustomers").prop("checked",true);
            }else{
                $("#checkallCustomers").prop("checked",false);
            }
            $('.editable').editable();
            var total = oSettings._iRecordsTotal;
            if(total==0){
                $("#approveAllBtn").prop("disabled",true);
            }else{
                $("#approveAllBtn").prop("disabled",false);
            }
        }
        // scrollY: 800,
        // scrollCollapse: true
    } );

    //when typing starts in searchbox
    $('#customers-datatable_filter input').unbind('keypress keyup').bind('keypress keyup', function(e){
        $("#checkallCustomers").prop("checked",false);
        myCustomerTransactionTable.fnFilter($(this).val());
    });
});
</script>
@endsection