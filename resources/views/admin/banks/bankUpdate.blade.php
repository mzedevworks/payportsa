@extends('layouts.app')

@section('content')
  <div class="container-fluid">
    <div class="row">
      <div class="col-12 grid-margin">
        <div class="card">
          
          <div class="card-body">
            <form class="form-sample" method="post" autocomplete="off">
              @csrf
              <p class="card-description">
                Bank Account Info.
              </p>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group row {{ $errors->first('bank_name')?'has-error':'' }}">
                    <label class="col-sm-3 col-form-label">Bank Name *</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" name="bank_name" value="{{old('bank_name',$bankDetails['bank_name']) }}" placeholder="Bank Name">
                      <p class="error">{{ $errors->first('bank_name')}}</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group row {{ $errors->first('branch_code')?'has-error':'' }}">
                    <label class="col-sm-3 col-form-label">Branch Code *</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" name="branch_code" value="{{ old('branch_code',$bankDetails['branch_code']) }}" id="branch_code" placeholder="Branch Code">
                      <p class="error">{{ $errors->first('branch_code')}}</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group row {{ $errors->first('avs_bank_code')?'has-error':'' }}">
                    <label class="col-sm-3 col-form-label">AVS Bank Code **</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" name="avs_bank_code" value="{{ old('avs_bank_code',$bankDetails['avs_bank_code']) }}" id="avs_bank_code" placeholder="AVS Bank Code">
                      <p class="error">{{ $errors->first('avs_bank_code')}}</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-12">
                  <div class="form-group row">
                    <label class="col-sm-3">Is Active *</label>
                  
                      <div class="col-sm-9">
                        <div class="custom-control custom-radio radio custom-control-inline">
                          <input type="radio" class="custom-control-input" id="is_active_true" name="is_active" value="yes" {{ (old('is_active',$bankDetails['is_active'])=='yes') ? 'checked': ''}}>
                          <label class="custom-control-label" for="is_active_true">Yes</label>
                        </div>
                      
                        <div class="custom-control custom-radio radio custom-control-inline">
                          <input type="radio" class="custom-control-input" id="is_active_false" name="is_active" value="no" {{ (old('is_active',$bankDetails['is_active'])=='no') ? 'checked': ''}}>
                          <label class="custom-control-label" for="is_active_false">No</label>
                        </div>
                        <p class="error">{{$errors->first('is_active')}}</p>
                      </div>
                  </div>
                </div>
              </div>
            
              <p class="card-description">
                Available Account Types 
              </p>
              <div class="row">
                <div class="col-md-12">
                  <div class="form-group row">
                    <label class="col-sm-3">Savings *</label>
                  
                      <div class="col-sm-9">
                        <div class="custom-control custom-radio radio custom-control-inline">
                          <input type="radio" class="custom-control-input" id="is_savings_true" name="is_savings" value="yes" {{ (old('is_savings',$bankDetails['is_savings'])=='yes') ? 'checked': ''}}>
                          <label class="custom-control-label" for="is_savings_true">Yes</label>
                        </div>
                      
                        <div class="custom-control custom-radio radio custom-control-inline">
                          <input type="radio" class="custom-control-input" id="is_savings_false" name="is_savings" value="no" {{ (old('is_savings',$bankDetails['is_savings'])=='no') ? 'checked': ''}}>
                          <label class="custom-control-label" for="is_savings_false">No</label>
                        </div>
                        <p class="error">{{$errors->first('is_savings')}}</p>
                      </div>
                      
                  </div>
                </div>
                
               

                <div class="col-md-12">
                  <div class="form-group row">
                    <label class="col-sm-3">Cheque *</label>
                  
                      <div class="col-sm-9">
                        <div class="custom-control custom-radio radio custom-control-inline">
                          <input type="radio" class="custom-control-input" id="is_cheque_true" name="is_cheque" value="yes" {{ (old('is_cheque',$bankDetails['is_cheque'])=='yes') ? 'checked': ''}}>
                          <label class="custom-control-label" for="is_cheque_true">Yes</label>
                        </div>
                      
                        <div class="custom-control custom-radio radio custom-control-inline">
                          <input type="radio" class="custom-control-input" id="is_cheque_false" name="is_cheque" value="no" {{ (old('is_cheque',$bankDetails['is_cheque'])=='no') ? 'checked': ''}}>
                          <label class="custom-control-label" for="is_cheque_false">No</label>
                        </div>
                        <p class="error">{{$errors->first('is_cheque')}}</p>
                      </div>
                  </div>
                </div>
              </div>

              <p class="card-description">
                Available AVS Types 
              </p>
              <div class="row">
                <div class="col-md-12">
                  <div class="form-group row">
                    <label class="col-sm-3">Real Time *</label>
                  
                      <div class="col-sm-9">
                        <div class="custom-control custom-radio radio custom-control-inline">
                          <input type="radio" class="custom-control-input" id="is_realtime_avs_true" name="is_realtime_avs" value="yes" {{ (old('is_realtime_avs',$bankDetails['is_realtime_avs'])=='yes') ? 'checked': ''}}>
                          <label class="custom-control-label" for="is_realtime_avs_true">Yes</label>
                        </div>
                      
                        <div class="custom-control custom-radio radio custom-control-inline">
                          <input type="radio" class="custom-control-input" id="is_realtime_avs_false" name="is_realtime_avs" value="no" {{ (old('is_realtime_avs',$bankDetails['is_realtime_avs'])=='no') ? 'checked': ''}}>
                          <label class="custom-control-label" for="is_realtime_avs_false">No</label>
                        </div>
                        <p class="error">{{$errors->first('is_realtime_avs')}}</p>
                      </div>
                      
                  </div>
                </div>
                
               

                <div class="col-md-12">
                  <div class="form-group row">
                    <label class="col-sm-3">Batch AVS *</label>
                  
                      <div class="col-sm-9">
                        <div class="custom-control custom-radio radio custom-control-inline">
                          <input type="radio" class="custom-control-input" id="is_batch_avs_true" name="is_batch_avs" value="yes" {{ (old('is_batch_avs',$bankDetails['is_batch_avs'])=='yes') ? 'checked': ''}}>
                          <label class="custom-control-label" for="is_batch_avs_true">Yes</label>
                        </div>
                      
                        <div class="custom-control custom-radio radio custom-control-inline">
                          <input type="radio" class="custom-control-input" id="is_batch_avs_false" name="is_batch_avs" value="no" {{ (old('is_batch_avs',$bankDetails['is_batch_avs'])=='no') ? 'checked': ''}}>
                          <label class="custom-control-label" for="is_batch_avs_false">No</label>
                        </div>
                        <p class="error">{{$errors->first('is_batch_avs')}}</p>
                      </div>
                  </div>
                </div>
              </div>


              <button type="submit" class="btn btn-common mr-3">Submit</button>
              <a href="{{ url('admin/banks') }}" class="btn btn-light">Cancel</a></button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection