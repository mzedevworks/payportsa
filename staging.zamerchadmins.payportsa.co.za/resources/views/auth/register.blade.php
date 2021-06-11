@extends('auth.layouts.app')

@section('content')
<img src="{{ asset('img/logo_new.png')}}">
<div class="card-header border-bottom text-center">
                <h4 class="card-title">Register</h4>
              </div>
              <div class="card-body">
                 <form method="POST" action="{{ route('register') }}" class="form-horizontal m-t-20">
                        @csrf
                  <div class="form-group">
                    <input id="name" type="text" class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }}" name="name" value="{{ old('name') }}" required autofocus placeholder="Name">

                                @if ($errors->has('name'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('name') }}</strong>
                                    </span>
                                @endif
                  </div>
                  <div class="form-group">
                    <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ old('email') }}" required placeholder="Email">

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
                    
                        <input id="password-confirm" placeholder="Confirm Password" type="password" class="form-control" name="password_confirmation" required >
                        
                  </div>
                  <div class="form-group">
                    <div class="custom-control custom-checkbox">
                      <input type="checkbox" class="custom-control-input" id="customCheck1" required>
                      <label class="custom-control-label" for="customCheck1" name="terms_and_conditions">I accept Terms and Conditions</label>
                    </div>
                  </div>
                  <div class="form-group text-center m-t-20">
                    <button class="btn btn-common btn-block" type="submit">Register</button>
                  </div>
                  <div class="form-group m-t-10 mb-0">
                    <div class="text-center">
                      <a href="{{ route('login') }}" class="text-muted">Already have account?</a>
                    </div>
                  </div>
                </form>
              </div>


@endsection
