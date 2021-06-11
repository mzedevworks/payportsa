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
      <div class="col-12 grid-margin">
        <div class="card">
          
          <div class="card-body">
              
            <div class="tab-info">
              <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                  <a href="{{url('merchant/creditors/view/').'/'.Helper::encryptVar($creditorRes['id'])}}" class="nav-link">Creditor Info</a>
                </li>
                <li class="nav-item">
                  <a href="{{url('merchant/creditors/transactions/').'/'.Helper::encryptVar($creditorRes['id'])}}" class="nav-link active">History</a>
                </li>
                
              </ul>
              <div class="tab-content">
                <div role="tabpanel" class="tab-pane fade" id="default-tab-1">
                  
                </div>
                <div role="tabpanel" class="tab-pane active" id="default-tab-2">
                  <div class="p-20">
                    
                    <div class="table-responsive">
                        <table id="customers-datatable" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Account Holder</th>
                                    <th>Account Number</th>
                                    <th>Account Type</th>
                                    <th>Action Date</th>
                                    <th>Status</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            
                        </table>
                    </div>

                  </div>
                </div>
                
              </div>
            </div>




              





          </div>
        </div>
      </div>
    </div>
  </div>
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
$(document).ready(function() {

    myCustomerTable=$('#customers-datatable').DataTable( {
        "lengthMenu": [ [25, 50, 100,200,500,1000, -1], [25, 50, 100,200,500,1000, "All"] ],
        serverSide: true,
        searching: true,
        ajax: {
            url: "{{ url('merchant/creditors/transactions/ajax-tranx/'.Helper::encryptVar($creditorId))}}"
        },
        //dom: "rtiS",
        "columns": [
            {"data":0},
            {"data":1},
            {"data":2},
            {"data":3},
            {"data":4},
            {"data":5},
        ],
        columnDefs: [ 
            { orderable: false, targets: [] },
            { searchable: false, targets: [] }
        ],
        "order": [[0, 'asc']],
        deferRender: true,
        "fnDrawCallback": function( oSettings ) {
            
        }
        // scrollY: 800,
        // scrollCollapse: true
    } );

    //when typing starts in searchbox
    $('#customers-datatable_filter input').unbind('keypress keyup').bind('keypress keyup', function(e){
        
        myCustomerTable.search($(this).val());
    });
    
    
});
</script>
@endsection