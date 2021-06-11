<div class="row">
    <div class="col-md-6">
      <div class="form-group row {{ $errors->first('same_day_payment')?'has-error':'' }}">
        <label class="col-sm-3 col-form-label">Same Day</label>
        <div class="col-sm-9">
          <input type="text" class="form-control" placeholder="Same Day" name="same_day_payment" value="{{old('same_day_payment',$rates['same_day_payment'])}}">
          <p class="error">{{ $errors->first('same_day_payment')}}</p>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-group row {{ $errors->first('one_day_payment')?'has-error':'' }}">
        <label class="col-sm-3 col-form-label">1 day</label>
        <div class="col-sm-9">
          <input type="text" class="form-control" placeholder="One day payment" name="one_day_payment" value="{{ old('one_day_payment',$rates['one_day_payment']) }}">
          <p class="error">{{ $errors->first('one_day_payment')}}</p>
        </div>
      </div>
    </div>
   <div class="col-md-6">
      <div class="form-group row {{ $errors->first('two_day_payment')?'has-error':'' }}">
        <label class="col-sm-3 col-form-label">2 day</label>
        <div class="col-sm-9">
          <input type="text" class="form-control" placeholder="Two day payment" name="two_day_payment" value="{{old('two_day_payment',$rates['two_day_payment'])}}">
          <p class="error">{{ $errors->first('two_day_payment')}}</p>
        </div>
      </div>
    </div>
  
   <div class="col-md-6">
      <div class="form-group row {{ $errors->first('batch_fee_payment')?'has-error':'' }}">
        <label class="col-sm-3 col-form-label">Batch Fee</label>
        <div class="col-sm-9">
          <input type="text" class="form-control" placeholder="Batch Fee" name="batch_fee_payment" value="{{old('batch_fee_payment',$rates['batch_fee_payment'])}}">
          <p class="error">{{ $errors->first('batch_fee_payment')}}</p>
        </div>
      </div>
    </div>
  
   <div class="col-md-6">
      <div class="form-group row {{ $errors->first('failed_payment')?'has-error':'' }}">
        <label class="col-sm-3 col-form-label">Failed Transaction</label>
        <div class="col-sm-9">
          <input type="text" class="form-control" placeholder="Failed Transaction" name="failed_payment" value="{{old('failed_payment',$rates['failed_payment'])}}">
          <p class="error">{{ $errors->first('failed_payment')}}</p>
        </div>
      </div>
    </div>
 </div>               