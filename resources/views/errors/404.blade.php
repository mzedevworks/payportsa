@extends('layouts.app-error')

@section('content')
<div class="container">
        <div class="row">
          <div class="col-12">
            <div class="d-flex justify-content-center h-100vh w-100vw align-items-center">
              <div class="error-container text-center">
                <h1 class="error-number">404</h1>
                <h2 class="semi-bold">This page is not found.</h2>
                <p class="p-b-10">Sorry, this page doesn't exist.</p>
                <div><a href="javascript:history.back()" class="btn btn-common btn-rounded btn-lg">Back</a></div>
              </div>
            </div>
          </div>
        </div>
      </div>
@endsection