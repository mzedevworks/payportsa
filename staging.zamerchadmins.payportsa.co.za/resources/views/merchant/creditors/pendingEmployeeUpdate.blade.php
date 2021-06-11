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
            @if($empRes['status']!=2)
            <form class="form-sample" method="post" autocomplete="off">
              @csrf
            @endif 
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('first_name')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Name*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="First Name" name="first_name" value="{{old('first_name',$empRes['first_name'])}}">
                      <p class="error">{{$errors->first('first_name')}}</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('last_name')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Surname*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="Last Name" name="last_name" value="{{old('last_name',$empRes['last_name'])}}">
                      <p class="error">{{$errors->first('last_name')}}</p>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('email')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Email</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="Email" name="email" value="{{ old('email',$empRes['email'])}}">
                      <p class="error">{{$errors->first('email')}}</p>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('id_number')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Employee No.*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="ID Number" name="id_number" value="{{old('id_number',$empRes['id_number'])}}">
                      <p class="error">{{ $errors->first('id_number') }}</p>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group row {{ $errors->first('salary')?'has-error':'' }}">
                    <label class="col-sm-3 col-form-label">Amount*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="Salary" name="salary" value="{{old('salary',$empRes['salary'])}}">
                      <p class="error">{{$errors->first('salary')}}</p>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group row 
                  {{ $errors->first('address') ? 'has-error' : '' }}">
                    <label class="col-sm-3 col-form-label">Address</label>
                    <div class="col-sm-9">
                      <textarea class="form-control" name="address">{{old('address',$empRes['address'])}}</textarea>
                      <p class="error">{{$errors->first('address')}}</p>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('contact_number')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Contact No.</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="Contact No." name="contact_number" value="{{old('contact_number',$empRes['contact_number'])}}">
                      <p class="error">{{$errors->first('contact_number')}}</p>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('reference')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Reference*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="Reference" name="reference" value="{{old('reference',$empRes['reference'])}}">
                      <p class="error">{{$errors->first('reference')}}</p>
                    </div>
                  </div>
                </div>
              </div>
              <p class="card-description">Bank Account Info.</p>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group row {{ $errors->first('bank_id')?'has-error':'' }}">
                    <label class="col-sm-3 col-form-label">Bank Name*</label>
                    <div class="col-sm-9">
                      <select class="form-control" name="bank_id" id="bank_id">
                        <option value="">--Select Bank--</option>
                        @foreach(Helper::getBankDetails() as $eachBank)
                        <option value="{{ $eachBank->id }}" 
                        {{ $eachBank->id==old('bank_id',$empRes['bank_id'])?'selected':''}}
                        >{{ $eachBank->bank_name }}</option>
                        @endforeach
                      </select>
                      <p class="error bank_error">{{ $errors->first('bank_id')}}</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group row {{ $errors->first('branch_code')?'has-error':'' }}">
                    <label class="col-sm-3 col-form-label">Branch Code*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" name="branch_code" id="branch_code" value="{{old('branch_code',$empRes['branch_code'])}}" readonly="">
                      <p class="error">{{ $errors->first('branch_code')}}</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group row {{ $errors->first('account_type')?'has-error':'' }}">
                    <label class="col-sm-3 col-form-label">Account Type*</label>
                    <div class="col-sm-9">
                      <select class="form-control" name="account_type">
                        @foreach(Config('constants.paymentAccountType') as $key => $type)
                        <option value="{{ $type }}" 
                        {{ $type===old('account_type',$empRes['account_type'])?'selected':''}}
                        >{{ $type }}</option>
                        @endforeach
                      </select>
                      <p class="error">{{ $errors->first('account_type')}}</p>
                    </div>
                  </div>
                </div>
                
                <div class="col-md-6">
                  <div class="form-group row {{ $errors->first('account_holder_name')?'has-error':'' }}">
                    <label class="col-sm-3 col-form-label">Account Holder Name*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" name="account_holder_name" value="{{old('account_holder_name',$empRes['account_holder_name'])}}">
                      <p class="error">{{ $errors->first('account_holder_name')}}</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group row {{ $errors->first('account_number')?'has-error':'' }}">
                      <label class="col-sm-3 col-form-label">Account Number*</label>
                      <div class="col-sm-9">
                        <input type="text" class="form-control" name="account_number" value="{{old('account_number',$empRes['account_number'])}}">
                        <p class="error">{{ $errors->first('account_number')}}</p>
                      </div>
                    </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group row {{ $errors->first('status')?'has-error':'' }}">
                    <label class="col-sm-3 col-form-label">Status*</label>
                    <div class="col-sm-9">
                      <select class="form-control" name="status">
                        
                        @foreach(Config('constants.status') as $key => $value)
                          <option value="{{ $key }}" {{ $key===old('status',$empRes['account_type'])?'selected':''}}
                          >{{ $value }}</option>
                        @endforeach
                        
                      </select>
                      <p class="error">{{$errors->first('status')}}</p>
                    </div>
                  </div>
                </div>
              </div>
              
              @if($empRes['status']!=2)
                <button type="submit" class="btn btn-common mr-3">Update</button>
                <a href="{{ url('merchant/employees/pending-list') }}" class="btn btn-light">Cancel</a>
              </form>
              @endif
            
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('extra_script')

<script type="text/javascript">
  
  var bank_id = '{{ old("bank_id",$empRes["bank_id"])}}';
  if(bank_id!=''){
    getBranchCode(bank_id);
  }
$(document).ready(function(){
  $('#bank_id').on('change',function(){
     getBranchCode($(this).val());
  });
});

</script>
@endsection