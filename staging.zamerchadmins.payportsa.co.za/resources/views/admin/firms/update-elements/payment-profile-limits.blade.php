<div class="row">
                          <div class="col-md-6">
                            <div class="form-group row {{ $errors->first('line_payment')?'has-error':'' }}">
                              <label class="col-sm-3 col-form-label">Line Limit</label>
                              <div class="col-sm-9">
                                <input type="text" class="form-control" placeholder="Line Limit" name="line_payment" value="{{old('line_payment',$profileLimits['line_payment'])}}">
                                <p class="error">{{ $errors->first('line_payment')}}</p>
                              </div>
                            </div>
                          </div>
                        
                         <div class="col-md-6">
                            <div class="form-group row {{ $errors->first('batch_payment')?'has-error':'' }}">
                              <label class="col-sm-3 col-form-label">Batch Limit</label>
                              <div class="col-sm-9">
                                <input type="text" class="form-control" placeholder="Batch Limit" name="batch_payment" value="{{old('batch_payment',$profileLimits['batch_payment'])}}">
                                <p class="error">{{ $errors->first('batch_payment')}}</p>
                              </div>
                            </div>
                          </div>
                        
                        
                         <div class="col-md-6">
                            <div class="form-group row {{ $errors->first('monthly_payment')?'has-error':'' }}">
                              <label class="col-sm-3 col-form-label">Monthly Limit</label>
                              <div class="col-sm-9">
                                <input type="text" class="form-control" placeholder="Monthly Limit" name="monthly_payment" value="{{old('monthly_payment',$profileLimits['monthly_payment'])}}">
                                <p class="error">{{ $errors->first('monthly_payment')}}</p>
                              </div>
                            </div>
                          </div>
                        
         </div>               