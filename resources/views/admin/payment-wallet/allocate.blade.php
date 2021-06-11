@extends('layouts.app')

@section('content')
            <div class="container-fluid">
              <div class="row">
                <div class="col-12 grid-margin">
                  <div class="card">
                    <div class="card-header border-bottom">
                      <h4 class="card-title">Details of Un-allocated Fund Transmission</h4>
                    </div>
                    
                    <div class="p-20">
                            
                            <div class="row">
                              <div class="col-md-6">
                                <div class="form-group row">
                                  <label class="col-sm-3 col-form-label">Payment Ref</label>
                                  <div class="col-sm-9 col-form-label">
                                    <p>{{ $fundToAllocate->reffrence_number}}</p>
                                  </div>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="form-group row ">
                                  <label class="col-sm-3 col-form-label">Amount </label>
                                  <div class="col-sm-9 col-form-label">
                                    <p>{{ $fundToAllocate->amount}}</p>
                                  </div>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="form-group row">
                                  <label class="col-sm-3 col-form-label">Date </label>
                                  <div class="col-sm-9 col-form-label">
                                    <p>{{Helper::convertDate($fundToAllocate->created_at,'d-m-Y')}}</p>
                                  </div>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="form-group row">
                                  <label class="col-sm-3 col-form-label">Status </label>
                                  <div class="col-sm-9 col-form-label">
                                    <p>{{ Helper::getUnTrackedStatusTitle($fundToAllocate->allocation_status)}}</p>
                                  </div>
                                </div>
                              </div>
                              
                            </div>
                            <p class="card-description">
                              Allocate This Fund To:-
                            </p>
                            <div class="row">
                              <form class="form-inline ml-2" method="post" id="allocateFundForm" action="{{url('admin/payment/payment-wallet/').'/'.Helper::encryptVar($fundId)}}">
                                @csrf
                                <label class="sr-only" for="payment_from">Merchant</label>
                             
                                <select class="form-control mr-sm-2" name="merchantId" id="allocatedMerchantId">
                                  <option value="">Select</option>
                                  @foreach($eligibleFirms as $key =>$eachEligibleFund)
                                    <option value="{{$eachEligibleFund->id}}" {{ old('merchant_id')==$eachEligibleFund->id ? 'selected' : ''}}>{{$eachEligibleFund->business_name}}</option>
                                  @endforeach
                                </select>
                                    
                                  
                              <button id="allocateFundBtn" type="button" onclick="confirmFormSubmit('Are you sure?','allocateFundForm')" class="btn btn-common mr-3"> Apply</button>
                                    
                              </form>
                            </div>
                          </div>



                  </div>
                </div>
              </div>
            </div>
@endsection

@section('extra_script')
<script type="text/javascript">
  function confirmFormSubmit(message,formId){
    alert($("#allocatedMerchantId").val());
    if($("#allocatedMerchantId").length>0 && $("#allocatedMerchantId").val()!=""){
        confirmDialog(message, (ans) => {
      if (ans) {
        $("#"+formId).submit();
        }
     });
    }else{
        alertDialog("Please Select Any Merchant/Business!", (ans) => {});
    }

    
}
</script>
@endsection