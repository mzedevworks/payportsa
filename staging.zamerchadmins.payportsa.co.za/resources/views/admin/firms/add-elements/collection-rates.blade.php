<div class="row">
    <div class="col-md-6">
      <div class="form-group row {{ $errors->first('same_day_collection')?'has-error':'' }}">
        <label class="col-sm-3 col-form-label">Same Day</label>
        <div class="col-sm-9">
          <input type="text" class="form-control" placeholder="Same Day" name="same_day_collection" value="{{ old('same_day_collection') }}">
          <p class="error">{{ $errors->first('same_day_collection')}}</p>
        </div>
      </div>
    </div>
  <div class="col-md-6">
      <div class="form-group row {{ $errors->first('one_day_collection')?'has-error':'' }}">
        <label class="col-sm-3 col-form-label">1 Day</label>
        <div class="col-sm-9">
          <input type="text" class="form-control" placeholder="One Day collection" name="one_day_collection" value="{{ old('one_day_collection') }}">
          <p class="error">{{ $errors->first('one_day_collection')}}</p>
        </div>
      </div>
    </div>
   <div class="col-md-6">
      <div class="form-group row {{ $errors->first('two_day_collection')?'has-error':'' }}">
        <label class="col-sm-3 col-form-label">2 day</label>
        <div class="col-sm-9">
          <input type="text" class="form-control" placeholder="Two day collection" name="two_day_collection" value="{{ old('two_day_collection') }}">
          <p class="error">{{ $errors->first('two_day_collection')}}</p>
        </div>
      </div>
    </div>
  
   <!-- <div class="col-md-6">
      <div class="form-group row {{ $errors->first('batch_fee_collection')?'has-error':'' }}">
        <label class="col-sm-3 col-form-label">Batch Fee</label>
        <div class="col-sm-9">
          <input type="text" class="form-control" placeholder="Batch Fee" name="batch_fee_collection" value="{{ old('batch_fee_collection') }}">
          <p class="error">{{ $errors->first('batch_fee_collection')}}</p>
        </div>
      </div>
    </div> -->
  
    <div class="col-md-6">
      <div class="form-group row {{ $errors->first('failed_collection')?'has-error':'' }}">
        <label class="col-sm-3 col-form-label">Failed Transaction</label>
        <div class="col-sm-9">
          <input type="text" class="form-control" placeholder="Failed Transaction" name="failed_collection" value="{{ old('failed_collection') }}">
          <p class="error">{{ $errors->first('failed_collection')}}</p>
        </div>
      </div>
    </div>
                        
 </div>               