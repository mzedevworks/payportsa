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
                    <a href="{{ url('merchant/customer/create')}}" class="btn btn-common">
                      Add Customer</a>

                    <form method="POST" action="{{url('merchant/customers/deletemultiple')}}" class="btn bg-transparent">
                        {{csrf_field()}}
                        {{method_field('DELETE')}}
                        <div id="feildsToDelete">
                            
                        </div>
                        <button id="deleteAllFormBtn" type="submit" disabled="disabled" onclick="return confirm('Are you sure to delete')" class="btn btn-common mr-3"><i class="lni-trash" aria-hidden="true"></i> Delete</button>
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
                                    <th>Contact</th>
                                    <th>OnceOff Amount</th>
                                    <th>Payment Date</th>
                                    <th>Bank Name</th>
                                    <th>Branch</th>
                                    <th>Account Number</th>
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
function showCheckbox(data, type, row, meta){
    var checkedStr="";
    if(selectedCustomers.indexOf(data)!==-1){

        checkedStr="checked='checked'";
    }
    var format='<input type="checkbox" onclick="customerClicked(this);" name="checkUsers[]" id="checkUsers_'+data+'" value="'+data+'" class="customerCheckboxes" '+checkedStr+'>';
   return format;
}
function generateAction(data, type, row, meta){

    var str='<div class="float-left"><a href="{{url('merchant/customers/update/')}}/'+data+'" class="btn bg-transparent" data-toggle="tooltip" data-placement="top" data-original-title="Edit"><i class="lni-pencil" aria-hidden="true"></i></a></div>';
    str+='<button type="button" onclick="deleteRecord(\''+data+'\',this);" class="btn bg-transparent" data-toggle="tooltip" data-placement="top" data-original-title="Delete"><i class="lni-trash" aria-hidden="true"></i></button>'
    return str;
}

function deleteRecord(dataId,elem){
    var con=confirm("Are you sure to delete this record?");
    
    if(con){
        $.ajax({
                
                type  : 'DELETE',
                url   : "{{url('merchant/customers/delete')}}",
                data  : 'customerId='+dataId,
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
}
function customerClicked(elem){
    var elemVal=parseInt(elem.value);
    //var elemVal=elem.value;
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
    
    $("#feildsToDelete").html("");

    selectedCustomers.forEach(function(item, index) {
      $("#feildsToDelete").append("<input type='hidden' name='toDelete[]' value='"+item+"'>");
    });
    if(selectedCustomers.length>0){
        $("#deleteAllFormBtn").prop("disabled",false);
    }else{
        $("#deleteAllFormBtn").prop("disabled",true);
    }

}
$(document).ready(function() {

    $("#checkallCustomers").click(function(){
        if($(this).prop("checked") == true){
            $(".customerCheckboxes").each(function() {
                $( this ).prop("checked",true);
                var elemVal=parseInt(this.value);
                //var elemVal=this.value;
                var arrIndex=selectedCustomers.indexOf(elemVal);
                if(arrIndex===-1){
                    selectedCustomers.push(elemVal);
                }
            });
        }else if($(this).prop("checked") == false){
            $(".customerCheckboxes").each(function() {
                $( this ).prop("checked",false);
                var elemVal=parseInt(this.value);
                //var elemVal=this.value;
                var arrIndex=selectedCustomers.indexOf(elemVal);
                if(arrIndex!==-1){
                    selectedCustomers.splice(arrIndex, 1);
                }
            });
        }
        createFormFeild();
    });

    myCustomerTable=$('#customers-datatable').DataTable( {
        serverSide: true,
        searching: true,
        ajax: {
            url: "{{ url('merchant/customers/listing/ajax')}}"
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
            },
        ],
        columnDefs: [ 
            { orderable: false, targets: [0,10] },
            { searchable: false, targets: [0,10] }
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
    } );

    //when typing starts in searchbox
    $('#customers-datatable_filter input').unbind('keypress keyup').bind('keypress keyup', function(e){
        $("#checkallCustomers").prop("checked",false);
        myCustomerTable.fnFilter($(this).val());
    });
    
    
});
</script>
@endsection