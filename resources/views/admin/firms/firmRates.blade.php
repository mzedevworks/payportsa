@extends('layouts.app')

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
          @if($rates)
          <div class="tab-content">
            <div role="tabpanel" class="tab-pane active">
              <div class="p-20">
                @if(isset($firm->is_payment) && $firm->is_payment==1)
                  <p class="card-description">
                    Payment Rates Per Transaction
                  </p>
                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Same Day</label>
                        <div class="col-sm-9 col-form-label">
                          <p>{{$rates['same_day_payment']}}</p>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group row">
                        <label class="col-sm-3 col-form-label">1 day</label>
                        <div class="col-sm-9 col-form-label">
                          <p>{{$rates['one_day_payment']}}</p>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group row">
                        <label class="col-sm-3 col-form-label">2 day</label>
                        <div class="col-sm-9 col-form-label">
                          <p>{{$rates['two_day_payment']}}</p>
                        </div>
                      </div>
                    </div>
                  
                    <div class="col-md-6">
                      <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Batch Fee</label>
                        <div class="col-sm-9 col-form-label">
                          <p>{{$rates['batch_fee_payment']}}</p>
                        </div>
                      </div>
                    </div>
                  
                    <div class="col-md-6">
                      <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Failed Transaction</label>
                        <div class="col-sm-9 col-form-label">
                          <p>{{$rates['failed_payment']}}</p>
                        </div>
                      </div>
                    </div>
                  </div> 
                  <p class="card-description">AVS Rates</p>
                    <div class="row">
                        @if(isset($firm->is_avs_batch) && $firm->is_avs_batch==1)
                        <div class="col-md-6">
                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">AVS Batch</label>
                                <div class="col-sm-9 col-form-label">
                                    <p>{{$rates['avs_batch']}}</p>
                                </div>
                            </div>
                        </div>
                        @endif
                        @if(isset($firm->is_avs_rt) && $firm->is_avs_rt==1)
                        <div class="col-md-6">
                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">AVS Real Time</label>
                                <div class="col-sm-9 col-form-label">
                                    <p>{{$rates['avs_rt']}}</p>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div> 
                  @if($profileLimits)
                  <p class="card-description">
                    Payment Profile Limits
                  </p>
                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Line Limit</label>
                        <div class="col-sm-9 col-form-label">
                          <p>{{$profileLimits['line_payment']}}</p>
                        </div>
                      </div>
                    </div>
                  
                    <div class="col-md-6">
                      <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Batch Limit</label>
                        <div class="col-sm-9 col-form-label">
                          <p>{{$profileLimits['batch_payment']}}</p>
                        </div>
                      </div>
                    </div>
                  
                    <div class="col-md-6">
                      <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Monthly Limit</label>
                        <div class="col-sm-9 col-form-label">
                          <p>{{$profileLimits['monthly_payment']}}</p>
                        </div>
                      </div>
                    </div>
                  </div>  
                  @endif 
                @endif

                @if(isset($firm->is_collection) && $firm->is_collection==1)
                  <p class="card-description">
                    <b>Collection Rates Per Transaction</b>
                  </p>
                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Same Day</label>
                        <div class="col-sm-9 col-form-label">
                          <p>{{$rates['same_day_collection']}}</p>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group row">
                          <label class="col-sm-3 col-form-label">1 day</label>
                          <div class="col-sm-9 col-form-label">
                            <p>{{$rates['one_day_collection']}}</p>
                            
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group row">
                          <label class="col-sm-3 col-form-label">2 day</label>
                          <div class="col-sm-9 col-form-label">
                            <p>{{$rates['two_day_collection']}}</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group row {{ $errors->first('failed_collection')?'has-error':'' }}">
                          <label class="col-sm-3 col-form-label">Failed Transaction</label>
                          <div class="col-sm-9 col-form-label">
                            <p>{{$rates['failed_collection']}}</p>
                          </div>
                        </div>
                      </div>
                  </div> 
                  @if($profileLimits)
                  <p class="card-description">
                   Collection Profile Limits
                  </p>
                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-group row">
                          <label class="col-sm-3 col-form-label">Line Limit</label>
                          <div class="col-sm-9 col-form-label">
                            <p>{{$profileLimits['line_collection']}}</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group row">
                          <label class="col-sm-3 col-form-label">Surety Percentage</label>
                          <div class="col-sm-9 col-form-label">
                            <p>{{$profileLimits['surety_amount']}}</p>
                          </div>
                        </div>
                      </div>
                    </div>
                  @endif
                  @endif

                <a href="{{url('admin/firms/update/rates/').'/'.Helper::encryptVar($firm['id'])}}" class="btn btn-common">Edit</a>
              </div>

                
            </div>

          </div>
          @endif
          <!-- Tab Content end -->
        </div>
        <!-- Tab info end -->
      </div>
    </div>
  </div>
</div>
@endsection

@section('extra_script')

@endsection