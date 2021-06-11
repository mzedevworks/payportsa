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
<div class="container-fluid">
  <div class="row">
    <div class="col-12 grid-margin">
      <div class="card">
        <div class="card-header border-bottom">
          <h4 class="card-title">Filters</h4>
        </div>
        
        <div class="card-body">
          <form class="form-sample" method="get" action="{{url('admin/tranx-report/avs') }}" autocomplete="off">
            @csrf
            <div class="row">
              <div class="col-md-4">
                <div class="form-group row">
                  <label class="col-sm-5 col-form-label">Merchant</label>
                  <div class="col-sm-7">
                    <select class="form-control" id="firmid" name="firmid">
                      <option value="">Merchant Name</option>
                        @if(count($firms)>0)
                          @foreach($firms as $eachMerchant)
                            <option value="{{ $eachMerchant->id }}" {{intval($eachMerchant->id)==intval(old('firmid',$request->firmid))?'selected':''}}>
                              {{ $eachMerchant->trading_as }}
                            </option>
                          @endforeach
                        @endif
                    </select>

                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group row">
                  <label class="col-sm-5 col-form-label">First Name</label>
                  <div class="col-sm-7">
                    <input type="text" class="form-control" placeholder="First Name" id="f_name" name="f_name" value="{{old('f_name',$request->f_name)}}">
                    
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group row">
                  <label class="col-sm-5 col-form-label">Last Name</label>
                  <div class="col-sm-7">
                    <input type="text" class="form-control" placeholder="Last Name" id="l_name" name="l_name" value="{{old('l_name',$request->l_name)}}">
                    
                  </div>
                </div>
              </div>
              
              <div class="col-md-4">
                <div class="form-group row">
                  <label class="col-sm-5 col-form-label">Avs Type</label>
                  <div class="col-sm-7">
                    <select class="form-control" name="serviceType" id="serviceType">
                      <option value="">--Select Type--</option>
                      <option value="business" {{'business'==old('serviceType',$request->serviceType)?'selected':''}}>Business</option>
                      <option value="individual" {{'individual'==old('serviceType',$request->serviceType)?'selected':''}}>Individual</option>
                     
                    </select>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                  <div class="form-group row">
                    <label class="col-sm-5 col-form-label">Account Number</label>
                    <div class="col-sm-7">
                      <input type="text" class="form-control recurringEntity" placeholder="Account Number" id="acc_num" name="acc_num" value="{{ old('acc_num',$request->acc_num) }}">

                    </div>
                  </div>
              </div>
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
                                    <th>Type</th>
                                    <th>Id/Registration Number</th>
                                    <th>Initials</th>
                                    <th>Company/SurName</th>
                                    <th>Bank</th>
                                    <th>Branch</th>
                                    <th>Account Number</th>
                                    <th>Status</th>
                                    <th>Verfication Date</th>
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
</div>
<div class="modal fade statusBoxModal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="myLargeModalLabel">AVS Details</h5>
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      </div>
      <div class="modal-body" id="statusBoxModalBody">
        
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

<script>var _tooltip = jQuery.fn.tooltip;</script>
<script src="{{ asset('js/jquery-ui.js') }}"></script>
<script>jQuery.fn.tooltip = _tooltip;</script>
<script type="text/javascript">
var myCustomerTable="";

function generateAction(data, type, row, meta){

    var str='<div class="float-left"><a data-id="'+data.id+'"  onclick="getStatus(\''+data.id+'\',this);" class="btn bg-transparent" data-toggle="tooltip" data-placement="top" data-original-title="View Details"><i class="lni-eye" aria-hidden="true"></i></a></div>';
    //str+='<button type="button" onclick="deleteRecord(\''+data+'\',this);" class="btn bg-transparent"><i class="lni-trash" aria-hidden="true"></i></button>';
    return str;
}

function getStatus(avsId,elem){
  
    // AJAX request
    $.ajax({
        url: "{{ url('admin/tranx-report/avs/detailed')}}",
        type: 'post',
        data: {id: avsId},
        success: function(response){ 
            // Add response in Modal body
            
            $('.statusBoxModal').modal('show');
            $('#statusBoxModalBody').html(response); 
        }
    });
}

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
        dom: "<'row'<'col-sm-12 text-right p-0 mb-2'B><'col-sm-6'l>>" +"<'row'<'col-sm-12'tr>>" +"<'row'<'col-sm-5'i><'col-sm-7'p>>",
        buttons: [
            //'copy', 'excel', 'pdf', 'print'
            {
                    extend: 'excelHtml5',
                    text: 'Export to Excel',
                    filename:"Transacations",
                    action: newExportAction
                }
        ],
        ajax: {
            url: "{{ url('admin/tranx-report/avs/ajax-reports')}}",
            data: function ( d ) {
                d.startat = $("#startat").val();
                d.upto  = $("#upto").val();
                d.f_name=$("#f_name").val();
                d.l_name=$("#l_name").val();
                d.serviceType=$("#serviceType").val();
                d.acc_num=$("#acc_num").val();
                d.firmid=$("#firmid").val();
            }
        },
        //dom: "rtiS",
        "columns": [
            {"data":0},
            {"data":1},
            {"data":2},
            {"data":3},
            {
              "data":4,
              
            },
            {"data":5},
            {"data":6},
            {"data":7},
            {
              "data":8,
              "render":function(data, type, row, meta){
                return data;
              }
              
            },
            {
              "data":9,
            "render": generateAction
          },
        ],
        columnDefs: [ 
            { orderable: false, targets: [9] },
            { searchable: false, targets: [] }
        ],
        "order": [[3, 'desc']],
        deferRender: true,
        "fnDrawCallback": function( oSettings ) {
            $('[data-toggle="tooltip"]').tooltip();
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