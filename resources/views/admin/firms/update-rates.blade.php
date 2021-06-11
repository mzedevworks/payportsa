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
                  <div id="accordion">
                    <div class="card">
                          <div class="card-header">
                            <p>
                              <a data-toggle="collapse" data-parent="#accordion" href="#active-details" aria-expanded="false" aria-controls="active-details" class="btn btn-common m-b-10 collapsed">
                                Active Rates and profile limits
                              </a>
                              <a data-toggle="collapse" data-parent="#accordion" href="#past-details" aria-expanded="false" aria-controls="past-details" class="btn btn-common m-b-10 collapsed">
                                Past Rate tables
                              </a>
                            </p>
                          </div>
                          <div id="active-details" class="collapse show" data-parent="#accordion" style="">
                            <div class="card-body">
                               @include('elements.message')
                                <form class="form-sample" method="post" 
                                action="{{ url('admin/firms/update/rates/'.encrypt($firm->id)) }}" autocomplete="off">
                                  @csrf

                                  <div class="row">
                                    <div class="col-md-9">
                                      <div class="form-group row {{ $errors->first('trading_as')?'has-error':'' }}">
                                        <label class="col-sm-3 col-form-label">Selected Products</label>
                                        <div class="col-sm-9 col-form-label">
                                          @if(isset($firm->is_payment) && $firm->is_payment==1)
                                            <span>
                                              Payments
                                              @php
                                                    $paymentProducts=[];
                                                    if(isset($firm->is_salaries) && $firm->is_salaries==1){
                                                      $paymentProducts[]='Salaries';
                                                    }
                                                    if(isset($firm->is_creditors) && $firm->is_creditors==1){
                                                      $paymentProducts[]='Creditors';
                                                    }
                                                echo '('.implode(',',$paymentProducts).')';
                                              @endphp
                                            </span>
                                              
                                          @endif
                                          @if(isset($firm->is_collection) && $firm->is_collection==1)
                                             <span>
                                                Collection
                                                @php
                                                    $collectionProducts=[];
                                                    if(isset($firm->is_normal_collection) && $firm->is_normal_collection==1){
                                                      $collectionProducts[]='Once-Off';
                                                    }
                                                    if(isset($firm->is_reoccur_collection) && $firm->is_reoccur_collection==1){
                                                      $collectionProducts[]='Reccuring';
                                                    }
                                                echo '('.implode(',',$collectionProducts).')';
                                                @endphp

                                             </span>
                                          @endif

                                          @if(isset($firm->is_debicheck) && $firm->is_debicheck==1)
                                             <span>
                                                Debicheck
                                             </span>
                                          @endif

                                          @if(isset($firm->is_avs) && $firm->is_avs==1)
                                             <span>
                                                AVS
                                             </span>
                                          @endif
                                        </div>
                                      </div>
                                    </div>
                                  </div>
                                  
                                  @if(isset($firm->is_payment) && $firm->is_payment==1)
                                  <b><p class="card-description">
                                   Payment Rates Per Transaction
                                  </p></b>
                                     @include('admin.firms.update-elements.payment-rates')

                                  <b><p class="card-description">
                                   Payment Profile Limits
                                  </p></b>
                                     @include('admin.firms.update-elements.payment-profile-limits')
                                  @endif
                                  @if(isset($firm->is_avs) && $firm->is_avs==1)
                                  <b>
                                        <p class="card-description">
                                            AVS Rates
                                        </p>
                                    </b>
                                    @include('admin.firms.update-elements.avs-rates')
                                    @endif
                                  @if(isset($firm->is_collection) && $firm->is_collection==1)
                                  <p class="card-description">
                                    <b>Collection Rates Per Transaction</b>
                                  </p>
                                     @include('admin.firms.update-elements.collection-rates')
                                  <b><p class="card-description">
                                   Collection Profile Limits
                                  </p></b>
                                     @include('admin.firms.update-elements.collection-profile-limits')
                                  @endif

                                  <!-- <b><p class="card-description">
                                   Other Limits
                                  </p></b>
                                  @include('admin.firms.update-elements.other-amounts') -->
                                  <button type="submit" class="btn btn-common mr-3">Update</button>
                                  <a href="{{ url('admin/firms') }}" class="btn btn-light">Cancel</a></button>
                                </form>
                            </div>
                          </div>
                    </div>
                    <div class="card">
                          <div id="past-details" class="collapse" data-parent="#accordion" style="">
                            <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="datatable" class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Same Day Paymnt</th>
                                                    <th>One Day Payment</th>
                                                    <th>Two Day Payment</th>
                                                    <th>Batch Fee Payment</th>
                                                    <th>Failed Payment</th>
                                                    <th>Same Day Collection</th>
                                                    <th>One Day Collection</th>
                                                    <th>Two Day Collection</th>
                                                    <th>Batch Fee Collection</th>
                                                    <th>Failed Collection</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            @if(count($past_rates)>0)
                                              @foreach($past_rates as $past_rate)
                                              <tr>
                                                <td>{{ $past_rate->same_day_payment }}</td>
                                                <td>{{ $past_rate->one_day_payment }}</td>
                                                <td>{{ $past_rate->two_day_payment }}</td>
                                                <td>{{ $past_rate->batch_fee_payment }}</td>
                                                <td>{{ $past_rate->failed_payment }}</td>
                                                <td>{{ $past_rate->same_day_collection }}</td>
                                                <td>{{ $past_rate->one_day_collection }}</td>
                                                <td>{{ $past_rate->two_day_collection }}</td>
                                                <td>{{ $past_rate->batch_fee_collection }}</td>
                                                <td>{{ $past_rate->failed_collection }}</td>
                                                <td>{{ Helper::convertDate($past_rate->created_at,'d-m-Y')}}</td>
                                              </tr>
                                              @endforeach
                                            @endif
                                        </table>
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
<script src="{{ asset('js/datatables.init.js') }}"></script>
@endsection