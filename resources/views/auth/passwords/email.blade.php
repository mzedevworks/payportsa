@extends('auth.layouts.app')

@section('content')
              <img src="{{ asset('img/logo_new.png')}}">
              <div class="card-header border-bottom text-center">
                <h4 class="card-title">Reset Password</h4>
              </div>
              @if (session('status'))
                        <div class="alert alert-{{ session('class') }}" role="alert">
                            {{ session('status') }}
                        </div>
              @else
              <div class="alert alert-info alert-dismissible">
                  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>Enter your Registered <b>Email Address
              </div>
              @endif
              <div class="card-body">
                <form method="POST" action="{{ route('password.email') }}" class="form-horizontal m-t-20">
                        @csrf
                  <div class="form-group">
                    <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" placeholder="Email" name="email" value="{{ old('email') }}" required>

                                @if ($errors->has('email'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                @endif
                  </div>
                  <div class="form-group text-center m-t-20">
                    <button class="btn btn-common btn-block" type="submit">Send Email</button>
                  </div>
                </form>
              </div>
@endsection
