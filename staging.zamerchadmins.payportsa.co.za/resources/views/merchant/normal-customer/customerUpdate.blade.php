@extends('layouts.app')

@section('extra_style')
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
@endsection

@section('content')
  <div class="container-fluid">
    <div class="row">
      <div class="col-12 grid-margin">
        <div class="card">
          <div class="card-header border-bottom">
            <h4 class="card-title">Update Customer</h4>
          </div>
          <div class="card-body">
            <form class="form-sample" method="post" autocomplete="off">
              @csrf
              <p class="card-description">Customer's Personal Information</p>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('first_name')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">First Name*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="First Name" name="first_name" value="{{old('first_name',$cusRes['first_name'])}}">
                      <p class="error">{{$errors->first('first_name')}}</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('last_name')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Last Name*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="Last Name" name="last_name" value="{{old('last_name',$cusRes['last_name'])}}">
                      <p class="error">{{$errors->first('last_name')}}</p>
                      
                    </div>
                  </div>
                </div>
              
                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('email')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Email</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="Email" name="email" value="{{old('email',$cusRes['email'])}}">
                      <p class="error">{{$errors->first('email')}}</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('contact_number')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Cellphone</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="Contact No." name="contact_number" value="{{old('contact_number',$cusRes['contact_number'])}}">
                      <p class="error">{{$errors->first('contact_number')}}</p>
                    </div>
                  </div>
                </div>
                
                <div class="col-md-6">
                  <div class="form-group row">
                    <label class="col-sm-3">Status *</label>
                  
                      <div class="col-sm-9">
                        <div class="custom-control custom-radio radio custom-control-inline">

                          <input type="radio" class="custom-control-input" id="status_active" name="status" value="1" {{ (intval(old('status',$cusRes['status']))===1) ? 'checked': ''}}>
                          <label class="custom-control-label" for="status_active">Active</label>
                        </div>
                      
                        <div class="custom-control custom-radio radio custom-control-inline">
                          
                          <input type="radio" class="custom-control-input" id="status_inactive" name="status" value="2" {{ (intval(old('status',$cusRes['status']))===2) ? 'checked': ''}}>
                          <label class="custom-control-label" for="status_inactive">In-active </label>
                        </div>
                        <p class="error">{{$errors->first('status')}}</p>
                      </div>
                      
                  </div>
                </div>

              </div>
              <p class="card-description">Account Details</p>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group row {{ $errors->first('account_holder_name')?'has-error':'' }}">
                    <label class="col-sm-3 col-form-label">Account Holder Name*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" name="account_holder_name" value="{{old('account_holder_name',$cusRes['account_holder_name'])}}" placeholder="Account Holder Name">
                      <p class="error">{{ $errors->first('account_holder_name')}}</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group row {{ $errors->first('account_number')?'has-error':'' }}">
                      <label class="col-sm-3 col-form-label">Account Number*</label>
                      <div class="col-sm-9">
                        <input type="text" class="form-control" placeholder="Account Number" name="account_number" value="{{old('account_number',$cusRes['account_number'])}}">
                        <p class="error">{{ $errors->first('account_number')}}</p>
                      </div>
                    </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group row {{ $errors->first('account_type')?'has-error':'' }}">
                    <label class="col-sm-3 col-form-label">Account Type*</label>
                    <div class="col-sm-9">
                      <select class="form-control" name="account_type">
                        <option value="">--Select Account Type--</option>
                        @foreach(Config('constants.collectionAccountType') as $key => $type)
                        <option value="{{ $type }}" 
                        {{ $type===old('account_type',$cusRes['account_type'])?'selected':''}}
                       >{{ $type }}</option>
                        @endforeach
                      </select>
                      <p class="error">{{ $errors->first('account_type')}}</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('bank_id')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Bank Name*</label>
                    <div class="col-sm-9">
                      <select class="form-control" name="bank_id" id="bank_id">
                        <option value="">Select</option>
                        @foreach(Helper::getBankDetails() as $eachBank)
                          <option value="{{$eachBank->id}}" 
                          {{ $eachBank->id==old('bank_id',$cusRes['bank_id'])?'selected':''}}>{{$eachBank->bank_name}}</option>
                        @endforeach
                      </select>
                      <p class="error">{{$errors->first('bank_id')}}</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group row {{ $errors->first('branch_code')?'has-error':'' }}">
                    <label class="col-sm-3 col-form-label">Branch Code*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" name="branch_code" id="branch_code" value="{{old('branch_code',$cusRes['branch_code'])}}" readonly="">
                      <p class="error">{{ $errors->first('branch_code')}}</p>
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
                      <input type="text" class="form-control" placeholder="Address Line 1" name="address_one" value="{{old('address_one',$cusRes['address_one'])}}">
                      <p class="error">{{$errors->first('address_one')}}</p>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('address_line_two')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Address Line 2</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="Address Line 2" name="address_line_two" vvalue="{{old('address_line_two',$cusRes['address_line_two'])}}">
                      <p class="error">{{$errors->first('address_line_two')}}</p>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('subrub')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Suburb</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="Suburb" name="suburb" value="{{old('subrub',$cusRes['subrub'])}}">
                      <p class="error">{{$errors->first('subrub')}}</p>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('city')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">City</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="City" name="city" value="{{old('city',$cusRes['city'])}}">
                      <p class="error">{{$errors->first('city')}}</p>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('province')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Province</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="Province" name="province" value="{{old('province',$cusRes['province'])}}">
                      <p class="error">{{$errors->first('province')}}</p>
                    </div>
                  </div>
                </div>

              </div>
              <p class="card-description">Service Details</p>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('mandate_id')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Mandate Id*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="Mandate Id" name="mandate_id" value="{{old('mandate_id',$cusRes['mandate_id'])}}">
                      <p class="error">{{$errors->first('mandate_id')}}</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('id_number')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Id No.</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="Id Number" name="id_number" value="{{old('id_number',$cusRes['id_number'])}}">
                      <p class="error">{{$errors->first('id_number')}}</p>
                    </div>
                  </div>
                </div>
                
              </div>
              <?php /*
              <p class="card-description">Collection Details(For OnceOff)</p>
              <div class="row">
                <div class="col-md-6">
                    <div class="form-group row {{ $errors->first('collection_date')?'has-error':'' }}">
                      <label class="col-sm-3 col-form-label">Collection Date*</label>
                      <div class="col-sm-9">
                        @php
                        $collection_date = old('collection_date',$cusRes['collection_date']);
                        @endphp
                        <input type="text" class="form-control" placeholder="Collection Date" name="collection_date" value="{{ Helper::convertDate($collection_date,'Y-m-d')}}
                        " id="collection_date">
                        <p class="error">{{ $errors->first('collection_date')}}</p>
                      </div>
                    </div>
                </div>
                <div class="col-md-6">
                      <div class="form-group row {{ $errors->first('once_off_amount')?'has-error':'' }}">
                        <label class="col-sm-3 col-form-label">OnceOff Amount*</label>
                        <div class="col-sm-9">
                          <input type="text" class="form-control" placeholder="OnceOff Amount" name="once_off_amount" value="{{old('once_off_amount',$cusRes['once_off_amount'])}}">
                          <p class="error">{{ $errors->first('once_off_amount')}}</p>
                        </div>
                      </div>
                </div> 
              </div>
              */ ?>
              
                <button type="submit" class="btn btn-common mr-3">Update</button>
                <a href="{{ url('merchant/collection/normal/customers') }}" class="btn btn-light">Cancel</a>
              </form>
            
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
  var holidays = <?php echo json_encode($holidayDates); ?>;
  var bank_id = '{{ old("bank_id",$cusRes['bank_id'])}}';

$(document).ready(function(){
  
  getCollectionDate('2 Day',true,'normal');

  

  $('#service_type').on('change',function(){
    var service_type = $('#service_type').val();
    $('#collection_date').datepicker('destroy');
    $('#recurring_start_date').datepicker('destroy');
    getCollectionDate(service_type,true,'normal');
  });

  if(bank_id!=''){
    getBranchCode(bank_id);
  }
  
  $('#bank_id').on('change',function(){
     getBranchCode($(this).val());
  });
});

</script>
@endsection