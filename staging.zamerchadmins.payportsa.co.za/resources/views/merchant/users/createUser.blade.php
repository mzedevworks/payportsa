@extends('layouts.app')

@section('content')
  <div class="container-fluid">
    <div class="row">
      <div class="col-12 grid-margin">
        <div class="card">
          <div class="card-header border-bottom">
            <h4 class="card-title">Add Merchant/Capturer User</h4>
          </div>
          
          <div class="card-body">
            <form class="form-sample" method="post" action="{{{ url('merchant/users/create') }}}" autocomplete="off">
              @csrf
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('first_name')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">First Name*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="First Name" name="first_name" value="{{old('first_name')}}">
                      <p class="error">{{$errors->first('first_name')}}</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('last_name')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Last Name*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="Last Name" name="last_name" value="{{old('last_name')}}">
                      <p class="error">{{$errors->first('last_name')}}</p>
                    </div>
                  </div>
                </div>
              
                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('email')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Email*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="Email" name="email" value="{{old('email')}}">
                      <p class="error">{{$errors->first('email')}}</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('role_id')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Role*</label>
                    <div class="col-sm-9">
                      <select class="form-control" name="role_id">
                        <option value="">Select</option>
                        @foreach($roles as $eachRole)
                          <option value="{{$eachRole->id}}" {{$eachRole->id==old('role_id')?'selected':''}}>{{$eachRole->name}}</option>
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
                      <input type="text" class="form-control" placeholder="Contact No." name="contact_number" value="{{old('contact_number')}}">
                      <p class="error">{{$errors->first('contact_number')}}</p>
                    </div>
                  </div>
                </div>
              
                
                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('status')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Status*</label>
                    <div class="col-sm-9">
                      <select class="form-control" name="status">
                        <option value="">Select</option>
                        @foreach(config('constants.userStatus') as $eachUserStatus)
                          <option value="{{$eachUserStatus['value']}}" {{$eachUserStatus['value']===old('status')?'selected':''}}>{{$eachUserStatus['title']}}</option>
                        @endforeach
                        
                      </select>
                      <p class="error">{{$errors->first('status')}}</p>
                    </div>
                  </div>
                </div>
              </div>
              
              
              <button type="submit" class="btn btn-common mr-3">Update</button>
              <a href="{{ url('merchant/users') }}" class="btn btn-light">Cancel</a>
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