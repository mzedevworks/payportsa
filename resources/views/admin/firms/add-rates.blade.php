@extends('layouts.app')

@section('content')
            <div class="container-fluid">
              <div class="row">
                <div class="col-12 grid-margin">
                  <div class="card">
                    <div class="card-body">
                       @include('elements.message')
                      <form class="form-sample" method="post" 
                      action="{{ url('admin/firms/add/rates/'.encrypt($firm->id)) }}">
                        @csrf

                        <div class="row">
                          <div class="col-md-6">
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
                          <p class="card-description">
                            <b>Payment Rates Per Transaction</b>
                          </p>
                             @include('admin.firms.add-elements.payment-rates')
                          
                          <p class="card-description">
                            <b>Payment Profile Limits</b>
                          </p>
                           @include('admin.firms.add-elements.payment-profile-limits')
                        @endif

                        @if(isset($firm->is_avs) && $firm->is_avs==1)
                        <p class="card-description">
                                <b>AVS Rate</b>
                        </p>
                          @include('admin.firms.add-elements.avs-rates')
                        @endif
                        @if(isset($firm->is_collection) && $firm->is_collection==1)
                        <p class="card-description">
                          <b>Collection Rates Per Transaction</b>
                        </p>
                           @include('admin.firms.add-elements.collection-rates')

                        <p class="card-description">
                          <b>Collection Profile Limits</b>
                        </p>
                           @include('admin.firms.add-elements.collection-profile-limits')
                        @endif
                        <button type="submit" class="btn btn-common mr-3">Submit</button>
                        <a href="{{ url('admin/firms') }}" class="btn btn-light">Cancel</a></button>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
            </div>
@endsection

@section('extra_script')

@endsection