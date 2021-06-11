@extends('auth.layouts.app')

@section('content')
             <img src="{{ asset('img/logo_new.png')}}">
             <div class="card-header border-bottom text-center">

                <h4 class="card-title">Sign In</h4>
              </div>
              <div class="card-body">
                @if( $errors->any() )
                     <div class="alert alert-danger">
                           <ul>
                              @foreach( $errors->all() as $error )
                                 <li>{{ $error }}</li>
                              @endforeach
                           </ul>
                     </div>
                @endif
                <form method="POST" action="{{ route('login') }}" class="form-horizontal m-t-20">
                  @csrf
                  <div class="form-group">
                    <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ old('email') }}" required autofocus placeholder="Email">

                                @if ($errors->has('email'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                @endif
                  </div>
                  <div class="form-group">
                    <input id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required placeholder="Password">

                                @if ($errors->has('password'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                @endif
                  </div>
                  <div class="form-group">
                    <div class="custom-control custom-checkbox">
                      <input type="checkbox" class="custom-control-input" id="customCheck1" name="remember" {{ old('remember') ? 'checked' : '' }}>
                      <label class="custom-control-label" for="customCheck1">Remember me</label>
                    </div>
                  </div>
                  <div class="form-group text-center m-t-20">
                    <button class="btn btn-common btn-block" type="submit">Log In</button>
                  </div>
                  <div class="form-group">
                    <div class="float-right">
                      <a href="{{ route('password.request') }}" class="text-muted"><i class="lni-lock"></i> Forgot your password?</a>
                    </div>
                  </div>
                </form>
</div>
@endsection
