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
                    
                    <form method="POST" action="{{url('merchant/collection/normal/savebatch')}}" class="btn bg-transparent" id="approveStatusForm" autocomplete="off">
                        {{csrf_field()}}
                        {{method_field('POST')}}
                        <input type="hidden" name="actionType" value="approve"/>
                        <input type="hidden" name="batch_name" value="{{$postData['batch_name']}}"/>
                        <input type="hidden" name="service_type" value="{{$postData['service_type']}}"/>
                        <input type="hidden" name="collection_date" value="{{$postData['collection_date']}}"/>
                        <input type="hidden" name="customer_selection" value="{{$postData['customer_selection']}}"/>
                        <div id="batchContainerDiv">
                            
                        </div>
                        <button id="createBatchFormBtn" type="button" disabled onclick=" confirmFormSubmit('Are you sure?','approveStatusForm')" class="btn btn-common mr-3"><i class="lni-thumbs-up" aria-hidden="true"></i> Create Batch</button>
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
                                    <th>Mandate Id</th>
                                    <th>Name</th>
                                    <th>Surname</th>
                                    <th>Amount</th>
                                    <th>Refrence</th>
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
var selectedAmount=[];
var selectedReffrence=[];
var totalBatchAmount=0;
function confirmFormSubmit(message,formId){
    if($("#customerList").length>0){
        $("#customerList").val(JSON.stringify(selectedCustomers));
    }else{
        $("#batchContainerDiv").append("<input type='hidden' id='customerList' name='customerList' value='"+JSON.stringify(selectedCustomers)+"'>");
    }
    

    if($("#customerAmount").length>0){
        $("#customerAmount").val(JSON.stringify(selectedAmount));
    }else{
        $("#batchContainerDiv").append("<input type='hidden' id='customerAmount' name='customerAmount' value='"+JSON.stringify(selectedAmount)+"'>");
    }

    if($("#customerReff").length>0){
        $("#customerReff").val(JSON.stringify(selectedReffrence));
    }else{
        $("#batchContainerDiv").append("<input type='hidden' id='customerReff'name='customerReff' value='"+JSON.stringify(selectedReffrence)+"'>");
    }

    if($("#salaryBatchAmount").length>0){
        $("#salaryBatchAmount").val(parseFloat(totalBatchAmount));
    }else{
        $("#batchContainerDiv").append("<input type='hidden' id='salaryBatchAmount' name='salaryBatchAmount' value='"+parseFloat(totalBatchAmount)+"'>");
    }

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
function createAmountInput(data, type, row, meta){
    var str='<input data-id="'+row[0]+'" id="amount_'+row[0]+'" type="text" class="form-control" placeholder="Amount" name="amount" value="'+data+'" onchange="updateCustomer(this)">';
    
    return str;
}

function createReffrenceInput(data, type, row, meta){
    console.log(data);
    if(data==null || data=="NULL"){
        data="";
    }
    var str='<input data-id="'+row[0]+'" id="refference_'+row[0]+'" type="text" class="form-control" placeholder="refrence" name="reffrence" value="'+data+'"  onchange="updateCustomer(this)">';
    
    return str;
}

function updateCustomer(elem){
    var customerId=$(elem).data('id');
    var elemVal=$("#checkUsers_"+customerId).val();
    var arrIndex=selectedCustomers.indexOf(elemVal);

    if($("#checkUsers_"+customerId).prop("checked") == true){
        
        
        if(arrIndex!==-1){
            totalBatchAmount=parseFloat(totalBatchAmount)-parseFloat(selectedAmount[arrIndex]);
            selectedCustomers[arrIndex]=elemVal;
            selectedAmount[arrIndex]=$("#amount_"+customerId).val();
            selectedReffrence[arrIndex]=$("#refference_"+customerId).val();
            totalBatchAmount=parseFloat(totalBatchAmount)+parseFloat(selectedAmount[arrIndex]);
        }
        
    }
}
function customerClicked(elem){
    //var elemVal=parseInt(elem.value);
    var elemVal=elem.value;
    var arrIndex=selectedCustomers.indexOf(elemVal);
    var empSalary=$("#amount_"+elemVal).val();
    var refferenceStr=$("#refference_"+elemVal).val();
    if($(elem).prop("checked") == true){
        if(arrIndex===-1){
            
            selectedCustomers.push(elemVal);
            selectedAmount.push(empSalary);
            selectedReffrence.push(refferenceStr);
            totalBatchAmount=parseFloat(totalBatchAmount)+parseFloat(empSalary);
        }
    }else if($(elem).prop("checked") == false){
        if(arrIndex!==-1){
            totalBatchAmount=parseFloat(totalBatchAmount)-parseFloat(selectedAmount[arrIndex]);
            selectedCustomers.splice(arrIndex, 1);
            selectedAmount.splice(arrIndex, 1);
            selectedReffrence.splice(arrIndex, 1);
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
    
    $("#batchContainerDiv").html("");
    

    selectedCustomers.forEach(function(item, index) {
        $("#batchContainerDiv").append("<input type='hidden' name='employee[]' value='"+item+"'>");
    });
    if(selectedCustomers.length>0){
        $("#createBatchFormBtn").prop("disabled",false);
    }else{
        $("#createBatchFormBtn").prop("disabled",true);
    }

}
$(document).ready(function() {

    $("#checkallCustomers").click(function(){
        var allChecked=$(this).prop("checked");
        
        $(".customerCheckboxes").each(function() {
            $( this ).prop("checked",allChecked);
            
            var elemVal=this.value;
            var empSalary=$("#amount_"+elemVal).val();
            var refferenceStr=$("#refference_"+elemVal).val();

            var arrIndex=selectedCustomers.indexOf(elemVal);
            if(arrIndex===-1 && allChecked==true){
                selectedCustomers.push(elemVal);
                selectedAmount.push(empSalary);
                selectedReffrence.push(refferenceStr);
                totalBatchAmount=parseFloat(totalBatchAmount)+parseFloat(empSalary);
            }

            if(arrIndex!==-1 && allChecked==false){
                totalBatchAmount=parseFloat(totalBatchAmount)-parseFloat(selectedAmount[arrIndex]);
                selectedCustomers.splice(arrIndex, 1);
                selectedAmount.splice(arrIndex, 1);
                selectedReffrence.splice(arrIndex, 1);
            }
        });
        
        createFormFeild();
    });

    myCustomerTable=$('#customers-datatable').DataTable( {
        "lengthMenu": [ [25, 50, 100,200,500,1000, -1], [25, 50, 100,200,500,1000, "All"] ],
        serverSide: true,
        searching: true,
        ajax: {
            url: "{{ url('merchant/collection/normal/listforbatch')}}"
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
            {
                "data":4,
                "render":createAmountInput
            },
            {
                "data":5,
                "render":createReffrenceInput
            },
            
        ],
        columnDefs: [ 
            { orderable: false, targets: [0,4,5] },
            { searchable: false, targets: [0,4,5] }
        ],
        "order": [[1, 'asc']],
        deferRender: true,
        "fnDrawCallback": function( oSettings ) {
            if($("[class='customerCheckboxes']:not(:checked)").length===0){
                $("#checkallCustomers").prop("checked",true);
            }else{
                $("#checkallCustomers").prop("checked",false);
            }
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