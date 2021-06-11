@extends('layouts.app')

@section('extra_style')
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
@endsection

@section('content')
  <div class="container-fluid">
    <div class="row">
      <div class="col-12 grid-margin">
        <div class="card">
          
          <div class="card-body">
              
            <div class="tab-info">
              <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                  <a href="{{url('merchant/creditors/view/').'/'.Helper::encryptVar($creditorRes['id'])}}" class="nav-link active">Creditor Info</a>
                </li>
                <li class="nav-item">
                  <a href="{{url('merchant/creditors/transactions/').'/'.Helper::encryptVar($creditorRes['id'])}}" class="nav-link">History</a>
                </li>
                
              </ul>
              <div class="tab-content">
                <div role="tabpanel" class="tab-pane active" id="default-tab-1">
                  <div class="p-20">
                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-group row">
                          <label class="col-sm-3 col-form-label">Name</label>
                          <div class="col-sm-9">
                            <p>{{$creditorRes['first_name']}}</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group row ">
                          <label class="col-sm-3 col-form-label">Surname</label>
                          <div class="col-sm-9">
                            <p>{{$creditorRes['last_name']}}</p>
                          </div>
                        </div>
                      </div>

                      <div class="col-md-6">
                        <div class="form-group row ">
                          <label class="col-sm-3 col-form-label">Email</label>
                          <div class="col-sm-9">
                            <p>{{$creditorRes['email']}}</p>
                          </div>
                        </div>
                      </div>

                      <div class="col-md-6">
                        <div class="form-group row ">
                          <label class="col-sm-3 col-form-label">Creditor No.</label>
                          <div class="col-sm-9">
                            <p>{{$creditorRes['id_number']}}</p>
                          </div>
                        </div>
                      </div>

                      <div class="col-md-6">
                        <div class="form-group row">
                          <label class="col-sm-3 col-form-label">Amount</label>
                          <div class="col-sm-9">
                            <p>{{$creditorRes['salary']}}</p>
                          </div>
                        </div>
                      </div>

                      <div class="col-md-6">
                        <div class="form-group row ">
                          <label class="col-sm-3 col-form-label">Address</label>
                          <div class="col-sm-9">
                            <p>{{$creditorRes['address']}}</p>
                          </div>
                        </div>
                      </div>

                      <div class="col-md-6">
                        <div class="form-group row">
                          <label class="col-sm-3 col-form-label">Contact No.</label>
                          <div class="col-sm-9">
                            <p>{{$creditorRes['contact_number']}}</p>
                          </div>
                        </div>
                      </div>

                      <div class="col-md-6">
                        <div class="form-group row ">
                          <label class="col-sm-3 col-form-label">Reference</label>
                          <div class="col-sm-9">
                            <p>{{$creditorRes['reference']}}</p>
                          </div>
                        </div>
                      </div>
                    </div>
                    <p class="card-description">Bank Account Info.</p>
                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-group row ">
                          <label class="col-sm-3 col-form-label">Bank Name</label>
                          <div class="col-sm-9">
                            <p>
                              @foreach(Helper::getBankDetails() as $eachBank)
                                {{ ($eachBank->id==$creditorRes['bank_id'])?$eachBank->bank_name:''}}
                              @endforeach
                            </p>
                            
                            
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group row ">
                          <label class="col-sm-3 col-form-label">Branch Code</label>
                          <div class="col-sm-9">
                            <p>{{$creditorRes['branch_code']}}</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group row">
                          <label class="col-sm-3 col-form-label">Account Type</label>
                          <div class="col-sm-9">
                            <p>{{$creditorRes['account_type']}}</p>
                            
                          </div>
                        </div>
                      </div>
                      
                      <div class="col-md-6">
                        <div class="form-group row ">
                          <label class="col-sm-3 col-form-label">Account Holder Name</label>
                          <div class="col-sm-9">
                            <p>{{$creditorRes['account_holder_name']}}</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6">
                          <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Account Number</label>
                            <div class="col-sm-9">
                            <p>{{$creditorRes['account_number']}}</p>
                            </div>
                          </div>
                      </div>
                      
                    </div>
                    
                      <a href="{{url('merchant/creditors/update/').'/'.Helper::encryptVar($creditorRes['id'])}}" class="btn btn-common">Edit</a>
                  </div>
                </div>
                <div role="tabpanel" class="tab-pane fade" id="default-tab-2">
                  <div class="p-20">
                    <p>Rouge Group, use your harpoons and tow cables. Go for the legs. It might be our only chance of stopping them. All right, stand by, Dack. Luke, we've got a malfunction in fire control. I'll have to cut in the auxiliary. ust hang on. Hang on, Dack. Get ready to fire that tow cable. Dack? Dack! Yes, Lord Vader. I've reached the main power generator. The shield will be down in moments. You may start your landing. Rouge Three. Copy, Rouge Leader Wedge, I've lost my gunner.You'll have to make this shot.</p>
                  </div>
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
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

@endsection