<div class="row">
      <div class="col-md-6">
        <div class="form-group row {{ $errors->first('line_collection')?'has-error':'' }}">
          <label class="col-sm-3 col-form-label">Line Limit</label>
          <div class="col-sm-9">
            <input type="text" class="form-control" placeholder="Line Limit" name="line_collection" value="{{old('line_collection',$profileLimits['line_collection'])}}">
            <p class="error">{{ $errors->first('line_collection')}}</p>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group row {{ $errors->first('surety_amount')?'has-error':'' }}">
          <label class="col-sm-3 col-form-label">Surety Percentage</label>
          <div class="col-sm-9">
            <input type="text" class="form-control" placeholder="Surety Percentage" name="surety_amount" value="{{old('surety_amount',$profileLimits['surety_amount'])}}">
            <p class="error">{{ $errors->first('surety_amount')}}</p>
          </div>
        </div>
      </div>

     <!-- <div class="col-md-6">
        <div class="form-group row {{ $errors->first('batch_collection')?'has-error':'' }}">
          <label class="col-sm-3 col-form-label">Batch Limit</label>
          <div class="col-sm-9">
            <input type="text" class="form-control" placeholder="Batch Limit" name="batch_collection" value="{{old('batch_collection',$profileLimits['batch_collection'])}}">
            <p class="error">{{ $errors->first('batch_collection')}}</p>
          </div>
        </div>
      </div>
    
     <div class="col-md-6">
        <div class="form-group row {{ $errors->first('daily_collection')?'has-error':'' }}">
          <label class="col-sm-3 col-form-label">Daily Limit</label>
          <div class="col-sm-9">
            <input type="text" class="form-control" placeholder="Daily Limit" name="daily_collection" value="{{old('daily_collection',$profileLimits['daily_collection'])}}">
            <p class="error">{{ $errors->first('daily_collection')}}</p>
          </div>
        </div>
      </div>
    
     <div class="col-md-6">
        <div class="form-group row {{ $errors->first('monthly_collection')?'has-error':'' }}">
          <label class="col-sm-3 col-form-label">Monthly Limit</label>
          <div class="col-sm-9">
            <input type="text" class="form-control" placeholder="Monthly Limit" name="monthly_collection" value="{{old('monthly_collection',$profileLimits['monthly_collection'])}}">
            <p class="error">{{ $errors->first('monthly_collection')}}</p>
          </div>
        </div>
      </div> -->
                        
</div>               