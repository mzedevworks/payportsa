@extends('layouts.app')

@section('extra_style')

@endsection

@section('content')
  <div class="container-fluid">
    <div class="row">
      <div class="col-12 grid-margin">
        <div class="card">
          <div class="card-header border-bottom">
            <h4 class="card-title">View Customer</h4>
          </div>
          <div class="card-body">
            
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
              <p class="card-description">Collection Details(For OnceOff)</p>
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
              <p class="card-description">Collection Details(For Recurring)</p>
              <div class="row">  
                  <div class="col-md-6">
                        <div class="form-group row {{ $errors->first('recurring_start_date')?'has-error':'' }}">
                          <label class="col-sm-3 col-form-label">Recurring Start Date</label>
                          <div class="col-sm-9">
                            
                            <p>{{Helper::convertDate($cusRes['recurring_start_date'],'Y-m-d')}}</p>
                          </div>
                        </div>
                  </div> 
                  <div class="col-md-6">
                        <div class="form-group row {{ $errors->first('recurring_amount')?'has-error':'' }}">
                          <label class="col-sm-3 col-form-label">Recurring Amount</label>
                          <div class="col-sm-9">
                            <p>{{$cusRes['recurring_amount']}}</p>
                            
                          </div>
                        </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group row {{$errors->first('debit_frequency')?'has-error':''}}">
                      <label class="col-sm-3 col-form-label">Debit Frequency</label>
                      <div class="col-sm-9">
                        <p>{{$cusRes['debit_frequency']}}</p>
                        
                      </div>
                    </div>
                  </div>

                  <div class="col-md-6" id="duration">
                    <div class="form-group row {{ $errors->first('duration')?'has-error':''}}">
                      <label class="col-sm-3 col-form-label">Duration(In months)</label>
                      <div class="col-sm-9">
                        <p>{{$cusRes['duration']}}</p>
                        
                      </div>
                    </div>
                  </div>
              </div>
              
                <a href="{{url('merchant/collection/reoccur/customer/pendingupdate/').'/'.Helper::encryptVar($cusRes['id'])}}" class="btn btn-common">Edit</a>
                <a href="javascript:void(0);" onclick="takeAction('{{Helper::encryptVar($cusRes['id'])}}','approve');"  class="btn btn-common">Approve</a>
                <a href="javascript:void(0);" onclick="takeAction('{{Helper::encryptVar($cusRes['id'])}}','reject');"  class="btn btn-common">Reject</a>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('extra_script')

<script type="text/javascript">
  

function takeAction(dataId,actionType){
    confirmDialog("Are you sure to "+actionType+" this record?", (ans) => {
      if (ans) {
          $.ajax({
                
                type  : 'post',
                url   : "{{url('merchant/collection/reoccur/customer/statusupdate')}}",
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
     });
    
}

</script>
@endsection