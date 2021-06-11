@extends('layouts.app')

@section('extra_style')
<link rel="stylesheet" href="{{ asset('css/jquery-ui.css') }}">

@endsection

@section('content')
  <div class="container-fluid">
    <div class="row">
      <div class="col-12 grid-margin">
        <div class="card">
          <div class="card-header border-bottom">
            <h4 class="card-title">Create AVS Request</h4>
          </div>
          
          <div class="card-body">
            <!-- <form class="form-sample" method="post" action="{{ isset($employee->id) && $employee->id!='' ? url('merchant/collection/normal/customer/'.encrypt($employee->id)) : url('merchant/collection/normal/customer/create') }}"> -->
              <!-- @csrf -->
              <div class="row">               
                
                <div class="col-md-12">
                  <div class="form-group row">
                    <label class="col-sm-3">Beneficiary Type *</label>
                  
                      <div class="col-sm-9">
                        <div class="custom-control custom-radio radio custom-control-inline">

                          <input type="radio" class="custom-control-input" id="avs_type_individual" name="avs_type" value="individual" checked onclick="updateFeildVisiblity()">
                          <label class="custom-control-label" for="avs_type_individual">Individual</label>
                        </div>
                      
                        <div class="custom-control custom-radio radio custom-control-inline">
                          
                          <input type="radio" class="custom-control-input" id="avs_type_business" name="avs_type" value="business" onclick="updateFeildVisiblity()">
                          <label class="custom-control-label" for="avs_type_business">Business</label>
                        </div>
                        <p class="error" id="error_avs_type">{{$errors->first('avs_type')}}</p>
                      </div>
                      
                  </div>
                </div>

                <div class="col-md-12 individualFeilds">
                  <div class="form-group row {{$errors->first('beneficiary_id_number')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Beneficiary ID Number/Passport*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" id="individualIdNumber" name="beneficiary_id_number" value="{{old('beneficiary_id_number')}}">
                      <p class="error" id="error_ind_beneficiary_id_number">{{$errors->first('beneficiary_id_number')}}</p>
                      
                    </div>
                  </div>
                </div>

                <div class="col-md-12 individualFeilds">
                  <div class="form-group row {{$errors->first('beneficiary_initial')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Beneficiary initials*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" id="individualInitial" name="beneficiary_initial" value="{{old('beneficiary_initial')}}">
                      <p class="error" id="error_ind_beneficiary_initial">{{$errors->first('beneficiary_initial')}}</p>
                      
                    </div>
                  </div>
                </div>

                <div class="col-md-12 individualFeilds">
                  <div class="form-group row {{$errors->first('beneficiary_last_name')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Beneficiary surname*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" id="individualLastName" name="beneficiary_last_name" value="{{old('beneficiary_last_name')}}">
                      <p class="error" id="error_ind_beneficiary_last_name">{{$errors->first('beneficiary_last_name')}}</p>
                      
                    </div>
                  </div>
                </div>

                <div class="col-md-12 businessFeilds d-none">
                  <div class="form-group row {{$errors->first('beneficiary_last_name')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Company Name*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" id="businessCompanyName" name="beneficiary_last_name" value="{{old('beneficiary_last_name')}}">
                      <p class="error" id="error_bus_beneficiary_last_name">{{$errors->first('beneficiary_last_name')}}</p>
                      
                    </div>
                  </div>
                </div>
                <div class="col-md-12 businessFeilds d-none">
                  <div class="form-group row {{$errors->first('beneficiary_id_number')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Company Registration Number*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" id="businessRegNumber" name="beneficiary_id_number" value="{{old('beneficiary_id_number')}}">
                      <p class="error" id="error_bus_beneficiary_id_number">{{$errors->first('beneficiary_id_number')}}</p>
                      
                    </div>
                  </div>
                </div>
                <div class="col-md-12">
                  <div class="form-group row {{$errors->first('bank_name')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Bank Name*</label>
                    <div class="col-sm-9">
                     <!--  <input type="text" class="form-control" id="bankName" name="bank_name" value="{{ old('bank_name') }}"> -->
                      <select class="form-control" name="bank_name" id="bankName">
                        <option value="">Select</option>
                        @foreach(Helper::getBankInfo(['is_realtime_avs'=>'yes','is_active'=>'yes']) as $eachBank)
                          <option value="{{$eachBank->id}}" {{$eachBank->id==old('bank_id')?'selected':''}}>{{$eachBank->bank_name}}</option>
                        @endforeach
                      </select>
                      <p class="error" id="error_bank_name">{{$errors->first('bank_name')}}</p>
                    </div>
                  </div>
                </div>

                <div class="col-md-12">
                  <div class="form-group row {{ $errors->first('branch_code')?'has-error':'' }}">
                    <label class="col-sm-3 col-form-label">Branch Code*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" name="branch_code" id="branch_code" value="{{ old('branch_code') }}">
                      <p class="error" id="error_branch_code">{{ $errors->first('branch_code')}}</p>
                    </div>
                  </div>
                </div>
                
                <div class="col-md-12">
                    <div class="form-group row {{ $errors->first('bank_account_number')?'has-error':'' }}">
                      <label class="col-sm-3 col-form-label">Account Number*</label>
                      <div class="col-sm-9">
                        <input type="text" class="form-control" id="accountNumber" name="bank_account_number" value="{{ old('bank_account_number') }}">
                        <p class="error" id="error_bank_account_number">{{ $errors->first('bank_account_number')}}</p>
                      </div>
                    </div>
                </div>
                <div class="col-md-12">
                  <div class="form-group row {{ $errors->first('bank_account_type')?'has-error':'' }}">
                    <label class="col-sm-3 col-form-label">Account Type *</label>
                    <div class="col-sm-9">
                      <select class="form-control" name="account_type" id="accountType">
                        <option value="">Select Type</option>
                         @foreach(Config('constants.accountType') as $key => $type)
                          <option value="{{ $type }}" {{$type==old('bank_account_type')?'selected':''}}
                         >{{ $type }}</option>
                        @endforeach
                      </select>
                      <p class="error" id="error_bank_account_type">{{ $errors->first('bank_account_type')}}</p>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="row">
                <div class="col-md-12 ml-3">
                  <div class="form-group row {{$errors->first('consent')?'has-error':''}}">
                    <div class="custom-control custom-checkbox checkbox custom-control-inline">
                        <input type="checkbox" class="custom-control-input" name="concent_check" id="concent_check" value="yes" onclick="concentUpdate();" {{ (old('concent_check')=='yes') ? 'checked': ''}}>
                        <label class="custom-control-label" for="concent_check">I have obtained consent from the individual beneficiary or entity on use of ID number or Company registration number for the purpose of this account verification</label>
                        <p class="error" id="error_consent">{{ $errors->first('bank_account_type')}}</p>
                      </div>
                  </div>
                </div>
              </div>
              
              
              
             
              <button id="concentSubmitBtn" type="button" class="btn btn-common mr-3" onclick="submitAvs();" disabled="disabled">Submit</button>
              <a href="{{ url('avs/history/realtime') }}" class="btn btn-light">Cancel</a>
            <!-- </form> -->
          </div>
          <div class="row">
            <div class="wait-loader"  style="display: none">
              <div class="loader">
                
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
@endsection

@section('extra_script')

<script src="{{ asset('js/jquery-ui.js') }}"></script>
<script type="text/javascript">


function updateFeildVisiblity(){
    var avsValue = $("input[name='avs_type']:checked").val();
    if(avsValue=='individual'){
      $(".individualFeilds").removeClass('d-none');
      $(".businessFeilds").addClass('d-none');
    }else{
      $(".businessFeilds").removeClass('d-none');
      $(".individualFeilds").addClass('d-none');
    }
    
}

function concentUpdate(){
    if($("#concent_check").prop("checked") == true){
        $("#concentSubmitBtn").attr("disabled",false);
    }else if($("#concent_check").prop("checked") == false){
        $("#concentSubmitBtn").attr("disabled",true);
    }
}
var resentCount=0;
function resendAvs(avsEnquiryId){
  resentCount++;
  console.log(resentCount,avsEnquiryId,new Date());
  //setInterval(function(){ alert("Hello"); }, 3000);
  if(resentCount<=11){
    $.ajax({
                
            type  : 'post',
            url   : "{{url('merchant/avs/ajax-recheck-avs')}}",
            data  : { "_token": "{{ csrf_token() }}",avsEnquiryId},
            success : function(data){
                var avsEnquiryData=data.data;
                if(data.status=='sucessful'){
                  window.location = "{{url('merchant/avs/result/')}}/"+avsEnquiryData.encryptedId;
                }else if(data.status=='pending'){
                  setTimeout(resendAvs(avsEnquiryData.id),5000);
                }else if(data.status=='rejected' || data.status=='failed'){
                  $(".wait-loader").css('display','none');
                  $.notify('<strong>'+data.msg+'</strong>', {
                      'type': data.type,
                      offset: {
                        x:20,
                        y:100,
                      },
                      allow_dismiss: true,
                      newest_on_top: false,
                  });
                }

            },
            error:function(requestObject, error, errorThrown){
              
              var errors=requestObject.responseJSON.errors;
              $(".wait-loader").css('display','none');
              $.notify('<strong>'+requestObject.responseJSON.msg+'</strong>', {
                    'type': "danger",
                    offset: {
                      x:20,
                      y:100,
                    },
                    allow_dismiss: true,
                    newest_on_top: false,
              });  
            },complete:function(){
                
            }
        });
    
  }else{
    $(".wait-loader").css('display','none');
    $.notify('<strong>Request timeout, please try again later!</strong>', {
              'type': "danger",
              offset: {
                x:20,
                y:100,
              },
              allow_dismiss: true,
              newest_on_top: false,
        });  
  }
  
}


function submitAvs(){
    resentCount=0;
    if($("#concent_check").prop("checked") == false){
        alertDialog("Please check the consent checkbox", (ans) => {});
    }else if($("#concent_check").prop("checked") == true){
      var avsType=$("input[name='avs_type']:checked").val();
      if(avsType=='individual'){
        var identityNumber=$("#individualIdNumber").val();
        var initials=$("#individualInitial").val();
        var lastName=$("#individualLastName").val();
        

      }else if(avsType=='business'){
        var identityNumber=$("#businessRegNumber").val();
        var initials='';
        var lastName=$("#businessCompanyName").val();
      }else{
        return false;
      }
      
      var bankName=$("#bankName").val();
      var branchCode=$("#branch_code").val();
      var accountNumber=$("#accountNumber").val();
      var accountType=$("#accountType").val();
      $(".error").text("");
      $(".has-error").removeClass("has-error");
      $(".wait-loader").css('display','block');
      $.ajax({
                
                type  : 'post',
                url   : "{{url('merchant/avs/save-request')}}",
                data  : { "_token": "{{ csrf_token() }}",avsType,identityNumber, initials,lastName,bankName,branchCode,accountNumber,accountType,'consent':'yes'},
                success : function(data){
                    var avsEnquiryData=data.data;
                    if(data.status=='sucessful'){
                      window.location = "{{url('merchant/avs/result/')}}/"+avsEnquiryData.encryptedId;
                    }else if(data.status=='pending'){
                      setTimeout(resendAvs(avsEnquiryData.id),50000);
                    }else if(data.status=='rejected' || data.status=='failed'){

                    $(".wait-loader").css('display','none');
                    
                      $.notify('<strong>'+data.msg+'</strong>', {
                          'type': data.type,
                          offset: {
                            x:20,
                            y:100,
                          },
                          allow_dismiss: true,
                          newest_on_top: false,
                      });
                    }

                },
                error:function(requestObject, error, errorThrown){
                  $(".wait-loader").css('display','none');
                  var errors=requestObject.responseJSON.errors;
                  $(".error").text("");
                  $(".has-error").removeClass("has-error");
                  Object.keys(errors).forEach(function (element,index){
                    
                    switch (element) {
                      case 'avs_type':
                        $("#error_avs_type").text(errors[element][0]);
                        $("#error_avs_type").parent().parent().addClass("has-error");
                        break;
                      case 'beneficiary_id_number':
                        if(avsType=='individual'){
                          $("#error_ind_beneficiary_id_number").text(errors[element][0]);
                          $("#error_ind_beneficiary_id_number").parent().parent().addClass("has-error");
                        }else if(avsType=='business'){
                          $("#error_bus_beneficiary_id_number").text(errors[element][0]);
                          $("#error_bus_beneficiary_id_number").parent().parent().addClass("has-error");
                        }
                        
                        break;
                      case 'beneficiary_initial':
                        if(avsType=='individual'){
                          $("#error_ind_beneficiary_initial").text(errors[element][0]);
                          $("#error_ind_beneficiary_initial").parent().parent().addClass("has-error");
                        }
                        break;
                      case 'beneficiary_last_name':
                        if(avsType=='individual'){
                          $("#error_ind_beneficiary_last_name").text(errors[element][0]);
                          $("#error_ind_beneficiary_last_name").parent().parent().addClass("has-error");
                        }else if(avsType=='business'){
                          $("#error_bus_beneficiary_last_name").text(errors[element][0]);
                          $("#error_bus_beneficiary_last_name").parent().parent().addClass("has-error");
                        }
                         
                        break;

                      case 'bank_name':
                        $("#error_bank_name").text(errors[element][0]);
                        $("#error_bank_name").parent().parent().addClass("has-error");
                        break;
                      case 'branch_code':
                        $("#error_branch_code").text(errors[element][0]);
                        $("#error_branch_code").parent().parent().addClass("has-error");
                        break;
                      case 'bank_account_number':
                        $("#error_bank_account_number").text(errors[element][0]);
                        $("#error_bank_account_number").parent().parent().addClass("has-error");
                        break;
                      case 'consent':
                        $("#error_consent").text(errors[element][0]);
                        $("#error_consent").parent().parent().addClass("has-error");
                        break;
                      case 'bank_account_type':
                        $("#error_bank_account_type").text(errors[element][0]);
                        $("#error_bank_account_type").parent().parent().addClass("has-error");
                    }
                  });
                    
                  $.notify('<strong>'+requestObject.responseJSON.msg+'</strong>', {
                        'type': "danger",
                        offset: {
                          x:20,
                          y:100,
                        },
                        allow_dismiss: true,
                        newest_on_top: false,
                  });  
                },complete:function(){
                    
                }
            });


    }
}


$(document).ready(function(){

  
});  

  
</script>
@endsection