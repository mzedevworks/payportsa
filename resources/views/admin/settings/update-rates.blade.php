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
                                <form class="form-sample" method="post" action="{{ url('admin/firms/update/rates/'.encrypt($firm->id)) }}" autocomplete="off">
                                  @csrf

                                  <div class="row">
                                    <div class="col-md-6">
                                      <div class="form-group row {{ $errors->first('trading_as')?'has-error':'' }}">
                                        <label class="col-sm-3 col-form-label">Products</label>
                                        <div class="col-sm-9">
                                          @if(isset($firm->is_payment) && $firm->is_payment==1)
                                          <p>Payments</p>
                                            @if(isset($firm->is_salaries) && $firm->is_salaries==1)
                                             <p>Salaries</p>
                                            @endif
                                            @if(isset($firm->is_creditors) && $firm->is_creditors==1)
                                             <p>Creditors</p>
                                            @endif
                                          @endif
                                          @if(isset($firm->is_collection) && $firm->is_collection==1)
                                             <p>Collection</p>
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
                                  @endif

                                  @if(isset($firm->is_collection) && $firm->is_collection==1)
                                  <p class="card-description">
                                    <b>Collection Rates Per Transaction</b>
                                  </p>
                                     @include('admin.firms.update-elements.collection-rates')
                                  @endif

                                  @if(isset($firm->is_payment) && $firm->is_payment==1)
                                  <b><p class="card-description">
                                   Payment Profile Limits
                                  </p></b>
                                     @include('admin.firms.update-elements.payment-profile-limits')
                                  @endif


                                  @if(isset($firm->is_collection) && $firm->is_collection==1)
                                  <b><p class="card-description">
                                   Collection Profile Limits
                                  </p></b>
                                     @include('admin.firms.update-elements.collection-profile-limits')
                                  @endif

                                  <b><p class="card-description">
                                   Other Limits
                                  </p></b>
                                  @include('admin.firms.update-elements.other-amounts')
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
                                                    <th>Same Day Collcetion</th>
                                                    <th>One Day Collection</th>
                                                    <th>Two Day Collection</th>
                                                    <th>Batch Fee Collcetion</th>
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
           