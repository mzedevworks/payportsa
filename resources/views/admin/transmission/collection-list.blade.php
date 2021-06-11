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
                                    <th>Date</th>
                                    <th>Transmission</th>
                                    <th>Reply</th>
                                    <th>Records</th>
                                    <th>Amount</th>
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
var myFirmTable="";
var selectedFirms=[];

function tranxDownload(data, type, row, meta){
    var str='';
    if(data.file_path!=null){
        str='<div class="float-left"><a href="{{url('admin/transmission/collection-trax/')}}/'+data.id+'" class="btn bg-transparent" data-toggle="tooltip" data-placement="top" data-original-title="Download"><i class="lni-download" aria-hidden="true"></i></a></div>';
    }
    return str;
}

function replyDownload(data, type, row, meta){
    var str='';
    if(data.reply_file!=null){
        str='<div class="float-left"><a href="{{url('admin/transmission/collection-reply/')}}/'+data.id+'" class="btn bg-transparent" data-toggle="tooltip" data-placement="top" data-original-title="Download"><i class="lni-download" aria-hidden="true"></i></a></div>';
    }
    
    
    return str;
}

function viewTransaction(data, type, row, meta){
    var str='';
        str='<div class="float-left"><a href="{{url('admin/transmission/collection-transactions/')}}/'+data.id+'" class="btn bg-transparent" data-toggle="tooltip" data-placement="top" data-original-title="View">'+data.trnx_count+'</a></div>';
    
    
    
    return str;
}


$(document).ready(function() {
    myFirmTable=$('#firms-datatable').DataTable({
        "lengthMenu": [ [25, 50, 100,200,500,1000, -1], [25, 50, 100,200,500,1000, "All"] ],
        serverSide: true,
        searching: true,
        ajax: {
            url: "{{ url('admin/transmission/ajax-collection-listing')}}"
        },
        //dom: "rtiS",
        "columns": [
            
            {"data":0},
            {
                "data":1,
                "render": tranxDownload
            },
            {
                "data":2,
                "render": replyDownload
            },
            {
                "data":3,
                "render": viewTransaction
            },
            {
                "data":4
            },
            {
                "data":5
            }
        ],
        columnDefs: [ 
            { orderable: false, targets: [1,2,3,4] },
            { searchable: false, targets: [1,2,3,4] }
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