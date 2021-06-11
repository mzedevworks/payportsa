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
                    
                </div>
                <div class="card-body">
                    @include('elements.message')
                    <div class="table-responsive">
                        <table id="customers-datatable" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Batch Name</th>
                                    <th>Service Type</th>
                                    <th>Action Date</th>
                                    <th>Created On</th>
                                    <th>amount</th>
                                    <th>Transactions</th>
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

function generateAction(data, type, row, meta){

    
    var str='<div class=""><a href="{{url('merchant/collection/normalbatch/pending-transmission/transaction/')}}/'+data+'" class="m-1 bg-transparent" data-toggle="tooltip" data-placement="top" data-original-title="Edit"><i class="lni-pencil" aria-hidden="true"></i></a>';
    
    str+='<a href="javascript:void(0);" onclick="takeAction(\''+data+'\',\'approve\');" class="m-1 bg-transparent" data-toggle="tooltip" data-placement="top" data-original-title="Approve"><i class="lni-thumbs-up" aria-hidden="true"></i></a>';
    str+='<a href="javascript:void(0);" onclick="takeAction(\''+data+'\',\'cancel\');" class="m-1 bg-transparent" data-toggle="tooltip" data-placement="top" data-original-title="Reject"><i class="lni-thumbs-down" aria-hidden="true"></i></a></div>';
    return str;
    
}

function formatBatchName(data, type, row, meta){

    var str='<a href="{{url('merchant/collection/normalbatch/pending-transmission/transaction/')}}/'+data.id+'">'+data.batch_name+'</a>';
    
    return str;
}

function takeAction(dataId,actionType){
    confirmDialog("Are you sure to "+actionType+" this record?", (ans) => {
        if (ans) {
            $.ajax({
                
                type  : 'post',
                url   : "{{url('merchant/collection/normalbatch/statusupdate')}}",
                data  : 'batchId='+dataId+'&action='+actionType,
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
    
}

$(document).ready(function() {

    

    myCustomerTable=$('#customers-datatable').DataTable( {
        "lengthMenu": [ [25, 50, 100,200,500,1000, -1], [25, 50, 100,200,500,1000, "All"] ],
        serverSide: true,
        searching: true,
        dom: "<'row'<'col-sm-12 text-right p-0 mb-2'B><'col-sm-6'l><'col-sm-6'f>>" +"<'row'<'col-sm-12'tr>>" +"<'row'<'col-sm-5'i><'col-sm-7'p>>",
        buttons: [
            //'copy', 'excel', 'pdf', 'print'
            {
                    extend: 'excelHtml5',
                    text: 'Export to Excel',
                    filename:"pending-batches",
                    action: newExportAction
                }
        ],
        ajax: {
            url: "{{ url('merchant/collection/normalbatch/ajax-approval-list')}}"
        },
        //dom: "rtiS",
        "columns": [
            {
                "data":0,
                "render": formatBatchName
            },
            {"data":1},
            {"data":2},
            {"data":3},
            {"data":4},
            {"data":5},
            {
                "data":6,
                "render": generateAction
            }
            
        ],
        columnDefs: [ 
            { orderable: false, targets: [4,5,6] },
            { searchable: false, targets: [4,5,6] }
        ],
        "order": [],
        deferRender: true,
        "fnDrawCallback": function( oSettings ) {
            $('[data-toggle="tooltip"]').tooltip();
        }
        // scrollY: 800,
        // scrollCollapse: true
    } );

    //when typing starts in searchbox
    $('#customers-datatable_filter input').unbind('keypress keyup').bind('keypress keyup', function(e){
        
        myCustomerTable.fnFilter($(this).val());
    });
    
    
});
</script>
@endsection