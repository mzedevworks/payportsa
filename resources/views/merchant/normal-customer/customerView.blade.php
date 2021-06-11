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
                  <a href="{{url('merchant/collection/normal/customer/view/').'/'.Helper::encryptVar($cusRes['id'])}}" class="nav-link active">Customer Info</a>
                </li>
                <li class="nav-item">
                  <a href="{{url('merchant/collection/normal/customer/transactions/').'/'.Helper::encryptVar($cusRes['id'])}}" class="nav-link">History</a>
                </li>
                
              </ul>
              <div class="tab-content">
                <div role="tabpanel" class="tab-pane active" id="default-tab-1">
                  <div class="p-20">
                    <p class="card-description">Customer's Personal Information</p>
                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-group row ">
                          <label class="col-sm-3 col-form-label">First Name</label>
                          <div class="col-sm-9">
                            <p>{{$cusRes['first_name']}}</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group row ">
                          <label class="col-sm-3 col-form-label">Last Name</label>
                          <div class="col-sm-9">
                            <p>{{$cusRes['last_name']}}</p>
                          </div>
                        </div>
                      </div>
                    
                      <div class="col-md-6">
                        <div class="form-group row ">
                          <label class="col-sm-3 col-form-label">Email</label>
                          <div class="col-sm-9">
                            <p>{{$cusRes['email']}}</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group row ">
                          <label class="col-sm-3 col-form-label">Cellphone</label>
                          <div class="col-sm-9">
                            <p>{{$cusRes['contact_number']}}</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group row">
                          <label class="col-sm-3">Status </label>
                        
                            <div class="col-sm-9">
                              <p>
                                {{ (intval($cusRes['status'])===1) ? 'Active': ''}}
                                {{ (intval($cusRes['status'])===2) ? 'In-active': ''}}
                              </p>
                            </div>
                            
                        </div>
                      </div>
                    </div>
                    <p class="card-description">Account Details</p>
                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-group row">
                          <label class="col-sm-3 col-form-label">Account Holder Name</label>
                          <div class="col-sm-9">
                            <p>{{$cusRes['account_holder_name']}}</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6">
                          <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Account Number</label>
                            <div class="col-sm-9">
                              <p>{{$cusRes['account_number']}}</p>
                              
                            </div>
                          </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group row {{ $errors->first('account_type')?'has-error':'' }}">
                          <label class="col-sm-3 col-form-label">Account Type</label>
                          <div class="col-sm-9">
                            <p>{{$cusRes['account_type']}}</p>
                            
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group row {{$errors->first('bank_id')?'has-error':''}}">
                          <label class="col-sm-3 col-form-label">Bank Name</label>
                          <div class="col-sm-9">
                            <p>
                              @foreach(Helper::getBankDetails() as $eachBank)
                                {{ ($eachBank->id==$cusRes['bank_id'])?$eachBank->bank_name:''}}
                              @endforeach
                            </p>
                            
                            
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group row {{ $errors->first('branch_code')?'has-error':'' }}">
                          <label class="col-sm-3 col-form-label">Branch Code</label>
                          <div class="col-sm-9">
                            <p>{{$cusRes['branch_code']}}</p>
                            
                          </div>
                        </div>
                      </div>
                    </div>
                    <p class="card-description">Address</p>
                    <div class="row">
                      
                      <div class="col-md-6">
                        <div class="form-group row {{$errors->first('address_one')?'has-error':''}}">
                          <label class="col-sm-3 col-form-label">Address Line 1</label>
                          <div class="col-sm-9">
                            <p>{{$cusRes['address_one']}}</p>

                          </div>
                        </div>
                      </div>

                      <div class="col-md-6">
                        <div class="form-group row {{$errors->first('address_line_two')?'has-error':''}}">
                          <label class="col-sm-3 col-form-label">Address Line 2</label>
                          <div class="col-sm-9">
                            <p>{{$cusRes['address_line_two']}}</p>
                          </div>
                        </div>
                      </div>

                      <div class="col-md-6">
                        <div class="form-group row {{$errors->first('subrub')?'has-error':''}}">
                          <label class="col-sm-3 col-form-label">Suburb</label>
                          <div class="col-sm-9">
                            <p>{{$cusRes['subrub']}}</p>
                          </div>
                        </div>
                      </div>

                      <div class="col-md-6">
                        <div class="form-group row {{$errors->first('city')?'has-error':''}}">
                          <label class="col-sm-3 col-form-label">City</label>
                          <div class="col-sm-9">
                            <p>{{$cusRes['city']}}</p>
                          </div>
                        </div>
                      </div>

                      <div class="col-md-6">
                        <div class="form-group row {{$errors->first('province')?'has-error':''}}">
                          <label class="col-sm-3 col-form-label">Province</label>
                          <div class="col-sm-9">
                            <p>{{$cusRes['province']}}</p>
                            
                          </div>
                        </div>
                      </div>

                    </div>
                    <p class="card-description">Service Details</p>
                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-group row {{$errors->first('mandate_id')?'has-error':''}}">
                          <label class="col-sm-3 col-form-label">Mandate Id</label>
                          <div class="col-sm-9">
                            <p>{{$cusRes['mandate_id']}}</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group row {{$errors->first('id_number')?'has-error':''}}">
                          <label class="col-sm-3 col-form-label">Id No.</label>
                          <div class="col-sm-9">
                            <p>{{$cusRes['id_number']}}</p>
                          </div>
                        </div>
                      </div>
                      
                    </div>
                    <?php /* <p class="card-description">Collection Details(For OnceOff)</p>
                    <div class="row">
                      <div class="col-md-6">
                          <div class="form-group row {{ $errors->first('collection_date')?'has-error':'' }}">
                            <label class="col-sm-3 col-form-label">Collection Date</label>
                            <div class="col-sm-9">
                              
                              <p>{{Helper::convertDate($cusRes['collection_date'],'Y-m-d')}}</p>
                              
                            </div>
                          </div>
                      </div>
                      <div class="col-md-6">
                            <div class="form-group row {{ $errors->first('once_off_amount')?'has-error':'' }}">
                              <label class="col-sm-3 col-form-label">OnceOff Amount</label>
                              <div class="col-sm-9">
                                <p>{{$cusRes['once_off_amount']}}</p>
                                
                              </div>
                            </div>
                      </div> 
                    </div>
                    */ ?>
                      <a href="{{url('merchant/collection/normal/customer/update/').'/'.Helper::encryptVar($cusRes['id'])}}" class="btn btn-common">Edit</a>
                      
                  </div>
                </div>
                <div role="tabpanel" class="tab-pane fade" id="default-tab-2">
                  <div class="p-20">
                    <p></p>
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
<script type="text/javascript">
  

function takeAction(dataId,actionType){
    var con=confirm("Are you sure to "+actionType+" this record?");
    
    if(con){
        $.ajax({
                
                type  : 'post',
                url   : "{{url('merchant/collection/normal/customer/statusupdate')}}",
                data  : 'customerId='+dataId+'&action='+actionType,
                success : function(data){
                    data=JSON.parse(data);
                    $.notify('<strong>'+data.message+'</strong>', {
                        'type': data.type,
                        offset: {
                          x:20,
                          y:100,
                        },
                        allow_dismiss: true,
                        newest_on_top: false,
                    });
                },
                error:function(requestObject, error, errorThrown){
                    
                  $.notify('<strong>Something went wrong , Try Again later!</strong>', {
                        'type': "danger",
                        offset: {
                          x:20,
                          y:100,
                        },
                        allow_dismiss: true,
                        newest_on_top: false,
                    });  
                }
            });
    }
}

</script>
@endsection