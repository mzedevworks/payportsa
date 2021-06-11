@extends('auth.layouts.app')

@section('content')
              <img src="{{ asset('img/logo_new.png')}}">
              <div class="card-header border-bottom text-center">
                <h4 class="card-title">Reset Password</h4>
              </div>
              @if( $errors->any() )
                             <div class="alert alert-danger">
                                <strong>Following error occured !!</strong>
                                   <ul>
                                      @foreach( $errors->all() as $error )
                                         <li>{{ $error }}</li>
                                      @endforeach
                                   </ul>
                             </div>
              @endif
              @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                <a href="{{ route('login')}}" class="btn btn-common btn-block">Login</a>
              @else
              <div class="card-body">
                <form method="POST" action="{{ route('password.update') }}">
                        @csrf
                  <input type="hidden" name="token" value="{{ $token }}">
                  <div class="form-group">
                    <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ $email ?? old('email') }}" required autofocus placeholder="Email Address">
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
                     <input id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password_confirmation" required placeholder="Confirm Password">

                                @if ($errors->has('password'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                @endif
                  </div>
                  <div class="form-group text-center m-t-20">
                    <button class="btn btn-common btn-block" type="submit">Send Email</button>
                  </div>
                </form>
              </div>
              @endif

@endsection
