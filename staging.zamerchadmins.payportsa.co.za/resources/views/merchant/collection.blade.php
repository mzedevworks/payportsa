@extends('layouts.app')

@section('extra_style')
<style type="text/css">
.info-box .info-box-content .info-text {
    font-size: 20px !important;
}
.btn-common{
  margin-top: 36px;
}
.text-dark {
    color: #fff !important;
}
.info-box {
    min-height: 294px;
}
.info-box .info-box-content .number {
    font-size: 25px;
    color: 
    #fff;
    font-weight: 700;
    margin-bottom: 0;
}
.info-box-content hr {
   margin-top: 5px;
   margin-bottom: 12px;
}

</style>
<link rel="stylesheet" href="{{ asset('css/jquery-ui.css') }}">
@endsection

@section('content')
<div class="container-fluid">
              <div class="row">
                <div class="col-lg-3 col-md-6 col-xs-12">
                  <div class="info-box bg-primary">
                    <div class="icon-box">
                     <span class="icon-holder">
                       <i class="lni-layers"></i>
                     </span>
                    </div>
                    <div class="info-box-content">
                      <h4 class="number">Collection Profile limit</h4>
                      <hr></hr>
                      <p class="text-dark text-semibold">Monthly Limit:<span>{{ isset($profileLimits->monthly_collection) ? $profileLimits->monthly_collection : '' }}</span></p>
                      <p class="text-dark text-semibold">Line Limit: {{ isset($profileLimits->line_collection) ?  $profileLimits->line_collection : ''}}</p>
                    </div>
                  </div>
                </div>
                <div class="col-lg-3 col-md-6 col-xs-12">
                  <div class="info-box bg-success">
                    <div class="icon-box">
                      <i class="lni-package"></i>
                    </div>
                    <div class="info-box-content">
                      <h4 class="number">Balances</h4>
                      <hr></hr>
                      <p class="text-dark text-semibold">Balance            : R40 000,00</p>
                      <p class="text-dark text-semibold">Withdrawal balance : R10 000,00</p>
                    </div>
                  </div>
                </div>
                <div class="col-lg-3 col-md-6 col-xs-12">
                  <div class="info-box bg-info">
                    <div class="icon-box">
                      <i class="lni-cart"></i>
                    </div>
                    <div class="info-box-content">
                      <h4 class="number">Pending Collections</h4>
                      <hr></hr>
                      <p class="text-dark text-semibold">R10 000,00</p>
                    </div>
                  </div>
                </div>
                 <div class="col-lg-3 col-md-6 col-xs-12">
                  <div class="info-box bg-purple">
                    <div class="icon-box">
                      <i class="lni-cross-circle"></i>
                    </div>
                    <div class="info-box-content">
                      <h4 class="number">Failed Transactions</h4>
                      <table class="table table-lg">
                        <thead>
                          <tr>
                            <td class="text-dark text-semibold">Date</td>
                            <td class="text-dark text-semibold">Customer</td>
                            <td class="text-dark text-semibold">Amount</td>   
                          </tr>
                        </thead>
                        <tr>
                            <td class="text-dark text-semibold">01/8/2019</td>
                            <td class="text-dark text-semibold">Verona Reinders </td>
                            <td class="text-dark text-semibold">R100.00</td>   
                          </tr>
                          <tr>
                            <td class="text-dark text-semibold">01/8/2019</td>
                            <td class="text-dark text-semibold">Verona Reinders </td>
                            <td class="text-dark text-semibold">R100.00</td>   
                          </tr>
                        
                      </table>
                    </div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-12 col-md-12 col-xs-12">
                 <h4 class="card-title">Collections  Summary </h4>
                  <div class="card">
                    <div class="card-body">
                      <form class="form-sample" method="post" autocomplete="off">
                        @csrf
                        <div class="row">
                              <div class="col-sm-4">
                                <label>From Date</label>
                                <input type="text" class="form-control" placeholder="From Date" name="from_date" id="from_date" value="" required="">
                                <p class="from_date_error error"></p>
                              </div>
                              <div class="col-sm-4">
                                <label>To Date</label>
                                <input type="text" class="form-control" placeholder="To Date" name="to_date" value="" id="to_date" required="">
                                <p class="to_date_error error"></p>
                              </div>
                              <div class="col-sm-4">
                                 <label  class="mt-4"></label>
                                 <button type="button" class="btn btn-common collection-summary">Search
                                 </button>
                              </div>
                        </div>
                        <div class="row">
                          <div class="table-overflow" id="collection-summary">
                            
                          </div>
                        </div>
                      </form>
                    </div>
                </div>
              </div>
</div>
@endsection

@section('extra_script')
<script src="{{ asset('js/jquery-1.12.4.js') }}"></script>
<script src="{{ asset('js/jquery-ui.js') }}"></script>
<script type="text/javascript">
  $('#from_date').datepicker({
        dateFormat: 'yy-mm-dd',
        changeYear : true,
        changeMonth : true
  });
  $('#to_date').datepicker({
        dateFormat: 'yy-mm-dd',
        changeYear : true,
        changeMonth : true
  });
  $(document).ready(function(){
    $('.collection-summary').on('click',function(){
          var from_date = $('#from_date').val();
          var to_date  = $('#to_date').val();
          var valid = true;
          if(from_date==''){
            $('.from_date_error').html('Please select From date');
            return false;
          }
          if(to_date==''){
            $('.from_date_error').html('');
            $('.to_date_error').html('Please select To date');
            return false;
          }
          $('#collection-summary').html('');
          $('.from_date_error').html('');
          $('.to_date_error').html('');
          $.ajax({
            type: "GET",
            url: 'collection/summary',
            data: { from_date : from_date, to_date: to_date }, 
            success: function( data ) {
                $('#collection-summary').html(data);
            }
          });
      
    });
  });
</script>
@endsection