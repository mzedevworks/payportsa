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
                                    <th>Merchant</th>
                                    <th>Action Date</th>
                                    <th>Created On</th>
                                    <th>Amount</th>
                                    <th>Transactions</th>
                                    <th>Status</th>
                                    
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



function formatBatchName(data, type, row, meta){

    var str='<a href="{{url('admin/batch-collection/reoccur/processed/')}}/'+data.id+'">'+data.batch_name+'</a>';
    
    return str;
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
            url: "{{ url('admin/batch-collection/reoccur/ajax-processed')}}"
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
            {"data":6},
            
        ],
        columnDefs: [ 
            { orderable: false, targets: [4,5] },
            { searchable: false, targets: [4,5] },
        ],
        "order": [],
        deferRender: true,
        "fnDrawCallback": function( oSettings ) {
            
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