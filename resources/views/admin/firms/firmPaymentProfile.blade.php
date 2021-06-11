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
                      <h4 class="card-title">Details of Merchant {{$firm->business_name}}</h4>
                    </div>
                    <div class="tab-info">
                      
                      @include('admin.firms.view-detail-tabs')
                      <div class="tab-content">
                        <div role="tabpanel" class="tab-pane active">
                          <div class="p-20">
                            <div class="card-group">
                              <div class="card col-12">
                                <div class="card-body">
                                  <div class="row">
                                    <div class="col-12">
                                      <div class="d-flex no-block align-items-center">
                                        <div>
                                           <div class="icon"><i class="lni-empty-file"></i></div>
                                           <p class="text-muted">Available Balance for Account :- {{$availableFund->firm->payment_reff_number}}</p>
                                        </div>
                                        <div class="ml-auto">
                                           <h2 class="counter text-info">{{$availableFund->closing_amount}}</h2>
                                        </div>
                                      </div>
                                    </div>
                                    <div class="col-12">
                                      <div class="progress">
                                         <div class="progress-bar bg-info" role="progressbar" style="width: 85%; height: 6px;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              </div>
                              
                            </div>

                            <div class="row">
                              <div class="col-lg-12 col-md-12 col-xs-12">
                                <div class="card">
                                  <div class="card-header border-bottom">
                                    Statement for {{Helper::convertDate($dateFrom)}} to {{Helper::convertDate($dateUpto)}}
                                  </div>
                                  <div class="card-body">
                                    <form class="form-inline ml-2" method="post" action="{{url('admin/firms/payment-stats/').'/'.Helper::encryptVar($firm['id'])}}">
                                      @csrf
                                      <label class="sr-only" for="payment_from">From</label>
                                      <input type="text" class="form-control mb-2 mr-sm-2 paymentDatepicker" name="payment_from" readonly id="payment_from" value="{{old('payment_from',$request->payment_from)}}" placeholder="From">

                                      <label class="sr-only" for="payment_to">To</label>
                                      <input type="text" class="form-control mb-2 mr-sm-2 paymentDatepicker" name="payment_to" readonly id="payment_to" value="{{old('payment_to',$request->payment_to)}}" placeholder="To">
                                      
                                      <button type="submit" class="btn btn-common mb-3">Submit</button>
                                    </form>
                                    <div class="table-responsive">
                                      <table id="firms-datatable" class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Description</th>
                                                <th>Transactions</th>
                                                <th>Amount</th>
                                                <th>Closing Amount</th>
                                                
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @if(!is_null($firstRecord))
                                            <tr>
                                                <td></td>
                                                <td>Opening Balance</td>
                                                <td></td>
                                                <td></td>
                                                <td>{{$firstRecord['closing_amount']}}</td>
                                                
                                              </tr>
                                          @elseif(is_null($firstRecord))
                                            <tr>
                                                <td></td>
                                                <td>Opening Balance</td>
                                                <td></td>
                                                <td></td>
                                                <td>0</td>
                                                
                                              </tr>
                                          @endif


                                          @if(count($paymentStatement)>0)
                                            @foreach($paymentStatement as $eachStatement)
                                              <tr>
                                                <td>{{Helper::convertDate($eachStatement['entry_date'],'d-m-Y')}}</td>
                                                <td>
                                                  @if($eachStatement['transaction_type']=='batch_payment')
                                                    <a target="_blank" href="{{ url('admin/firms/payment-batch/tranx/'.encrypt($eachStatement['target_reffrence_id'])) }}">
                                                      {{$eachStatement['ledger_desc']}}
                                                    </a>
                                                  @else
                                                    {{$eachStatement['ledger_desc']}}
                                                  @endif
                                                </td>
                                                <td>
                                                  @if($eachStatement['transaction_type']=='batch_payment')
                                                    <a target="_blank" href="{{ url('admin/firms/payment-batch/tranx/'.encrypt($eachStatement['target_reffrence_id'])) }}">
                                                      {{count($eachStatement->paymentBatch->payments->where('payment_status',1))}}
                                                    </a>
                                                  @endif
                                                </td>
                                                <td>{{$eachStatement['amount']}}</th>
                                                <td>{{$eachStatement['closing_amount']}}</td>
                                                
                                              </tr>
                                            @endforeach
                                          @endif
                                        </tbody>
                                      </table>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>

                            

                          </div>

                            
                        </div>

                      </div>
                      <!-- Tab Content end -->
                    </div>
                    <!-- Tab info end -->



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

<!-- Datatable init js -->
<!-- <script src="{{ asset('js/datatables.init.js') }}"></script> -->
<script type="text/javascript">


$(document).ready(function() {
    $('#firms-datatable').DataTable({
      "lengthMenu": [ [25, 50, 100,200,500,1000, -1], [25, 50, 100,200,500,1000, "All"] ],
      "order": [],
        columnDefs: [ 
            { orderable: false, targets: [0,1,2,3,4] },
            
            
        ]
    });
    $('.paymentDatepicker').datepicker({
      
      dateFormat: 'yy-mm-dd',
      changeYear : true,
      changeMonth : true
    });
  });
</script>
@endsection