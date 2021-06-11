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
            <form class="form-sample" method="post" 
            action="{{ url('admin/holidays/create') }}" autocomplete="off">
              @csrf
              
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group row {{ $errors->first('holiday_event')?'has-error':'' }}">
                    <label class="col-sm-3 col-form-label">Holiday Event</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="Holiday Event" name="holiday_event" value="{{ old('holiday_event') }}">
                      <p class="error">{{ $errors->first('holiday_event')}}</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group row {{ $errors->first('holiday_date')?'has-error':'' }}">
                    <label class="col-sm-3 col-form-label">Holiday Date</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="Holiday Date" id="holiday_date" name="holiday_date" value="{{ old('holiday_date') }}">
                      <p class="error">{{ $errors->first('holiday_date')}}</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group row">
                    <label class="col-sm-3">Do it will Reoccur *</label>
                  
                      <div class="col-sm-9">
                        <div class="custom-control custom-radio radio custom-control-inline">

                          <input type="radio" class="custom-control-input" name="is_reocurr" value="1" id="is_reocurr_true" {{ (intval(old('is_reocurr'))===1) ? 'checked': ''}}>
                          <label class="custom-control-label" for="is_reocurr_true">Yes</label>
                        </div>
                      
                        <div class="custom-control custom-radio radio custom-control-inline">
                          
                          <input type="radio" class="custom-control-input" id="is_reocurr_false" name="is_reocurr" value="0" {{ (intval(old('is_reocurr'))===0 && sizeof($errors)>0 && !$errors->first('is_reocurr')) ? 'checked': ''}} >
                          <label class="custom-control-label" for="is_reocurr_false">No </label>
                        </div>
                        <p class="error">{{$errors->first('is_reocurr')}}</p>
                      </div>
                      
                  </div>
                </div>
              </div>
              <button type="submit" class="btn btn-common mr-3">Submit</button>
              <a href="{{ url('admin/setting/holidays') }}" class="btn btn-light">Cancel</a></button>
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
    $('#holiday_date').datepicker({
        minDate: '+2d',
        dateFormat: 'yy-mm-dd',
        changeYear : true,
        changeMonth : true
      });
  });
</script>
@endsection