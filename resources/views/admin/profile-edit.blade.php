@extends('layouts.app')

@section('content')
  <div class="container-fluid">
    <div class="row">
      <div class="col-12 grid-margin">
        <div class="card">    
          <div class="card-body">
            @include('elements.message')
            <form class="form-sample" method="post" action="{{ url('admin/edit/profile') }}" autocomplete="off">
              @csrf
                  <div class="form-group row {{$errors->first('email')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Email</label>
                    <div class="col-sm-9">
                      <input type="email" class="form-control" placeholder="Email" name="email" value="{{ auth()->user()->email }}">
                      <p class="error">{{$errors->first('email')}}</p>
                    </div>
                  </div>
                
                  <div class="form-group row {{$errors->first('first_name')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">First Name</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="First Name" name="first_name" value="{{ auth()->user()->first_name }}">
                      <p class="error">{{$errors->first('first_name')}}</p>
                      
                    </div>
                  </div>
                
                  <div class="form-group row {{$errors->first('last_name')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Last Name</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="Last Name" name="last_name" value="{{ auth()->user()->last_name }}">
                      <p class="error">{{$errors->first('last_name')}}</p>
                    </div>
                  </div>
                
              <button type="submit" class="btn btn-common mr-3">Save</button>
              <a href="{{ url('admin/dashboard') }}" class="btn btn-light">Cancel</a>
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