@extends('layouts.app')

@section('content')
  <div class="container-fluid">
    <div class="row">
      <div class="col-12 grid-margin">
        <div class="card">    
          <div class="card-body">
            @include('elements.message')
            <form class="form-sample" method="post" action="{{ route('change.password') }}" autocomplete="off">
              @csrf
                  <div class="form-group row {{$errors->first('old_password')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Old Password</label>
                    <div class="col-sm-9">
                      <input type="password" class="form-control" placeholder="Old Password" name="old_password" value="">
                      <p class="error">{{$errors->first('old_password')}}</p>
                      
                    </div>
                  </div>
                
                  <div class="form-group row {{$errors->first('password')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">New Password</label>
                    <div class="col-sm-9">
                      <input type="password" class="form-control" placeholder="New Password" name="password" value="">
                      <p class="error">{{$errors->first('password')}}</p>
                      
                    </div>
                  </div>
                
                  <div class="form-group row {{$errors->first('password_confirmation')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Confirm Password</label>
                    <div class="col-sm-9">
                      <input type="password" class="form-control" placeholder="Confirm Password" name="password_confirmation" value="">
                      <p class="error">{{$errors->first('password_confirmation')}}</p>
                    </div>
                  </div>
                
              <button type="submit" class="btn btn-common mr-3">Save</button>
              <a href="{{ url('administors') }}" class="btn btn-light">Cancel</a>
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