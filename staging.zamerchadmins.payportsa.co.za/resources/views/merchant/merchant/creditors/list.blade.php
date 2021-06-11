<!-- @extends('layouts.app')  -->

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
                    <a href="{{ url('merchant/creditors/create')}}" class="btn btn-common">
                      Add Creditor</a>
                      OR
                      <a href="{{ url('merchant/creditors/temp/list')}}" class="btn btn-common">
                      Upload CSV</a>
                    <!-- <form method="POST" action="{{url('merchant/creditors/deletemultiple')}}" class="btn bg-transparent">
                        {{csrf_field()}}
                        {{method_field('DELETE')}}
                        <div id="feildsToDelete">
                            
                        </div>
                        <button id="deleteAllFormBtn" type="submit" disabled="disabled" onclick="return confirm('Are you sure to delete')" class="btn btn-common mr-3"><i class="lni-trash" aria-hidden="true"></i> Delete</button>
                    </form> -->
                </div>
                <div class="card-body">
                    @include('elements.message')
                    <div class="table-responsive">
                        <table id="employees-datatable" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Creditor Id</th>
                                    <th>First Name</th>
                                    <th>Last Name</th>
                                    <th>Email</th>
                                    <th>Contact Number</th>
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
var myEmployeeTable="";
var selectedEmployees=[];
function showCheckbox(data, type, row, meta){
    var checkedStr="";
    if(selectedEmployees.indexOf(data)!==-1){

        checkedStr="checked='checked'";
    }
    var format='<input type="checkbox" onclick="customerClicked(this);" name="checkUsers[]" id="checkUsers_'+data+'" value="'+data+'" class="customerCheckboxes" '+checkedStr+'>';
   return format;
}
function generateAction(data, type, row, meta){

    var str='<div class="float-left"><a href="{{url('merchant/creditors/update/')}}/'+data+'" class="btn bg-transparent" data-toggle="tooltip" data-placement="top" data-original-title="Edit"><i class="lni-pencil" aria-hidden="true"></i></a></div>';
    str+='<a href="{{url('merchant/creditors/view/')}}/'+data+'" class="m-1 bg-transparent"  data-toggle="tooltip" data-placement="top" data-original-title="View Details"><i class="lni-eye" aria-hidden="true"></i></a>';
    //str+='<button type="button" onclick="deleteRecord(\''+data+'\',this);" class="btn bg-transparent"><i class="lni-trash" aria-hidden="true"></i></button>'
    str+='<a href="javascript:void(0);" onclick="takeAction(\''+data+'\',\'active\');" class="m-1 bg-transparent" data-toggle="tooltip" data-placement="top" data-original-title="Approve"><i class="lni-thumbs-up" aria-hidden="true"></i></a>';
    str+='<a href="javascript:void(0);" onclick="takeAction(\''+data+'\',\'in-active\');" class="m-1 bg-transparent" data-toggle="tooltip" data-placement="top" data-original-title="Reject"><i class="lni-thumbs-down" aria-hidden="true"></i></a></div>';
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
                    var info = myEmployeeTable.page.info();
                    if (info.page > 0) {
                        // when we are in the second page or above
                        if (info.recordsTotal-1 > info.page*info.length) {
                            // after removing 1 from the total, there are still more elements
                            // than the previous page capacity 
                            myEmployeeTable.draw( false )
                        } else {
                            // there are less elements, so we navigate to the previous page
                            myEmployeeTable.page( 'previous' ).draw( 'page' )
                        }
                    }else{
                        myEmployeeTable.draw( false )
                    }
                }
            });
      }
     });    
    // if(con){
        
    // }
}



$(document).ready(function() {

    

    myEmployeeTable=$('#employees-datatable').DataTable( {
        serverSide: true,
        searching: true,
        ajax: {
            url: "{{ url('merchant/creditors/listing/ajax')}}"
        },
        //dom: "rtiS",
        "columns": [
            {"data":0},
            {"data":1},
            {"data":2},
            {"data":3},
            {"data":4},
            {"data":5},
            {"data":6},
            {
                "data":7,
                "render": generateAction
            },
        ],
        columnDefs: [ 
            { orderable: false, targets: [7] },
            { searchable: false, targets: [7] }
        ],
        "order": [[1, 'asc']],
        deferRender: true,
        "fnDrawCallback": function( oSettings ) {
            $('[data-toggle="tooltip"]').tooltip();
        }
        // scrollY: 800,
        // scrollCollapse: true
    } );

    //when typing starts in searchbox
    $('#employees-datatable_filter input').unbind('keypress keyup').bind('keypress keyup', function(e){
        myEmployeeTable.search($(this).val()).draw();
    });
    
    
});
</script>
@endsection