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
                      <form class="form-sample" method="post" autocomplete="off">
                        @csrf

                        <p class="card-description">
                          Business Details
                        </p>
                        <div class="row">
                          <div class="col-md-6">
                            <div class="form-group row {{ $errors->first('mandate_ref')?'has-error':'' }}">
                              <label class="col-sm-3 col-form-label">Mandate Ref*</label>
                              <div class="col-sm-9">
                                <input type="text" class="form-control" placeholder="Mandate Ref" name="mandate_ref" value="{{old('mandate_ref',$firm['mandate_ref'])}}">
                                <p class="error">{{ $errors->first('mandate_ref')}}</p>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="form-group row {{ $errors->first('trading_as')?'has-error':'' }}">
                              <label class="col-sm-3 col-form-label">Abbreviated Name *</label>
                              <div class="col-sm-9">
                                <input type="text" class="form-control" placeholder="Abbreviated Name" name="trading_as" value="{{old('trading_as',$firm['trading_as'])}}">
                                <p class="error">{{ $errors->first('trading_as')}}</p>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="form-group row {{ $errors->first('business_name')?'has-error':'' }}">
                              <label class="col-sm-3 col-form-label">Business Name *</label>
                              <div class="col-sm-9">
                                <input type="text" class="form-control" placeholder="Business Name" name="business_name" value="{{old('business_name',$firm['business_name'])}}">
                                <p class="error">{{ $errors->first('business_name')}}</p>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="form-group row {{ $errors->first('vat_no')?'has-error':'' }}">
                              <label class="col-sm-3 col-form-label">VAT no. </label>
                              <div class="col-sm-9">
                                <input type="text" name="vat_no" class="form-control" placeholder="VAT No." value="{{old('vat_no',$firm['vat_no'])}}">
                                <p class="error">{{ $errors->first('vat_no')}}</p>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="form-group row {{ $errors->first('registration_no')?'has-error':'' }}">
                              <label class="col-sm-3 col-form-label">Registration No </label>
                              <div class="col-sm-9">
                                <input type="text" name="registration_no" class="form-control" placeholder="Registration No." value="{{old('registration_no',$firm['registration_no']) }}">
                                <p class="error">{{ $errors->first('registration_no')}}</p>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="form-group row {{$errors->first('entry_class')?'has-error':''}}">
                              <label class="col-sm-3 col-form-label">Entry Class*</label>
                              <div class="col-sm-9">
                                <select class="form-control" name="entry_class">
                                  <option value="">Select</option>
                                  @foreach(Config('constants.entry_class') as $key =>$entry_class)
                                    <option value="{{$key}}" {{ old('entry_class',$firm['entry_class'])==$key ? 'selected' : ''}}>{{$entry_class}}</option>
                                  @endforeach
                                </select>
                                <p class="error">{{$errors->first('entry_class')}}</p>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="form-group row {{ $errors->first('setup_fee')?'has-error':'' }}">
                              <label class="col-sm-3 col-form-label">Set-up Fee</label>
                              <div class="col-sm-9">
                                <input type="text" class="form-control" placeholder="setup fee" name="setup_fee" value="{{old('setup_fee',$firm['setup_fee']) }}">
                                <p class="error">{{ $errors->first('setup_fee')}}</p>
                              </div>
                            </div>
                          </div> 
                          <div class="col-md-6">
                            <div class="form-group row {{ $errors->first('setup_collection_date')?'has-error':'' }}">
                              <label class="col-sm-3 col-form-label">Setup Collection Date**</label>
                              <div class="col-sm-9">
                                <input type="text" class="form-control collection_date" placeholder="SetUp Collection Date" name="setup_collection_date" value="{{old('setup_collection_date',$firm['setup_collection_date']) }}" id="setup_collection_date">
                                <p class="error">{{ $errors->first('setup_collection_date')}}</p>
                              </div>
                            </div>
                          </div>

                          <div class="col-md-6">
                            <div class="form-group row {{ $errors->first('monthly_fee')?'has-error':'' }}">
                              <label class="col-sm-3 col-form-label">Monthly Fees</label>
                              <div class="col-sm-9">
                                <input type="text" class="form-control" placeholder="Monthly Fee" name="monthly_fee" value="{{old('monthly_fee',$firm['monthly_fee']) }}">
                                <p class="error">{{ $errors->first('monthly_fee')}}</p>
                              </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group row {{ $errors->first('monthly_collection_date')?'has-error':'' }}">
                              <label class="col-sm-3 col-form-label">Recurring Start Date**</label>
                              <div class="col-sm-9">
                                <input type="text" class="form-control" placeholder="Monthly Collection Date" name="monthly_collection_date" value="{{old('monthly_collection_date',$firm['monthly_collection_date']) }}" id="monthly_collection_date">
                                <p class="error">{{ $errors->first('monthly_collection_date')}}</p>
                              </div>
                            </div>
                        </div>

                          <div class="col-md-6">
                            <div class="form-group row {{ $errors->first('status')?'has-error':'' }}">
                              <label class="col-sm-3 col-form-label">Status *</label>
                              <div class="col-sm-9">
                                <select class="form-control" name="status">
                                  <option value="">Select</option>
                                  @foreach(Config('constants.status') as $key => $value)
                                    <option value="{{ $key }}" 
                                    {{ old('status',$firm['status'])==$key ? 'selected' : ''}}>{{ $value }}</option>
                                  @endforeach
                                  
                                </select>
                                <p class="error">{{$errors->first('status')}}</p>
                              </div>
                            </div>
                          </div>
                        </div>
                        <p class="card-description">
                          Address Details
                        </p>
                        <div class="row">
                          <div class="col-md-6">
                            <div class="form-group row {{ $errors->first('address1')?'has-error':'' }}">
                              <label class="col-sm-3 col-form-label">Address Line 1 *</label>
                              <div class="col-sm-9">
                                <textarea class="form-control" placeholder="Address" name="address1">{{old('address1',$firm['address1'])}}</textarea>
                                <p class="error">{{ $errors->first('address1')}}</p>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="form-group row {{ $errors->first('address2')?'has-error':'' }}">
                              <label class="col-sm-3 col-form-label">Address Line 2</label>
                              <div class="col-sm-9">
                                <textarea class="form-control" placeholder="Address" name="address2">{{old('address2',$firm['address2'])}}</textarea>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="form-group row {{ $errors->first('subrub')?'has-error':'' }}">
                              <label class="col-sm-3 col-form-label">Suburb </label>
                              <div class="col-sm-9">
                                <input type="text" name="subrub" class="form-control" placeholder="Suburb" value="{{old('subrub',$firm['subrub'])}}">
                                <p class="error">{{ $errors->first('subrub')}}</p>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="form-group row {{ $errors->first('city')?'has-error':'' }}">
                              <label class="col-sm-3 col-form-label">City *</label>
                              <div class="col-sm-9">
                                <input type="text" class="form-control" placeholder="Last Name" name="city" value="{{old('city',$firm['city'])}}">
                                <p class="error">{{ $errors->first('city')}}</p>
                              </div>
                            </div>
                          </div>
                          
                          <div class="col-md-6">
                            <div class="form-group row {{ $errors->first('province')?'has-error':'' }}">
                              <label class="col-sm-3 col-form-label">Province </label>
                              <div class="col-sm-9">
                                <input type="text" class="form-control" placeholder="Last Name" name="province" value="{{old('province',$firm['province'])}}">
                                <p class="error">{{ $errors->first('province')}}</p>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="form-group row {{ $errors->first('po_box_number')?'has-error':'' }}">
                              <label class="col-sm-3 col-form-label">PO BOX No </label>
                              <div class="col-sm-9">
                                <input type="text" class="form-control" placeholder="PO Box" name="po_box_number" value="{{old('po_box_number',$firm['po_box_number'])}}">
                                <p class="error">{{ $errors->first('po_box_number')}}</p>
                              </div>
                            </div>
                          </div>
                        </div>
                        
                        <p class="card-description">
                          Bank Account Info.
                        </p>
                        <div class="row">
                          <div class="col-md-6">
                            <div class="form-group row {{ $errors->first('account_holder_name')?'has-error':'' }}">
                              <label class="col-sm-3 col-form-label">Account Holder Name *</label>
                              <div class="col-sm-9">
                                <input type="text" class="form-control" name="account_holder_name" value="{{old('account_holder_name',$firm['account_holder_name']) }}">
                                <p class="error">{{ $errors->first('account_holder_name')}}</p>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="form-group row {{ $errors->first('account_number')?'has-error':'' }}">
                              <label class="col-sm-3 col-form-label">Account Number *</label>
                              <div class="col-sm-9">
                                <input type="text" class="form-control" name="account_number" value="{{old('account_number',$firm['account_number']) }}">
                                <p class="error">{{ $errors->first('account_number')}}</p>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="form-group row {{ $errors->first('account_type')?'has-error':'' }}">
                              <label class="col-sm-3 col-form-label">Account Type *</label>
                              <div class="col-sm-9">
                                <select class="form-control" name="account_type">
                                  @foreach(Config('constants.accountType') as $key => $type)
                                  <option value="{{ $type }}" 
                                  {{ $type===old('account_type',$firm['account_type'])?'selected':''}}
                                   >{{ $type }}</option>
                                  @endforeach
                                </select>
                                <p class="error">{{ $errors->first('account_type')}}</p>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="form-group row {{ $errors->first('bank_id')?'has-error':'' }}">
                              <label class="col-sm-3 col-form-label">Bank Name * </label>
                              <div class="col-sm-9">
                                <select class="form-control" name="bank_id" id="bank_id">
                                  <option value="">--Select Bank--</option>
                                  @foreach($bankDetails as $bankDetail)
                                  <option value="{{ $bankDetail->id }}" 
                                  {{ $bankDetail->id==old('bank_id',$firm['bank_id'])?'selected':''}}
                                  >{{ $bankDetail->bank_name }}</option>
                                  @endforeach
                                </select>
                                <p class="error">{{ $errors->first('bank_id')}}</p>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="form-group row {{ $errors->first('branch_code')?'has-error':'' }}">
                              <label class="col-sm-3 col-form-label">Branch Code *</label>
                              <div class="col-sm-9">
                                <input type="text" class="form-control" name="branch_code" value="{{old('branch_code',$firm['branch_code']) }}" id="branch_code">
                                <p class="error">{{ $errors->first('branch_code')}}</p>
                              </div>
                            </div>
                          </div>
                        </div>

                        <p class="card-description">
                          Product Categories
                        </p>
                        <div class="row">
                          <div class="col-md-12">
                            <div class="form-group row">
                              <label class="col-sm-3">Payments *</label>
                            
                                <div class="col-sm-9">
                                  <div class="custom-control custom-radio radio custom-control-inline">

                                    <input type="radio" class="custom-control-input" id="is_payment_true" name="is_payment" value="1" {{ (intval(old('is_payment',$firm['is_payment']))===1) ? 'checked': ''}} onclick="verifySubproductVisiblity()">
                                    <label class="custom-control-label" for="is_payment_true">Yes</label>
                                  </div>
                                
                                  <div class="custom-control custom-radio radio custom-control-inline">
                                    
                                    <input type="radio" class="custom-control-input" id="is_payment_false" name="is_payment" value="0" {{ (intval(old('is_payment',$firm['is_payment']))!=1 ) ? 'checked': ''}}  onclick="verifySubproductVisiblity()">
                                    <label class="custom-control-label" for="is_payment_false">No </label>
                                  </div>
                                  <p class="error">{{$errors->first('is_payment')}}</p>
                                </div>
                                
                            </div>
                          </div>
                          
                          <div class="col-md-12  {{ (old('is_payment',$firm['is_payment'])==1) ? '': 'd-none'}} " id="paymentProducts">
                            <div class="form-group row">
                              <label class="col-sm-3">Sub-product of Payments *</label>
                            
                                <div class="col-sm-9">
                                  <div class="custom-control custom-checkbox checkbox custom-control-inline">
                                    
                                    <input type="checkbox" class="custom-control-input" name="is_salaries" id="is_salaries" value="1" {{ (old('is_salaries',$firm['is_salaries'])==1) ? 'checked': ''}}>
                                    <label class="custom-control-label" for="is_salaries">Salaries</label>
                                  </div>
                                
                                  <div class="custom-control custom-checkbox checkbox custom-control-inline">
                                    
                                    <input type="checkbox" class="custom-control-input" name="is_creditors" id="is_creditors" value="1" {{ (old('is_creditors',$firm['is_creditors'])==1) ? 'checked': ''}}>
                                    <label class="custom-control-label" for="is_creditors">Creditors</label>
                                  </div>
                                  <p class="error">{{$errors->first('is_salaries')}}</p>
                                  
                                </div>
                                
                            </div>
                          </div>

                          <div class="col-md-12">
                            <div class="form-group row">
                              <label class="col-sm-3">Collections *</label>
                            
                                <div class="col-sm-9">
                                  <div class="custom-control custom-radio radio custom-control-inline">
                                    
                                    <input type="radio" class="custom-control-input" id="is_collection_true" name="is_collection" value="1" {{ (intval(old('is_collection',$firm['is_collection']))===1) ? 'checked': ''}} onclick="verifySubproductVisiblity()">
                                    <label class="custom-control-label" for="is_collection_true">Yes</label>
                                  </div>
                                
                                  <div class="custom-control custom-radio radio custom-control-inline">
                                    
                                    <input type="radio" class="custom-control-input" id="is_collection_false" name="is_collection" value="0" {{ (intval(old('is_collection',$firm['is_collection']))!=1) ? 'checked': ''}} onclick="verifySubproductVisiblity()">
                                    <label class="custom-control-label" for="is_collection_false">No</label>
                                  </div>
                                  <p class="error">{{$errors->first('is_collection')}}</p>
                                </div>
                            </div>
                          </div>

                          <div class="col-md-12  {{ (old('is_collection',$firm['is_collection'])==1) ? '': 'd-none'}}"  id="collectionProducts">
                            <div class="form-group row">
                              <label class="col-sm-3">Sub-product of Collection *</label>
                            
                                <div class="col-sm-9">
                                  <div class="custom-control custom-checkbox checkbox custom-control-inline">
                                    
                                    <input type="checkbox" class="custom-control-input" name="is_normal_collection" id="is_normal_collection" value="1" {{ (old('is_normal_collection',$firm['is_normal_collection'])==1) ? 'checked': ''}}>
                                    <label class="custom-control-label" for="is_normal_collection">Normal Collection</label>
                                  </div>
                                
                                  <div class="custom-control custom-checkbox checkbox custom-control-inline">
                                    
                                    <input type="checkbox" class="custom-control-input" name="is_reoccur_collection" id="is_reoccur_collection" value="1" {{ (old('is_reoccur_collection',$firm['is_reoccur_collection'])==1) ? 'checked': ''}}>
                                    <label class="custom-control-label" for="is_reoccur_collection">Reccuring Collection</label>
                                  </div>
                                  <p class="error">{{$errors->first('is_normal_collection')}}</p>
                                </div>
                            </div>
                          </div>

                          <div class="col-md-12">
                            <div class="form-group row">
                              <label class="col-sm-3">DebiCheck *</label>
                            
                                <div class="col-sm-9">
                                  <div class="custom-control custom-radio radio custom-control-inline">
                                    
                                    <input type="radio" class="custom-control-input" id="is_debicheck_true" name="is_debicheck" value="1" {{ (intval(old('is_debicheck',$firm['is_debicheck']))===1) ? 'checked': ''}}>
                                    <label class="custom-control-label" for="is_debicheck_true">Yes</label>
                                  </div>
                                
                                  <div class="custom-control custom-radio radio custom-control-inline">
                                    
                                    <input type="radio" class="custom-control-input" id="is_debicheck_false" name="is_debicheck" value="0" {{ (intval(old('is_debicheck',$firm['is_debicheck']))!=1) ? 'checked': ''}}>
                                    <label class="custom-control-label" for="is_debicheck_false">No</label>
                                  </div>
                                  <p class="error">{{$errors->first('is_debicheck')}}</p>
                                </div>
                            </div>
                          </div>

                          <div class="col-md-12">
                            <div class="form-group row">
                              <label class="col-sm-3">AVS *</label>
                            
                                <div class="col-sm-9">
                                  <div class="custom-control custom-radio radio custom-control-inline">
                                    
                                    <input type="radio" class="custom-control-input" id="is_avs_true" name="is_avs" value="1" {{ (intval(old('is_avs',$firm['is_avs']))===1) ? 'checked': ''}}>
                                    <label class="custom-control-label" for="is_avs_true">Yes</label>
                                  </div>
                                
                                  <div class="custom-control custom-radio radio custom-control-inline">
                                    
                                    <input type="radio" class="custom-control-input" id="is_avs_false" name="is_avs" value="0" {{ (intval(old('is_avs',$firm['is_avs']))!=1 ) ? 'checked': ''}}>
                                    <label class="custom-control-label" for="is_avs_false">No</label>
                                  </div>
                                  <p class="error">{{$errors->first('is_avs')}}</p>
                                </div>
                            </div>
                          </div>
                        </div>


                        <button type="submit" class="btn btn-common mr-3">Update</button>
                        <a href="{{ url('admin/firms') }}" class="btn btn-light">Cancel</a></button>
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
  var is_salaries = "{{ old('is_salaries',$firm['is_salaries'])==1 ? 'checked': ''}}";
  var is_creditors = "{{ old('is_creditors',$firm['is_creditors'])==1 ? 'checked': ''}}";
  

  var bank_id = '{{ old("bank_id")}}';
  $(document).ready(function(){
    if(bank_id!=''){
      getBranchCode(bank_id);
    }
    $('#bank_id').on('change',function(){
       getBranchCode($(this).val());
    });
    setFeeCollectionDatepickers(true);
  });

  
</script>
@endsection