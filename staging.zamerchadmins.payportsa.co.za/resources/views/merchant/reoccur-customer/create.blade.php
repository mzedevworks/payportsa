@extends('layouts.app')

@section('extra_style')
<link rel="stylesheet" href="{{ asset('css/jquery-ui.css') }}">
@endsection

@section('content')
  <div class="container-fluid">
    <div class="row">
      <div class="col-12 grid-margin">
        <div class="card">
          <div class="card-header border-bottom">
            <h4 class="card-title">Add Customer For reccur collection</h4>
          </div>

          <div class="card-body">
            <form class="form-sample" method="post" action="{{ isset($employee->id) && $employee->id!='' ? url('merchant/collection/reoccur/customer/'.encrypt($employee->id)) : url('merchant/collection/reoccur/customer/create') }}" autocomplete="off">
              @csrf
              <p class="card-description">Customer's Personal Information</p>
              <div class="row">

                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('first_name')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">First Name*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="First Name" name="first_name" value="{{old('first_name')}}">
                      <p class="error">{{$errors->first('first_name')}}</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('last_name')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Last Name*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="Last Name" name="last_name" value="{{old('last_name')}}">
                      <p class="error">{{$errors->first('last_name')}}</p>

                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('email')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Email</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="Email" name="email" value="{{old('email')}}">
                      <p class="error">{{$errors->first('email')}}</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('contact_number')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Cellphone*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="Contact No." name="contact_number" value="{{old('contact_number')}}">
                      <p class="error">{{$errors->first('contact_number')}}</p>
                    </div>
                  </div>
                </div>
              </div>
              <p class="card-description">Account Details</p>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group row {{ $errors->first('account_holder_name')?'has-error':'' }}">
                    <label class="col-sm-3 col-form-label">Account Holder Name*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" name="account_holder_name" value="{{ old('account_holder_name') }}" placeholder="Account Holder Name">
                      <p class="error">{{ $errors->first('account_holder_name')}}</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group row {{ $errors->first('account_number')?'has-error':'' }}">
                      <label class="col-sm-3 col-form-label">Account Number*</label>
                      <div class="col-sm-9">
                        <input type="text" class="form-control" placeholder="Account Number" name="account_number" value="{{ old('account_number') }}">
                        <p class="error">{{ $errors->first('account_number')}}</p>
                      </div>
                    </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group row {{ $errors->first('account_type')?'has-error':'' }}">
                    <label class="col-sm-3 col-form-label">Account Type*</label>
                    <div class="col-sm-9">
                      <select class="form-control" name="account_type">
                        <option value="">--Select Account Type--</option>
                        @foreach(Config('constants.collectionAccountType') as $key => $type)
                        <option value="{{ $type }}" {{$type==old('account_type')?'selected':''}}
                       >{{ $type }}</option>
                        @endforeach
                      </select>
                      <p class="error">{{ $errors->first('account_type')}}</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('bank_id')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Bank Name*</label>
                    <div class="col-sm-9">
                      <select class="form-control" name="bank_id" id="bank_id">
                        <option value="">Select</option>
                        @foreach(Helper::getBankDetails() as $eachBank)
                          <option value="{{$eachBank->id}}" {{$eachBank->id==old('bank_id')?'selected':''}}>{{$eachBank->bank_name}}</option>
                        @endforeach
                      </select>
                      <p class="error">{{$errors->first('bank_id')}}</p>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group row {{ $errors->first('branch_code')?'has-error':'' }}">
                    <label class="col-sm-3 col-form-label">Branch Code*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" name="branch_code" id="branch_code" value="{{ old('branch_code') }}">
                      <p class="error">{{ $errors->first('branch_code')}}</p>
                    </div>
                  </div>
                </div>
              </div>
              <p class="card-description">Address</p>
              <div class="row">

                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('address_one')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Address Line 1</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="Address Line 1" name="address_one" value="{{old('address_one')}}">
                      <p class="error">{{$errors->first('address_one')}}</p>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('address_line_two')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Address Line 2</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="Address Line 2" name="address_line_two" value="{{old('address_line_two')}}">
                      <p class="error">{{$errors->first('address_line_two')}}</p>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('subrub')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Suburb</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="Suburb" name="suburb" value="{{old('subrub')}}">
                      <p class="error">{{$errors->first('subrub')}}</p>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('city')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">City</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="City" name="city" value="{{old('city')}}">
                      <p class="error">{{$errors->first('city')}}</p>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('province')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Province</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="Province" name="province" value="{{old('province')}}">
                      <p class="error">{{$errors->first('province')}}</p>
                    </div>
                  </div>
                </div>

              </div>
              <p class="card-description">Service Details</p>
              <div class="row">

                <!-- <div class="col-md-6">
                  <div class="form-group row {{$errors->first('reference')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Reference*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="reference" name="reference" value="{{old('reference')}}">
                      <p class="error">{{$errors->first('reference')}}</p>
                    </div>
                  </div>
                </div> -->

                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('mandate_id')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Mandate Id*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="Mandate Id" name="mandate_id" value="{{old('mandate_id')}}">
                      <p class="error">{{$errors->first('mandate_id')}}</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('id_number')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Id No.*</label>
                    <div class="col-sm-9">
                      <input type="text" class="form-control" placeholder="Id Number" name="id_number" value="{{old('id_number')}}">
                      <p class="error">{{$errors->first('id_number')}}</p>
                    </div>
                  </div>
                </div>




              </div>
              <p class="card-description">Collection Details(For OnceOff)</p>
              <div class="row">
                <div class="col-md-6">
                      <div class="form-group row {{ $errors->first('collection_date')?'has-error':'' }}">
                        <label class="col-sm-3 col-form-label">Collection Date**</label>
                        <div class="col-sm-9">
                          <input type="text" class="form-control collection_date" placeholder="Collection Date" name="collection_date" value="{{ old('collection_date') }}" id="collection_date">
                          <p class="error">{{ $errors->first('collection_date')}}</p>
                        </div>
                      </div>
                </div>
                <div class="col-md-6">
                      <div class="form-group row {{ $errors->first('once_off_amount')?'has-error':'' }}">
                        <label class="col-sm-3 col-form-label">OnceOff Amount**</label>
                        <div class="col-sm-9">
                          <input type="text" class="form-control" placeholder="OnceOff Amount" name="once_off_amount" value="{{ old('once_off_amount') }}">
                          <p class="error">{{ $errors->first('once_off_amount')}}</p>
                        </div>
                      </div>
                </div>
              </div>
              <p class="card-description">Collection Details(For Recurring)</p>
              <div class="row">

                <div class="col-md-6">
                    <div class="form-group row {{ $errors->first('recurring_start_date')?'has-error':'' }}">
                      <label class="col-sm-3 col-form-label">Recurring Start Date**</label>
                      <div class="col-sm-9">
                        <input type="text" class="form-control recurringEntity" placeholder="Collection Start Date" name="recurring_start_date" value="{{old('recurring_start_date')}}" id="recurring_start_date">
                        <p class="error">{{ $errors->first('recurring_start_date')}}</p>
                      </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group row ">
                      <label class="col-sm-3 col-form-label">Action Day</label>
                      <div class="col-sm-9 input-group ">

                        <input type="text" readonly="readonly" class="form-control" placeholder="Action Day" name="recurring_action_day" value="{{old('recurring_action_day')}}" id="recurring_action_day"/>
                        <input type="hidden" name="next_action_day" value="{{old('next_action_day')}}" id="next_action_day">
                        <div class="input-group-prepend">
                            <div class="input-group-text next-action-date-txt">Next action Date</div>
                          </div>
                      </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group row {{ $errors->first('recurring_amount')?'has-error':'' }}">
                      <label class="col-sm-3 col-form-label">Recurring Amount</label>
                      <div class="col-sm-9">
                        <input type="text" class="form-control recurringEntity" placeholder="Recurring Amount" name="recurring_amount" value="{{ old('recurring_amount') }}">
                        <p class="error">{{ $errors->first('recurring_amount')}}</p>
                      </div>
                    </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group row {{$errors->first('debit_frequency')?'has-error':''}}">
                    <label class="col-sm-3 col-form-label">Debit Frequency**</label>
                    <div class="col-sm-9">
                      <select class="form-control recurringEntity" name="debit_frequency">
                        <option value="">Select</option>
                        @foreach(Config('constants.debitFrequency') as $key =>$debitFrequency)
                          <option value="{{$debitFrequency}}"
                          {{$debitFrequency==old('debit_frequency')?'selected':''}}>{{$debitFrequency}}</option>
                        @endforeach
                      </select>
                      <p class="error">{{$errors->first('debit_frequency')}}</p>
                    </div>
                  </div>
                </div>

                <div class="col-md-6" id="duration">
                  <div class="form-group row {{ $errors->first("duration")?"has-error":""}}">
                    <label class="col-sm-3 col-form-label">Duration</label><div class="col-sm-9">
                    <div>
                        <input type="text" class="form-control recurringEntity" placeholder="Duration" name="duration" value="{{ old('duration')}}">
                        <p class="error">{{ $errors->first("duration")}}</p></div>
                          <small id="durationHelp" class="form-text text-muted error">Duration is required if Recurring Amount > 0.</small>
                    </div>
                  </div>
                </div>
              </div>
              <?php /* <p class="card-description">
                If Collection date falls on Holiday/Weekend, Action date needs to be modified either Pre or post. Please mark your suitable option.
              </p>
              <div class="row">
                <div class="col-md-12">
                  <div class="form-group row">
                    <label class="col-sm-3">Action Date can be? *</label>

                      <div class="col-sm-9">
                        <div class="custom-control custom-radio radio custom-control-inline">

                          <input type="radio" class="custom-control-input" id="action_date_choice_pre" name="action_date_choice" value="pre" {{ (old('action_date_choice')==='pre') ? 'checked': ''}}>
                          <label class="custom-control-label" for="action_date_choice_pre">Pre Dated</label>
                        </div>

                        <div class="custom-control custom-radio radio custom-control-inline">

                          <input type="radio" class="custom-control-input" id="action_date_choice_post" name="action_date_choice" value="post" {{ (old('action_date_choice')=='post') ? 'checked': ''}}>
                          <label class="custom-control-label" for="action_date_choice_post">Post Dated</label>
                        </div>
                        <p class="error">{{$errors->first('action_date_choice')}}</p>
                      </div>
                  </div>
                </div>
              </div>
              */ ?>
              <button type="submit" class="btn btn-common mr-3">Submit</button>
              <a href="{{ url('merchant/collection/reoccur/customers') }}" class="btn btn-light">Cancel</a>
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
//var holidays = ["04-05-2020"];
var bank_id = '{{ old("bank_id")}}';

$(document).ready(function(){
    getCollectionDate('2 Day',true,'reoccur');

  if(bank_id!=''){
    getBranchCode(bank_id);
  }

  $('#bank_id').on('change',function(){
     getBranchCode($(this).val());
  });

  $("#recurring_start_date").on('change',function(){

    var newDate=new Date(this.value);
    newDate.setHours(0, 0, 0);   // Set hours, minutes and seconds
    var isWorkingDay=false;
    do {
      workingDayStatus=filterNonWorkingDays(newDate);
      isWorkingDay=workingDayStatus[0];
      if(isWorkingDay==false){
        newDate.setDate(newDate.getDate() + 1)
      }

    }
    while (isWorkingDay==false);

    var month = newDate.getUTCMonth() + 1; //months from 1-12
    var day = newDate.getDate();
    var year = newDate.getUTCFullYear();

    var nextActionDate = year + "-" + ('0'+month).slice(-2) + "-" + ('0'+day).slice(-2);

    $("#recurring_action_day").val(newDate.getDate());
    $("#next_action_day").val(nextActionDate);
    $(".next-action-date-txt").text("Next action Date "+nextActionDate);
  });

});


</script>
@endsection
