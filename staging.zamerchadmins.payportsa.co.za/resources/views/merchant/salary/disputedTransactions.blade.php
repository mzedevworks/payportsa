@extends('layouts.app') 

@section('extra_style')
<link rel="stylesheet" href="{{ asset('css/jquery-ui.css') }}">
    <!-- DataTables -->
    <link href="{{ asset('plugins/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('plugins/datatables/buttons.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Responsive datatable examples -->
    <link href="{{ asset('plugins/datatables/responsive.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" /> 
@endsection 

@section('content')
@php
$service = Config::get('constants.serviceType');
@endphp
<div class="container-fluid">
  <div class="row">
    <div class="col-12 grid-margin">
      <div class="card">
        <div class="card-header border-bottom">
          <h4 class="card-title">Filters</h4>
        </div>
        
        <div class="card-body">
          <form class="form-sample" method="get" action="{{url('merchant/collection/normal/disputes') }}" autocomplete="off">
            @csrf
            <div class="row">               
              <div class="col-md-4">
                  <div class="form-group row">
                    <label class="col-sm-5 col-form-label">From Date</label>
                    <div class="col-sm-7">
                      <input type="text" class="form-control collectionDate" placeholder="YYYY/MM/DD" id="startat"  name="startat" value="{{old('startat',$request->startat)}}">
                    </div>
                  </div>
              </div>
              <div class="col-md-4">
                  <div class="form-group row">
                    <label class="col-sm-5 col-form-label">Up to Date</label>
                    <div class="col-sm-7">
                      <input type="text" class="form-control collectionDate" placeholder="YYYY/MM/DD" name="upto" id="upto" value="{{old('upto',$request->upto)}}">
                    </div>
                  </div>
              </div>
              
            </div>
            
            <button type="submit" class="btn btn-common mr-3">Search</button>
            
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                
                <div class="card-body">
                    <div class="errorhtml"></div>
                    @include('elements.message')
                    
                   
                    <div class="table-responsive">
                        <table id="customers-datatable" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>mandate Id</th>
                                    <th>First Name</th>
                                    <th>Last Name</th>
                                    <th>Account Holder</th>
                                    <th>Account Number</th>
                                    <th>Account Type</th>
                                    <th>Action Date</th>
                                    <th>Payment Type</th>
                                    <th>Amount</th>
                                    <th>Reason</th>
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
</div>
@endsection 

@section('extra_script')
<script src="{{ asset('js/jquery-1.12.4.js') }}"></script>
<script src="{{ asset('js/jquery-ui.js') }}"></script>
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
<script type="text/javascript">
var myCustomerTable="";


$(document).ready(function() {
    $('.collectionDate').datepicker({
      
      //maxDate: '+0d',
      dateFormat: 'yy-mm-dd',
      changeYear : true,
      changeMonth : true
    });
    myCustomerTable=$('#customers-datatable').DataTable( {
        "lengthMenu": [ [25, 50, 100,200,500,1000, -1], [25, 50, 100,200,500,1000, "All"] ],
        serverSide: true,
        searching: true,
        dom: "<'row'<'col-sm-12 text-right p-0 mb-2'B><'col-sm-6'l><'col-sm-6'f>>" +
"<'row'<'col-sm-12'tr>>" +
"<'row'<'col-sm-5'i><'col-sm-7'p>>",
        buttons: [
                {
                    extend: 'excelHtml5',
                    text: 'Export to Excel',
                    filename:"disputed-transacation",
                    action: newExportAction
                }
        ],
        ajax: {
            url: "{{ url('merchant/collection/normal/ajax-disputed')}}",
            data: function ( d ) {
                d.startat = $("#startat").val();
                d.upto  = $("#upto").val();
            }
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
            {"data":7},
            {"data":8},
            {"data":9},
        ],
        columnDefs: [ 
            { orderable: false, targets: [] },
            { searchable: false, targets: [] }
        ],
        "order": [[6, 'desc']],
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