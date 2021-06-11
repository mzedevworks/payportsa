@extends('layouts.app')



@section('content')
  <div class="container-fluid">
    <div class="row">
      <div class="col-12 grid-margin">
        <div class="card">
          <div class="card-header border-bottom">
            <h4 class="card-title"></h4>
          </div>
          <div class="card-body">
            
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Name</label>
                    <div class="col-sm-9">
                      <p>{{$empRes['first_name']}}</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group row ">
                    <label class="col-sm-3 col-form-label">Surname</label>
                    <div class="col-sm-9">
                      <p>{{$empRes['last_name']}}</p>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group row ">
                    <label class="col-sm-3 col-form-label">Email</label>
                    <div class="col-sm-9">
                      <p>{{$empRes['email']}}</p>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group row ">
                    <label class="col-sm-3 col-form-label">Employee No.</label>
                    <div class="col-sm-9">
                      <p>{{$empRes['id_number']}}</p>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Amount</label>
                    <div class="col-sm-9">
                      <p>{{$empRes['salary']}}</p>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group row ">
                    <label class="col-sm-3 col-form-label">Address</label>
                    <div class="col-sm-9">
                      <p>{{$empRes['address']}}</p>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Contact No.</label>
                    <div class="col-sm-9">
                      <p>{{$empRes['contact_number']}}</p>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group row ">
                    <label class="col-sm-3 col-form-label">Reference</label>
                    <div class="col-sm-9">
                      <p>{{$empRes['reference']}}</p>
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
                          {{ ($eachBank->id==$empRes['bank_id'])?$eachBank->bank_name:''}}
                        @endforeach
                      </p>
                      
                      
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group row ">
                    <label class="col-sm-3 col-form-label">Branch Code</label>
                    <div class="col-sm-9">
                      <p>{{$empRes['branch_code']}}</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Account Type</label>
                    <div class="col-sm-9">
                      <p>{{$empRes['account_type']}}</p>
                      
                    </div>
                  </div>
                </div>
                
                <div class="col-md-6">
                  <div class="form-group row ">
                    <label class="col-sm-3 col-form-label">Account Holder Name</label>
                    <div class="col-sm-9">
                      <p>{{$empRes['account_holder_name']}}</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group row">
                      <label class="col-sm-3 col-form-label">Account Number</label>
                      <div class="col-sm-9">
                      <p>{{$empRes['account_number']}}</p>
                      </div>
                    </div>
                </div>
                
              </div>
              
              <a href="{{url('merchant/employees/pendingupdate/').'/'.Helper::encryptVar($empRes['id'])}}" class="btn btn-common">Edit</a>
                <a href="javascript:void(0);" onclick="takeAction('{{Helper::encryptVar($empRes['id'])}}','approve');"  class="btn btn-common">Approve</a>
                <a href="javascript:void(0);" onclick="takeAction('{{Helper::encryptVar($empRes['id'])}}','reject');"  class="btn btn-common">Reject</a>
            
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
                url   : "{{url('merchant/employees/statusupdate')}}",
                data  : 'employeeId='+dataId+'&action='+actionType,
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