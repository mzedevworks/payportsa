@extends('layouts.app')
@section('extra_style')
<link rel="stylesheet" href="{{ asset('css/jquery-ui.css') }}">
@endsection
@section('content')
<div class="container-fluid">
    <div class="row">
      <div class="col-12 grid-margin">
        <div class="card">
          <div class="card-body">
          @include('elements.message')
            <form class="form-sample" method="post" 
            action="{{ url('merchant/creditors/create-batch') }}" autocomplete="off">
              @csrf
              <div class="row">
                <p class="error">{{$errors->first('salaryBatchAmount')}}</p>
                <div class="col-md-12">
                  <div class="form-group row {{$errors->first('batch_name')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Batch Name*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="Batch Name" name="batch_name" value="{{ old('batch_name') }}">
                      <p class="error">{{$errors->first('batch_name')}}</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-12">
                  <div class="form-group row">
                    <label class="col-sm-3">Service Type *</label>
                  
                      <div class="col-sm-9">
                        <div class="custom-control custom-radio radio custom-control-inline">
                          
                          <input type="radio" class="custom-control-input" id="sameday_service" name="service_type" value="sameday" {{ (old('service_type')=="sameday") ? 'checked': ''}} onclick="handleServiceTypeClick()">
                          <label class="custom-control-label" for="sameday_service">Same Day</label>
                        </div>
                      
                        <div class="custom-control custom-radio radio custom-control-inline">
                          
                          <input type="radio" class="custom-control-input" id="dated_service" name="service_type" value="dated" {{ (old('service_type')=="dated") ? 'checked': ''}} onclick="handleServiceTypeClick()">
                          <label class="custom-control-label" for="dated_service">Dated</label>
                        </div>
                        <p class="error">{{$errors->first('service_type')}}</p>
                      </div>
                  </div>
                </div>
                <div class="col-md-12" id="batchPaymentDateHolder" >
                
                  <div class="form-group row {{$errors->first('payment_date')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Payment Date*</label>
                    <div class="col-sm-9">
                      <input type="text" id="payment_batch_date" class="form-control" placeholder="Payment Date" name="payment_date" value="{{ old('payment_date') }}">
                      <p class="error">{{$errors->first('payment_date')}}</p>
                    </div>
                  </div>
                </div>

                <div class="col-md-12">
                  <div class="form-group row">
                    <label class="col-sm-3">Creditor Selection *</label>
                  
                      <div class="col-sm-9">
                        <div class="custom-control custom-radio radio custom-control-inline">
                          
                          <input type="radio" class="custom-control-input" id="manualselection" name="employee_selection" value="manual" {{ (old('employee_selection')=="manual") ? 'checked': ''}} >
                          <label class="custom-control-label" for="manualselection">Single</label>
                        </div>
                      
                        <div class="custom-control custom-radio radio custom-control-inline">
                          
                          <input type="radio" class="custom-control-input" id="csvselection"  name="employee_selection" value="csvupload" {{ (old('employee_selection')=="csvupload") ? 'checked': ''}}>
                          <label class="custom-control-label" for="csvselection">CSV Upload</label>
                        </div>
                        <p class="error">{{$errors->first('employee_selection')}}</p>
                      </div>
                  </div>
                </div>
              </div>
                
              <button type="submit" class="btn btn-common mr-3">Next</button>
              <a href="{{ url('merchant/creditors') }}" class="btn btn-light">Cancel</a>
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
  var holidays = <?php echo json_encode($holidayDates); ?>;
  function handleServiceTypeClick(){
    $('#payment_batch_date').datepicker('destroy');
    setPaymentDatepickers(true);
  }
  $(document).ready(function(){
    setPaymentDatepickers(true);
  });
</script>
@endsection