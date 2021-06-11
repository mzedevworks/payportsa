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
          <form class="form-sample" method="get" action="{{url('admin/tranx-report/collection') }}" autocomplete="off">
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
                  <label class="col-sm-5 col-form-label">Mandate Id</label>
                  <div class="col-sm-7">
                    <input type="text" class="form-control" placeholder="Mandate Id" id="mandate_id" name="mandate_id" value="{{old('mandate_id',$request->mandate_id)}}">
                    
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group row">
                  <label class="col-sm-5 col-form-label">Refrence</label>
                  <div class="col-sm-7">
                    <input type="text" class="form-control" placeholder="Refrence" id="refrence" name="refrence" value="{{old('refrence',$request->refrence)}}">
                    
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group row">
                  <label class="col-sm-5 col-form-label">Product</label>
                  <div class="col-sm-7">
                    <select class="form-control" id="product" name="product">
                      <option value="">--Select Product--</option>
                      <option value="reoccur" {{'reoccur'==old('status',$request->product)?'selected':''}}>Recurring</option>
                      <option value="normal" {{'normal'==old('status',$request->product)?'selected':''}}>Standard</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group row">
                  <label class="col-sm-5 col-form-label">Service Type</label>
                  <div class="col-sm-7">
                    <select class="form-control" name="serviceType" id="serviceType">
                      <option value="">--Select Type--</option>
                      @foreach(config('constants.serviceType') as $key => $type)
                      <option value="{{ $type }}" {{$type==old('serviceType',$request->serviceType)?'selected':''}}>{{ $type }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                  <div class="form-group row">
                    <label class="col-sm-5 col-form-label">Amount</label>
                    <div class="col-sm-7">
                      <input type="text" class="form-control recurringEntity" placeholder="Amount" id="amount" name="amount" value="{{ old('amount',$request->amount) }}">

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
              <div class="col-md-4">
                <div class="form-group row">
                  <label class="col-sm-5 col-form-label">Status</label>
                  <div class="col-sm-7">
                    <select class="form-control" id="status" name="status">
                      <option value="-1">--Select Status--</option>
                      @foreach(config('constants.transactionStatus') as $key => $type)
                      <option value="{{ $type['value'] }}" {{intval($type['value'])==intval(old('status',$request->status))?'selected':''}}
                     >{{ $type['title'] }}</option>
                      @endforeach
                    </select>

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
                                    <th>Mandate Id</th>
                                    <th>Merchant</th>
                                    <th>Account Number</th>
                                    <th>Action Date</th>
                                    <th>Refrence</th>
                                    <th>Product</th>
                                    <th>Service Type</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date Of Return</th>
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
        <h5 class="modal-title" id="myLargeModalLabel">Status Timeline</h5>
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      </div>
      <div class="modal-body" id="statusBoxModalBody">
        
      </div>
    </div>
  </div>
</div>
@endsection

@section('extra_script')
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

function generateAction(data, type, row, meta){

    var str='<a data-id="'+data.id+'" onclick="getStatus('+data.id+',this);" href="javascript:void(0);">'+data.reffrence+'</a>';
    //str+='<button type="button" onclick="deleteRecord(\''+data+'\',this);" class="btn bg-transparent"><i class="lni-trash" aria-hidden="true"></i></button>';
    return str;
}

function getStatus(collectionId,elem){
  
    // AJAX request
    $.ajax({
        url: "{{ url('admin/tranx-report/logs')}}",
        type: 'post',
        data: {id: collectionId,trxType:'collection'},
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
            url: "{{ url('admin/tranx-report/collection/ajax-reports')}}",
            data: function ( d ) {
                d.startat = $("#startat").val();
                d.upto  = $("#upto").val();
                d.mandate_id=$("#mandate_id").val();
                d.refrence=$("#refrence").val();
                d.product=$("#product").val();
                d.serviceType=$("#serviceType").val();
                d.amount=$("#amount").val();
                d.status=$("#status").val();
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
              "render": generateAction
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
            {"data":9},
        ],
        columnDefs: [ 
            { orderable: false, targets: [] },
            { searchable: false, targets: [] }
        ],
        "order": [[3, 'desc']],
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