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
                        <table id="firms-datatable" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Transactions</th>
                                    <th>Amount</th>
                                    <th>Recieving Time</th>
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
var myFirmTable="";
var selectedFirms=[];

function generateAction(data, type, row, meta){

    var str='<div class="float-left"><a href="{{url('admin/outputs/download-file/')}}/'+data+'" class="btn bg-transparent" data-toggle="tooltip" data-placement="top" data-original-title="Download"><i class="lni-download" aria-hidden="true"></i></a><a data-toggle="tooltip" data-placement="top" title="" data-original-title="View" href="{{url('admin/outputs/payment-transaction/')}}/'+data+'" class="btn bg-transparent"><i class="lni-eye" aria-hidden="true"></i></a></div>';
    
    return str;
}


$(document).ready(function() {
    myFirmTable=$('#firms-datatable').DataTable({
        "lengthMenu": [ [25, 50, 100,200,500,1000, -1], [25, 50, 100,200,500,1000, "All"] ],
        serverSide: true,
        searching: true,
        ajax: {
            url: "{{ url('admin/outputs/ajax-payment-listing')}}"
        },
        //dom: "rtiS",
        "columns": [
            
            {"data":0},
            {"data":1},
            {"data":2},
            {
                "data":3,
                "render": generateAction
            },
        ],
        columnDefs: [ 
            { orderable: false, targets: [3] },
            { searchable: false, targets: [3] }
        ],
        "order": [],
        deferRender: true,
        "fnDrawCallback": function( oSettings ) {
            $('[data-toggle="tooltip"]').tooltip();
        }
        // scrollY: 800,
        // scrollCollapse: true
    });

    //when typing starts in searchbox
    $('#firms-datatable_filter input').unbind('keypress keyup').bind('keypress keyup', function(e){
        myFirmTable.search($(this).val());
    });
    
    
});
</script>
@endsection