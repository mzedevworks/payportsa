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
              <!-- Title Count Start -->
    <div class="card-group">
      <div class="card col-3">
        <div class="card-body">
          <div class="row">
            <div class="col-12">
              <div class="d-flex no-block align-items-center">
                <div>
                   <div class="icon"><i class="lni-empty-file"></i></div>
                   <p class="text-muted">Profile Limit</p>
                </div>
                <div class="ml-auto">
                   <h2 class="counter text-info">{{$transactionLimit->closing_balance}}</h2>
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
      <div class="card col-3">
        <div class="card-body">
          <div class="row">
            <div class="col-12">
              <div class="d-flex no-block align-items-center">
                <div>
                   <div class="icon"><i class="lni-empty-file"></i></div>
                   <p class="text-muted">Transacted Amount</p>
                </div>
                <div class="ml-auto">
                   <h2 class="counter text-info">
                   {{($transactedAmount->tot_amount>0)?$transactedAmount->tot_amount:0}}
                   </h2>
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
      <div class="card col-3">
        <div class="card-body">
          <div class="row">
            <div class="col-12">
              <div class="d-flex no-block align-items-center">
                <div>
                   <div class="icon"><i class="lni-empty-file"></i></div>
                   <p class="text-muted">Available Balance</p>
                </div>
                <div class="ml-auto">
                   <h2 class="counter text-info">
                   {{$transactionLimit->closing_balance-$transactedAmount->tot_amount}}
                   </h2>
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
              
              
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table id="firms-datatable" class="table table-bordered">
                  <thead>
                      <tr>
                          <th>Date</th>
                          <th>Description</th>
                          <th>Amount</th>
                          <th>Balance</th>
                      </tr>
                  </thead>
                  <tbody>
                    @if(count($ledgerData)>0)
                      @foreach($ledgerData as $eachLedger)
                        <tr>
                          <td>{{Helper::convertDate($eachLedger->entry_date,'d-m-Y')}}</td>
                          <td>{{$eachLedger->ledger_desc}}</th>
                          <td>{{($eachLedger->entry_type=='cr')?'+':'-'}}{{$eachLedger->amount}}</td>
                          <td>{{$eachLedger->closing_amount}}</td>
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


$(document).ready(function() {
    $('#firms-datatable').DataTable({
      "order": [],
        columnDefs: [ 
            { orderable: false, targets: [0,1,2,3] },
            
            
        ]
    });
  });
</script>
@endsection