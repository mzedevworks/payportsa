@extends('layouts.app')

@section('extra_style')
<link rel="stylesheet" href="{{ asset('css/jquery-ui.css') }}">
@endsection

@section('content')
<div class="container-fluid">
  <div class="row">
    <div class="col-12 grid-margin">
      <div class="card">
        <div class="card-header border-bottom">
          <h4 class="card-title">Filters</h4>
        </div>
        
        <div class="card-body">
          <form class="form-sample" method="get" action="{{url('merchant/collection/reoccur/reports') }}" autocomplete="off">
            @csrf
            <div class="row">               
              <div class="col-md-4">
                <div class="form-group row">
                  <label class="col-sm-5 col-form-label">Mandate Id</label>
                  <div class="col-sm-7">
                    <input type="text" class="form-control" placeholder="Mandate Id" name="mandate_id" value="{{old('mandate_id',$request->mandate_id)}}">
                    
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group row">
                  <label class="col-sm-5 col-form-label">First Name</label>
                  <div class="col-sm-7">
                    <input type="text" class="form-control" placeholder="First Name" name="first_name" value="{{old('first_name',$request->first_name)}}">
                    
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group row">
                  <label class="col-sm-5 col-form-label">Last Name</label>
                  <div class="col-sm-7">
                    <input type="text" class="form-control" placeholder="Last Name" name="last_name" value="{{old('last_name',$request->last_name)}}">
                    
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                  <div class="form-group row">
                    <label class="col-sm-5 col-form-label">Amount</label>
                    <div class="col-sm-7">
                      <input type="text" class="form-control recurringEntity" placeholder="Amount" name="amount" value="{{ old('amount',$request->amount) }}">
                    </div>
                  </div>
              </div>
              <div class="col-md-4">
                  <div class="form-group row">
                    <label class="col-sm-5 col-form-label">From Date</label>
                    <div class="col-sm-7">
                      <input type="text" class="form-control collectionDate" placeholder="YYYY/MM/DD" id="startat"  name="startat" value="{{old('startat',$request->startat)}}">
                    </div>
                  </div>
              </div>
              <div class="col-md-4">
                  <div class="form-group row">
                    <label class="col-sm-5 col-form-label">Up to Date</label>
                    <div class="col-sm-7">
                      <input type="text" class="form-control collectionDate" placeholder="YYYY/MM/DD" name="upto" id="upto" value="{{old('upto',$request->upto)}}">
                    </div>
                  </div>
              </div>
              <div class="col-md-4">
                <div class="form-group row">
                  <label class="col-sm-5 col-form-label">Status</label>
                  <div class="col-sm-7">
                    <select class="form-control" name="status">
                      <option value="-1">--Select Status--</option>
                      @foreach(config('constants.transactionStatus') as $key => $type)
                      <option value="{{ $type['value'] }}">{{ $type['title'] }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
              </div>
            </div>
            <button type="submit" class="btn btn-common mr-3">Search</button>
            
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('extra_script')
<script src="{{ asset('js/jquery-1.12.4.js') }}"></script>
<script src="{{ asset('js/jquery-ui.js') }}"></script>
<script type="text/javascript">


$(document).ready(function(){

$('.collectionDate').datepicker({
      
      //maxDate: '+0d',
      dateFormat: 'yy-mm-dd',
      changeYear : true,
      changeMonth : true
    });

});

  
</script>
@endsection