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
                      <div class="tab-content">
                        <div role="tabpanel" class="tab-pane active">
                          <div class="p-20">
                            <p class="card-description">
                              Business Details
                            </p>
                            <div class="row">
                              <div class="col-md-6">
                                <div class="form-group row">
                                  <label class="col-sm-3 col-form-label">Mandate Ref</label>
                                  <div class="col-sm-9 col-form-label">
                                    <p>{{ $firm->mandate_ref}}</p>
                                  </div>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="form-group row ">
                                  <label class="col-sm-3 col-form-label">Abbreviated Name </label>
                                  <div class="col-sm-9 col-form-label">
                                    <p>{{ $firm->trading_as}}</p>
                                  </div>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="form-group row">
                                  <label class="col-sm-3 col-form-label">Business Name </label>
                                  <div class="col-sm-9 col-form-label">
                                    <p>{{$firm['business_name']}}</p>
                                  </div>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="form-group row">
                                  <label class="col-sm-3 col-form-label">VAT no. </label>
                                  <div class="col-sm-9 col-form-label">
                                    <p>{{ $firm->vat_no}}</p>
                                  </div>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="form-group row ">
                                  <label class="col-sm-3 col-form-label">Registration No </label>
                                  <div class="col-sm-9 col-form-label">
                                    <p>{{ $firm->registration_no}}</p>
                                  </div>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="form-group row {{$errors->first('entry_class')?'has-error':''}}">
                                  <label class="col-sm-3 col-form-label">Entry Class</label>
                                  <div class="col-sm-9 col-form-label">
                                    <p>{{ Config('constants.entry_class')[$firm['entry_class']]}}</p>
                                  </div>
                                  
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="form-group row {{ $errors->first('setup_fee')?'has-error':'' }}">
                                  <label class="col-sm-3 col-form-label">Set-up Fee</label>
                                  <div class="col-sm-9 col-form-label">
                                    <p>{{ $firm->setup_fee}}</p>
                                  </div>
                                </div>
                              </div> 
                              <div class="col-md-6">
                                <div class="form-group row {{ $errors->first('setup_collection_date')?'has-error':'' }}">
                                  <label class="col-sm-3 col-form-label">Setup Collection Date</label>
                                  <div class="col-sm-9 col-form-label">
                                    <p>{{ $firm->setup_collection_date}}</p>
                                  </div>
                                </div>
                              </div>

                              <div class="col-md-6">
                                <div class="form-group row {{ $errors->first('monthly_fee')?'has-error':'' }}">
                                  <label class="col-sm-3 col-form-label">Monthly Fees</label>
                                  <div class="col-sm-9 col-form-label">
                                    <p>{{ $firm->monthly_fee}}</p>
                                  </div>
                                </div>
                              </div>
                              <div class="col-md-6">
                                  <div class="form-group row {{ $errors->first('monthly_collection_date')?'has-error':'' }}">
                                    <label class="col-sm-3 col-form-label">Recurring Start Date</label>
                                    <div class="col-sm-9 col-form-label">
                                      <p>{{ $firm->monthly_collection_date}}</p>
                                    </div>
                                  </div>
                              </div>

                              <div class="col-md-6">
                                <div class="form-group row {{ $errors->first('status')?'has-error':'' }}">
                                  <label class="col-sm-3 col-form-label">Status </label>
                                  <div class="col-sm-9 col-form-label">
                                    <p>{{ Config('constants.status')[$firm['status']]}}</p>
                                  </div>
                                  
                                </div>
                              </div>
                            </div>
                            <p class="card-description">
                              Address Details
                            </p>
                            <div class="row">
                              <div class="col-md-6">
                                <div class="form-group row ">
                                  <label class="col-sm-3 col-form-label">Address Line 1 </label>
                                  <div class="col-sm-9 col-form-label">
                                    <p>{{$firm['address1']}}</p>
                                    
                                  </div>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="form-group row">
                                  <label class="col-sm-3 col-form-label">Address Line 2</label>
                                  <div class="col-sm-9 col-form-label">
                                    <p>{{$firm['address2']}}</p>
                                  </div>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="form-group row ">
                                  <label class="col-sm-3 col-form-label">City </label>
                                  <div class="col-sm-9 col-form-label">
                                    <p>{{$firm['city']}}</p>
                                    
                                  </div>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="form-group row">
                                  <label class="col-sm-3 col-form-label">Subrub </label>
                                  <div class="col-sm-9 col-form-label">
                                    <p>{{$firm['subrub']}}</p>
                                  </div>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="form-group row {{ $errors->first('province')?'has-error':'' }}">
                                  <label class="col-sm-3 col-form-label">Province </label>
                                  <div class="col-sm-9 col-form-label">
                                    <p>{{$firm['province']}}</p>
                                  </div>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="form-group row {{ $errors->first('po_box_number')?'has-error':'' }}">
                                  <label class="col-sm-3 col-form-label">PO BOX No </label>
                                  <div class="col-sm-9 col-form-label">
                                    <p>{{$firm['po_box_number']}}</p>
                                  </div>
                                </div>
                              </div>
                            </div>
                            
                            <p class="card-description">
                              Bank Account Info.
                            </p>
                            <div class="row">
                              <div class="col-md-6">
                                <div class="form-group row">
                                  <label class="col-sm-3 col-form-label">Account Holder Name </label>
                                  <div class="col-sm-9 col-form-label">
                                    <p>{{$firm['account_holder_name']}}</p>
                                  </div>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="form-group row">
                                  <label class="col-sm-3 col-form-label">Account Number </label>
                                  <div class="col-sm-9 col-form-label">
                                    <p>{{$firm['account_number']}}</p>
                                  </div>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="form-group row">
                                  <label class="col-sm-3 col-form-label">Account Type </label>
                                  <div class="col-sm-9 col-form-label">
                                    <p>{{ $firm['account_type']}}</p>
                                  </div>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="form-group row {{ $errors->first('bank_id')?'has-error':'' }}">
                                  <label class="col-sm-3 col-form-label">Bank Name  </label>
                                  <div class="col-sm-9 col-form-label">
                                      <p>
                                      @foreach($bankDetails as $bankDetail)
                                        {{ $bankDetail->id==$firm['bank_id']?$bankDetail->bank_name:''}}
                                      @endforeach
                                    </p>
                                  </div>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="form-group row {{ $errors->first('branch_code')?'has-error':'' }}">
                                  <label class="col-sm-3 col-form-label">Branch Code </label>
                                  <div class="col-sm-9 col-form-label">
                                    <p>{{$firm['branch_code']}}</p>
                                  </div>
                                </div>
                              </div>
                            </div>

                            <p class="card-description">
                              Product Categories
                            </p>
                            <div class="row">
                              <div class="col-md-12">
                                <div class="form-group row">
                                  <label class="col-sm-3 col-form-label">Selected Products</label>
                                  <div class="col-sm-9 col-form-label col-form-label">
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
                            <a href="{{url('admin/firms/update/').'/'.Helper::encryptVar($firm['id'])}}" class="btn btn-common">Edit</a>

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

@endsection