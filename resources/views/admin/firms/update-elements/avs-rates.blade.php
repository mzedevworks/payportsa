<div class="row">
    @if(isset($firm->is_avs_batch) && $firm->is_avs_batch==1)
    <div class="col-md-6">
        <div class="form-group row {{ $errors->first('avs_batch')?'has-error':'' }}">
            <label class="col-sm-3 col-form-label">AVS Batch</label>
            <div class="col-sm-9">
                <input type="text" class="form-control" placeholder="AVS Batch" name="avs_batch"
                    value="{{old('avs_batch',$rates['avs_batch'])}}">
                <p class="error">{{ $errors->first('avs_batch')}}</p>
            </div>
        </div>
    </div>
    @endif
    @if(isset($firm->is_avs_rt) && $firm->is_avs_rt==1)
    <div class="col-md-6">
        <div class="form-group row {{ $errors->first('avs_rt')?'has-error':'' }}">
            <label class="col-sm-3 col-form-label">AVS Real Time</label>
            <div class="col-sm-9">
                <input type="text" class="form-control" placeholder="AVS RT" name="avs_rt"
                    value="{{ old('avs_rt',$rates['avs_rt']) }}">
                <p class="error">{{ $errors->first('avs_rt')}}</p>
            </div>
        </div>
    </div>
    @endif
</div>