@extends('layouts.app') 

@section('extra_style')
    <!-- DataTables -->
    <link href="{{ asset('plugins/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('plugins/datatables/buttons.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Responsive datatable examples -->
    <link href="{{ asset('plugins/datatables/responsive.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" /> 
@endsection 

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom">
                    
                    <form method="POST" action="{{url('merchant/creditors/mul-statusupdate')}}" class="btn bg-transparent" id="approveStatusForm">
                        {{csrf_field()}}
                        {{method_field('POST')}}
                        <input type="hidden" name="actionType" value="approve"/>
                        <div id="feildsToApprove">
                            
                        </div>
                        <button id="approveAllFormBtn" type="button" disabled="disabled" onclick=" confirmFormSubmit('Are you sure to approve','approveStatusForm')" class="btn btn-common mr-3"><i class="lni-thumbs-up" aria-hidden="true"></i> Approve Selected</button>
                    </form>
                
                    <form method="POST" action="{{url('merchant/creditors/mul-statusupdate')}}" class="btn bg-transparent"  id="rejectStatusForm">
                        {{csrf_field()}}
                        {{method_field('POST')}}
                        <input type="hidden" name="actionType" value="reject"/>
                        <div id="feildsToReject">
                            
                        </div>
                        <button id="rejectAllFormBtn" type="button" disabled="disabled" onclick=" confirmFormSubmit('Are you sure to reject','rejectStatusForm')" class="btn btn-common mr-3"><i class="lni-thumbs-down" aria-hidden="true"></i> Reject Selected</button>
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
                                    <th>Creditor No.</th>
                                    <th>Name</th>
                                    <th>Surname</th>
                                    <th>Contact</th>
                                    <th>Bank Name</th>
                                    <th>Branch</th>
                                    <th>Account Number</th>
                                    <th>Amount</th>
                                    <th>Status</th>
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
<script type="text/javascript">
var myCustomerTable="";
var selectedCustomers=[];

function confirmFormSubmit(message,formId){
    confirmDialog(message, (ans) => {
      if (ans) {
        $("#"+formId).submit();
        }
     });
}


function showCheckbox(data, type, row, meta){
    var checkedStr="";
    if(selectedCustomers.indexOf(data)!==-1){

        checkedStr="checked='checked'";
    }
    var format='<input type="checkbox" onclick="customerClicked(this);" name="checkUsers[]" id="checkUsers_'+data+'" value="'+data+'" class="customerCheckboxes" '+checkedStr+'>';
   return format;
}
function generateAction(data, type, row, meta){
    var str="";
    str+='<div class="float-left"><a href="{{url('merchant/creditors/pendingupdate/')}}/'+data+'" class="m-1 bg-transparent" data-toggle="tooltip" data-placement="top" data-original-title="Edit"><i class="lni-pencil" aria-hidden="true"></i></a>';
    str+='<a href="{{url('merchant/creditors/pendingview/')}}/'+data+'" class="m-1 bg-transparent"  data-toggle="tooltip" data-placement="top" data-original-title="View Details"><i class="lni-eye" aria-hidden="true"></i></a>';
    //str+='<button type="button" onclick="takeAction(\''+data+'\',\'approve\');" class="btn btn-common mr-2">Approve</button>';
    //str+='<button type="button" onclick="takeAction(\''+data+'\',\'reject\');" class="btn btn-common mr-2">Reject</button>';
    str+='<a href="javascript:void(0);" onclick="takeAction(\''+data+'\',\'approve\');" class="m-1 bg-transparent"  data-toggle="tooltip" data-placement="top" data-original-title="Approve"><i class="lni-thumbs-up" aria-hidden="true"></i></a>';
    str+='<a href="javascript:void(0);" onclick="takeAction(\''+data+'\',\'reject\');" class="m-1 bg-transparent" data-toggle="tooltip" data-placement="top" data-original-title="Reject"><i class="lni-thumbs-down" aria-hidden="true"></i></a></div>';
    return str;
}

function takeAction(dataId,actionType){
    //var con=confirm("Are you sure to "+actionType+" this record?");
    confirmDialog("Are you sure to "+actionType+" this record?", (ans) => {
      if (ans) {
         $.ajax({
                
                type  : 'post',
                url   : "{{url('merchant/creditors/statusupdate')}}",
                data  : 'employeeId='+dataId+'&action='+actionType,
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
                error:function(requestObject, error, errorThrown){
                    
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
                    var info = myCustomerTable.page.info();
                    if (info.page > 0) {
                        // when we are in the second page or above
                        if (info.recordsTotal-1 > info.page*info.length) {
                            // after removing 1 from the total, there are still more elements
                            // than the previous page capacity 
                            myCustomerTable.draw( false )
                        } else {
                            // there are less elements, so we navigate to the previous page
                            myCustomerTable.page( 'previous' ).draw( 'page' )
                        }
                    }else{
                        myCustomerTable.draw( false )
                    }
                }
            });
      }
     });    
    // if(con){
        
    // }
}
function customerClicked(elem){
    //var elemVal=parseInt(elem.value);
    var elemVal=elem.value;
    var arrIndex=selectedCustomers.indexOf(elemVal);
    if($(elem).prop("checked") == true){
        if(arrIndex===-1){
            
            selectedCustomers.push(elemVal);
        }
    }else if($(elem).prop("checked") == false){
        if(arrIndex!==-1){
            selectedCustomers.splice(arrIndex, 1);
        }
    }

    if($("[class='customerCheckboxes']:not(:checked)").length===0){
        $("#checkallCustomers").prop("checked",true);
    }else{
        $("#checkallCustomers").prop("checked",false);
    }
    createFormFeild();
}

function createFormFeild(){
    
    $("#feildsToApprove").html("");
    $("#feildsToReject").html("");

    selectedCustomers.forEach(function(item, index) {
        $("#feildsToApprove").append("<input type='hidden' name='toApprove[]' value='"+item+"'>");
        $("#feildsToReject").append("<input type='hidden' name='toReject[]' value='"+item+"'>");
    });
    if(selectedCustomers.length>0){
        $("#rejectAllFormBtn").prop("disabled",false);
        $("#approveAllFormBtn").prop("disabled",false);
    }else{
        $("#rejectAllFormBtn").prop("disabled",true);
        $("#approveAllFormBtn").prop("disabled",true);
    }

}
$(document).ready(function() {

    $("#checkallCustomers").click(function(){
        if($(this).prop("checked") == true){
            $(".customerCheckboxes").each(function() {
                $( this ).prop("checked",true);
                
                var elemVal=this.value;
                var arrIndex=selectedCustomers.indexOf(elemVal);
                if(arrIndex===-1){
                    selectedCustomers.push(elemVal);
                }
            });
        }else if($(this).prop("checked") == false){
            $(".customerCheckboxes").each(function() {
                $( this ).prop("checked",false);
                //var elemVal=parseInt(this.value);
                var elemVal=this.value;
                var arrIndex=selectedCustomers.indexOf(elemVal);
                if(arrIndex!==-1){
                    selectedCustomers.splice(arrIndex, 1);
                }
            });
        }
        createFormFeild();
    });

    myCustomerTable=$('#customers-datatable').DataTable( {
        "lengthMenu": [ [25, 50, 100,200,500,1000, -1], [25, 50, 100,200,500,1000, "All"] ],
        serverSide: true,
        searching: true,
        ajax: {
            url: "{{ url('merchant/creditors/ajax-pendinglist')}}"
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
                "data":10,
                "render": generateAction
            }
        ],
        columnDefs: [ 
            { orderable: false, targets: [0,9,10] },
            { searchable: false, targets: [0,9,10] }
        ],
        "order": [[1, 'asc']],
        deferRender: true,
        "fnDrawCallback": function( oSettings ) {
            if($("[class='customerCheckboxes']:not(:checked)").length===0){
                $("#checkallCustomers").prop("checked",true);
            }else{
                $("#checkallCustomers").prop("checked",false);
            }
            $('[data-toggle="tooltip"]').tooltip();
        }
        // scrollY: 800,
        // scrollCollapse: true
    });

    //when typing starts in searchbox
    $('#customers-datatable_filter input').unbind('keypress keyup').bind('keypress keyup', function(e){
        $("#checkallCustomers").prop("checked",false);
        myCustomerTable.fnFilter($(this).val());
    });
    
    
});
</script>
@endsection