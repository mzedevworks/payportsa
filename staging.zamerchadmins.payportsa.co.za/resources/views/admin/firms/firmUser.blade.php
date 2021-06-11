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
                            <div class="row">
                              <div class="col-md-6">
                                <div class="form-group row">
                                  <label class="col-sm-3 col-form-label">First Name</label>
                                  <div class="col-sm-9 col-form-label">
                                    <p>{{ $primaryUser->first_name}}</p>
                                  </div>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="form-group row ">
                                  <label class="col-sm-3 col-form-label">Last Name </label>
                                  <div class="col-sm-9 col-form-label">
                                    <p>{{ $primaryUser->last_name}}</p>
                                  </div>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="form-group row">
                                  <label class="col-sm-3 col-form-label">Email</label>
                                  <div class="col-sm-9 col-form-label">
                                    <p>{{ $primaryUser->email}}</p>
                                  </div>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="form-group row">
                                  <label class="col-sm-3 col-form-label">Contact No. </label>
                                  <div class="col-sm-9 col-form-label">
                                    <p>{{$primaryUser->contact_number}}</p>
                                  </div>
                                </div>
                              </div>
                              
                            </div>
                            <a href="{{url('admin/merchants/update').'/'.Helper::encryptVar($primaryUser['id'])}}" class="btn btn-common">Edit</a>
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