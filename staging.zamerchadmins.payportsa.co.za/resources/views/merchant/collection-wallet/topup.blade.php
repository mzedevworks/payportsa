@extends('layouts.app')

@section('content')
 <div class="container-fluid">
  <div class="card-group">
    <div class="card  col-3">
      <div class="card-body">
        <div class="row">
          <div class="col-12">
            <div class="d-flex no-block align-items-center">
              <div>
                 <div class="icon"><i class="lni-empty-file"></i></div>
                 <p class="text-muted">Available Balance</p>
              </div>
              <div class="ml-auto">
                 <h2 class="counter text-info">
                   {{$payPortInfo->closing_balance}}
                 </h2>
              </div>
            </div>
          </div>
          <div class="col-12">
            <div class="progress">
               <div class="progress-bar bg-info" role="progressbar" style="width: 85%; height: 6px;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    
  </div>
  <div class="row">
      <div class="col-12 grid-margin">
        <div class="card">
          <div class="card-body">
            <form class="form-sample" method="post" action="" autocomplete="off">
              @csrf
              <div class="row">
                                
                <div class="col-md-12">
                  <div class="form-group row {{$errors->first('firm_id')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Business Name</label>
                    <div class="col-sm-9">
                      <select class="form-control" name="firm_id">
                        <option value="">Merchant Name</option>
                          @if(count($firms)>0)
                            @foreach($firms as $eachMerchant)
                              <option value="{{ $eachMerchant->id }}">
                                {{ $eachMerchant->business_name }}
                              </option>
                            @endforeach
                          @endif
                      </select>
                      <p class="error">{{$errors->first('firm_id')}}</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-12">
                  <div class="form-group row {{$errors->first('transmission_type')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Transaction Type</label>
                    <div class="col-sm-9">
                      <select class="form-control" name="transmission_type">
                        <option value="">Select</option>
                          <option value="cr">Credit</option>
                          <option value="dr">Debit</option>
                      </select>
                      <p class="error">{{$errors->first('transmission_type')}}</p>
                    </div>
                  </div>
                </div>
                
                <div class="col-md-12">
                  <div class="form-group row {{$errors->first('amount')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Amount</label>
                    <div class="col-sm-9">
                      <input class="form-control" name="amount" type="text"/>
                      <p class="error">{{$errors->first('amount')}}</p>
                    </div>
                  </div>
                </div>
                
                <div class="col-md-12">
                  <div class="form-group row {{$errors->first('remark')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Remark</label>
                    <div class="col-sm-9">
                      <input class="form-control" name="remark" type="text"/>
                      <p class="error">{{$errors->first('remark')}}</p>
                    </div>
                  </div>
                </div>

              </div>
              
              
              <button type="submit" class="btn btn-common mr-3">Save</button>
              <a href="{{ url('admin/collection-wallet/profilestats') }}" class="btn btn-light">Cancel</a>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
