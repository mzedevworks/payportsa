@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
      <div class="col-12 grid-margin">
        <div class="card">
          <div class="card-body">
            <form class="form-sample" method="post" autocomplete="off">
              @csrf
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('first_name')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Name*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="First Name" name="first_name" value="{{  old('first_name',$merchant->first_name) }}">
                      <p class="error">{{$errors->first('first_name')}}</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('last_name')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Surname*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="Last Name" name="last_name" value="{{  old('last_name',$merchant->last_name) }}">
                      <p class="error">{{$errors->first('last_name')}}</p>
                      
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('email')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Email*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="Email" name="email" value="{{  old('email',$merchant->email) }}">
                      <p class="error">{{$errors->first('email')}}</p>
                    </div>
                  </div>
                </div>
                @if(!isset($merchant->id))
                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('password')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Password*</label>
                    <div class="col-sm-9">
                      <input type="password" class="form-control" placeholder="Password" name="password" value="">
                      <p class="error">{{$errors->first('password')}}</p>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('password')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Confirm Password*</label>
                    <div class="col-sm-9">
                      <input type="password" class="form-control" placeholder="Password" name="password_confirmation" value="">
                      <p class="error">{{$errors->first('password_confirmation')}}</p>
                    </div>
                  </div>
                </div>
                @endif
                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('role_id')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Role*</label>
                    <div class="col-sm-9">
                      <select class="form-control" name="role_id">
                        <option value="">Select</option>
                        {{ old('role_id') }}
                        @foreach($roles as $eachRole)
                          <option value="{{$eachRole->id}}" 
                             {{ $eachRole->id==old('role_id',$merchant['role_id'])?'selected':''}}>{{$eachRole->name}}</option>
                        @endforeach
                        
                      </select>
                      <p class="error">{{$errors->first('role_id')}}</p>
                      
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('contact_number')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Contact No.*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="Contact No." name="contact_number" value="{{  old('contact_number',$merchant->contact_number) }}">
                      <p class="error">{{$errors->first('contact_number')}}</p>
                    </div>
                  </div>
                </div>
                
                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('firm_id')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Please Select Merchant*</label>
                    <div class="col-sm-9">
                      <select class="form-control" name="firm_id">
                                  <option value="">Select</option>
                                  @foreach(Helper::getExistingFirms() as $firm)
                                    <option value="{{ $firm->id }}" 
                                   {{ $firm->id==old('firm_id',$merchant->firm_id) ? 'selected' : '' }}>{{ $firm->business_name }}</option>
                                  @endforeach
                      </select>
                      <p class="error">{{$errors->first('firm_id')}}</p>
                    </div>
                  </div>
                </div>
                
                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('status')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Status*</label>
                    <div class="col-sm-9">
                      <select class="form-control" name="status">
                          <option value="">Select</option>
                          @foreach(Config('constants.status') as $key => $value)
                            <option value="{{ $key }}" 
                            {{  $key==old('status',$merchant->status) ? 'selected' : '' }}>{{ $value }}</option>
                          @endforeach
                          
                      </select>
                      <p class="error">{{$errors->first('status')}}</p>
                    </div>
                  </div>
                </div>

              </div>          
              <button type="submit" class="btn btn-common mr-3">Update</button>
              <a href="{{ url('admin/merchants') }}" class="btn btn-light">Cancel</a>
            </form>
          </div>
        </div>
      </div>
    </div>
</div>
@endsection

@section('extra_script')
<script type="text/javascript">
  
</script>
@endsection