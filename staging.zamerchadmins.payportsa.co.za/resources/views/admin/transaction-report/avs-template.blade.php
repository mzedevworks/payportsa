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
          <form class="form-sample" method="get" action="{{url('admin/tranx-report/avs') }}" autocomplete="off">
            @csrf
            <div class="row">
              <div class="col-md-4">
                <div class="form-group row">
                  <label class="col-sm-5 col-form-label">Merchant</label>
                  <div class="col-sm-7">
                    <select class="form-control" id="firmid" name="firmid">
                      <option value="">Merchant Name</option>
                        @if(count($firms)>0)
                          @foreach($firms as $eachMerchant)
                            <option value="{{ $eachMerchant->id }}" >
                              {{ $eachMerchant->trading_as }}
                            </option>
                          @endforeach
                        @endif
                    </select>

                  </div>
                </div>
              </div>               
              <div class="col-md-4">
                <div class="form-group row">
                  <label class="col-sm-5 col-form-label">First Name</label>
                  <div class="col-sm-7">
                    <input type="text" class="form-control" placeholder="First Name" name="f_name" value="{{old('f_name',$request->f_name)}}">
                    
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group row">
                  <label class="col-sm-5 col-form-label">Last Name</label>
                  <div class="col-sm-7">
                    <input type="text" class="form-control" placeholder="Last Name" name="l_name" value="{{old('l_name',$request->l_name)}}">
                    
                  </div>
                </div>
              </div>
              
              <div class="col-md-4">
                <div class="form-group row">
                  <label class="col-sm-5 col-form-label">Avs Type</label>
                  <div class="col-sm-7">
                    <select class="form-control" name="serviceType" id="serviceType">
                      <option value="">--Select Type--</option>
                      <option value="business">Business</option>
                      <option value="individual">Individual</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                  <div class="form-group row">
                    <label class="col-sm-5 col-form-label">Account Number</label>
                    <div class="col-sm-7">
                      <input type="text" class="form-control recurringEntity" placeholder="Account Number" name="acc_num" value="{{ old('acc_num',$request->acc_num) }}">
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