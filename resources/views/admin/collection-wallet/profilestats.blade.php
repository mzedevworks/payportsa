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
                             <h2 class="counter text-info">{{$fundLimit->tot_aval}}</h2>
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
                             <p class="text-muted">Outstanding Balance</p>
                          </div>
                          <div class="ml-auto">
                             <h2 class="counter text-info">
                             {{$payPortInfo->closing_balance}}
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
                                    <th>Description</th>
                                    <th>Merchant</th>
                                    <th>Amount</th>
                                    <th>Closing Amount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                            @if(count($statements)>0)
                              @foreach($statements as $eachStatement)
                              <tr>
                                    <td>{{$eachStatement->remark}}</td>
                                    <td>{{$eachStatement->business_name}}</td>
                                    <td>{{$eachStatement->amount}}</th>
                                    <td>{{$eachStatement->closing_balance}}</td>
                                    <td>{{Helper::convertDate($eachStatement->transmission_date,'d-m-Y H:i')}}</td>
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
      "lengthMenu": [ [25, 50, 100,200,500,1000, -1], [25, 50, 100,200,500,1000, "All"] ],
      "order": [],
    });
  });
</script>
@endsection