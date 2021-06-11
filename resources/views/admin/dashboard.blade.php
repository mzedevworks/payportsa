@extends('layouts.app')
@section('extra_style')
<link rel="stylesheet" href="{{ asset('css/jquery-ui.css') }}">

@endsection 
@section('content')
 <div class="container-fluid">
             
    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title">
              Business Overview
            </h5>
          </div>
          <div class="tab-info">
            <ul class="nav nav-tabs" role="tablist">
              @php
                $class='active';
              @endphp
              
                <li class="nav-item">
                  <a href="#collectionStatement" class="nav-link {{$class}}" role="tab" data-toggle="tab">Collections</a>
                </li>
                @php
                  $class='';
                @endphp
              
              
                <li class="nav-item">
                  <a href="#paymentStatement" class="nav-link {{$class}}" role="tab" data-toggle="tab">Payment</a>
                </li>
                @php
                  $class='';
                @endphp
              
            </ul>
            <div class="tab-content">
              @php
                $class='active';
              @endphp
              
                <div role="tabpanel" class="tab-pane  {{$class}}" id="collectionStatement">
                  <div class="card-header">
                    
                    <div class="float-left">
                      <div class="form-inline">
                        
                          <select id="collectionGraphDays" class="form-control mb-2 mr-sm-2 " onchange="triggerCollectionGraph();">
                              <option selected="selected" value="30">Last 30 Days</option>
                              <option value="60">Last 60 Days</option>
                              <option value="90">Last 90 Days</option>
                              <option value="custom">Custom</option>
                            </select>

                        <!-- <label class="sr-only" for="inlineFormInputGroupUsername2">From</label> -->
                        <input type="text" readonly="" class="form-control mb-2 mr-sm-2 collectionDatepicker customDayFeild" style="display: none" placeholder="From Date" name="collection_from" value="" id="collection_from">

                        <!-- <label class="sr-only" for="inlineFormInputGroupUsername2">To</label> -->
                        <input type="text" readonly="" class="form-control mb-2 mr-sm-2 collectionDatepicker customDayFeild" style="display: none" placeholder="To Date" name="collection_to" value="" id="collection_to">


                        
                      </div>
                      
                    </div>
                  </div>
                  <div class="p-20 text-center">
                    @if(sizeof($barchartData)<=0)
                      <span class="text-center">No Transactions in this month</span>
                    @endif
                    <div id="bar-example"></div>
                    
                  </div>
                </div>
                @php
                  $class='fade';
                @endphp
              
              
                <div role="tabpanel" class="tab-pane {{$class}}" id="paymentStatement">
                  <div class="card-header">
                    
                    <div class="float-left">
                      <div class="form-inline">
                        
                          <select id="paymentGraphDays" class="form-control mb-2 mr-sm-2 " onchange="triggerPaymentGraph();">
                              <option selected="selected" value="30">Last 30 Days</option>
                              <option value="60">Last 60 Days</option>
                              <option value="90">Last 90 Days</option>
                              <option value="custom">Custom</option>
                            </select>

                        <!-- <label class="sr-only" for="inlineFormInputGroupUsername2">From</label> -->
                        <input type="text" readonly="" class="form-control mb-2 mr-sm-2 paymentDatepicker customDayPayFeild" style="display: none" placeholder="From Date" name="payment_from" value="" id="payment_from">

                        <!-- <label class="sr-only" for="inlineFormInputGroupUsername2">To</label> -->
                        <input type="text" readonly="" class="form-control mb-2 mr-sm-2 paymentDatepicker customDayPayFeild" style="display: none" placeholder="To Date" name="payment_to" value="" id="payment_to">


                        
                      </div>
                      
                    </div>
                  </div>
                  <div class="p-20 text-center">
                    @if(sizeof($paymentBarchartData)<=0)
                      <span class="text-center">No Transactions in this month</span>
                    @endif
                    <div id="paymentBarhart"></div>
                    
                  </div>
                </div>
                @php
                  $class='';
                @endphp
              
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="row" id="collectionDonutContainer">

      <div class="col-lg-4 col-md-12 col-xs-12">
        <div class="card">
          <div class="card-body text-center">
            <h4 class="header-title">Total Transaction</h4>
            <ul class="list-inline widget-chart m-t-20 text-center" id="collectionTranxNoPie">
              @foreach($collectionTranx['count'] as $eachTranx)
                <li>
                  <h4><b>{{$eachTranx['value']}}</b></h4>
                  <p class="text-muted m-b-0">{{$eachTranx['label']}}</p>
                </li>
              @endforeach
              
            </ul>
            <div id="morris-donut-example" style="height: 240px"></div>
          </div>
        </div>
      </div>
      <div class="col-lg-4 col-md-12 col-xs-12">
        <div class="card">
          <div class="card-body text-center">
            <h4 class="header-title">Success Ratio</h4>
            <ul class="list-inline widget-chart m-t-20 text-center" id="collectionTranxPerPie">
              @foreach($collectionTranx['percent'] as $eachTranx)
                <li>
                  <h4><b>{{$eachTranx['value']}}%</b></h4>
                  <p class="text-muted m-b-0">{{$eachTranx['label']}}</p>
                </li>
              @endforeach
              
            </ul>
            <div id="sucess-percent-tranx" style="height: 240px"></div>
          </div>
        </div>
      </div>
      <div class="col-lg-4 col-md-12 col-xs-12">
        <div class="card">
          <div class="card-body text-center">
            <h4 class="header-title">Transaction Amount</h4>
            <ul class="list-inline widget-chart m-t-20 text-center" id="collectionTranxAmtPie">
              @foreach($collectionTranx['amount'] as $eachTranx)
                <li>
                  <h4><b>{{$eachTranx['value']}}</b></h4>
                  <p class="text-muted m-b-0">{{$eachTranx['label']}}</p>
                </li>
              @endforeach
              
            </ul>
            <div id="collection-trnx-amount" style="height: 240px"></div>
          </div>
        </div>
      </div>
    </div>

    <div class="row d-none" id="paymentDonutContainer">
      
      <div class="col-lg-4 col-md-12 col-xs-12">
        <div class="card">
          <div class="card-body text-center">
            <h4 class="header-title">Total Transaction</h4>
            <ul class="list-inline widget-chart m-t-20 text-center" id="paymentTranxNoPie">
              @foreach($paymentTranx['count'] as $eachTranx)
                <li>
                  <h4><b>{{$eachTranx['value']}}</b></h4>
                  <p class="text-muted m-b-0">{{$eachTranx['label']}}</p>
                </li>
              @endforeach
              
            </ul>
            <div id="totalPaymentGraph" style="height: 240px"></div>
          </div>
        </div>
      </div>
      <div class="col-lg-4 col-md-12 col-xs-12">
        <div class="card">
          <div class="card-body text-center">
            <h4 class="header-title">Success Ratio</h4>
            <ul class="list-inline widget-chart m-t-20 text-center" id="paymentTranxPerPie">
              @foreach($paymentTranx['percent'] as $eachTranx)
                <li>
                  <h4><b>{{$eachTranx['value']}}%</b></h4>
                  <p class="text-muted m-b-0">{{$eachTranx['label']}}</p>
                </li>
              @endforeach
              
            </ul>
            <div id="successPaymentGraph" style="height: 240px"></div>
          </div>
        </div>
      </div>
      <div class="col-lg-4 col-md-12 col-xs-12">
        <div class="card">
          <div class="card-body text-center">
            <h4 class="header-title">Transaction Amount</h4>
            <ul class="list-inline widget-chart m-t-20 text-center" id="paymentTranxAmountPie">
              @foreach($paymentTranx['amount'] as $eachTranx)
                <li>
                  <h4><b>{{$eachTranx['value']}}</b></h4>
                  <p class="text-muted m-b-0">{{$eachTranx['label']}}</p>
                </li>
              @endforeach
              
            </ul>
            <div id="totalPaymentAmountGraph" style="height: 240px"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('extra_script')
<script src="{{ asset('js/jquery-ui.js') }}"></script>
<!-- Required datatable js -->
<script src="{{ asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/dataTables.bootstrap4.min.js') }}"></script>
<!-- Buttons examples -->
<script src="{{ asset('plugins/datatables/dataTables.buttons.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/buttons.bootstrap4.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/jszip.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/pdfmake.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/vfs_fonts.js') }}"></script>
<script src="{{ asset('plugins/datatables/buttons.html5.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/buttons.print.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/buttons.colVis.min.js') }}"></script>
<!-- Responsive examples -->
<script src="{{ asset('plugins/datatables/dataTables.responsive.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/responsive.bootstrap4.min.js') }}"></script>


<!-- Datatable init js -->
<!-- <script src="{{ asset('js/datatables.init.js') }}"></script> -->
<script type="text/javascript">
var collectionBarGraph='';
var collectionCountGraph='';
var collectionAmountGraph='';
var collectionPercentGraph='';
var paymentBarGraph='';
var paymentCountGraph='';
var paymentAmountGraph='';
var paymentPercentGraph='';
function updateGraphs(dayLimit,fromDate,toDate){
  $.ajax({
        type  : 'post',
        url   : "{{url('admin/dashboard/collection-graph-data')}}",
        data  : { dayLimit: dayLimit ,fromDate: fromDate,toDate:toDate},
        success : function(data){
            data=JSON.parse(data);
            collectionBarGraph.setData(data.barchart);
            collectionCountGraph.setData(data.count);
            var countHtml='';
            data.count.forEach(function(item, index) {
                countHtml+='<li><h4><b>'+item.value+'</b></h4><p class="text-muted m-b-0">'+item.label+'</p></li>';
                
            });
            $("#collectionTranxNoPie").html(countHtml);

            collectionPercentGraph.setData(data.percent);
            var percentHtml='';
            data.percent.forEach(function(item, index) {
                percentHtml+='<li><h4><b>'+item.value+'%</b></h4><p class="text-muted m-b-0">'+item.label+'</p></li>';
                
            });
            $("#collectionTranxPerPie").html(percentHtml);

            collectionAmountGraph.setData(data.amount);
            var amountHtml='';
            data.amount.forEach(function(item, index) {
                amountHtml+='<li><h4><b>'+item.value+'</b></h4><p class="text-muted m-b-0">'+item.label+'</p></li>';
                
            });
            $("#collectionTranxAmtPie").html(amountHtml);
        },
        error:function(){
          $.notify('<strong>Something went wrong , Try Again later!</strong>', {
                'type': "danger",
                offset: {
                  x:20,
                  y:100,
                },
                allow_dismiss: true,
                newest_on_top: false,
            });  
        },complete:function(){
            
        }
    });
}

function triggerCollectionGraph(){
  var days=$("#collectionGraphDays").val();
  if(days!="custom"){
    $("#collection_from").val('');
    $("#collection_to").val('');
    $(".customDayFeild").hide();
  }else{
    $(".customDayFeild").show();
  }
  var fromDate=$("#collection_from").val();
  var uptoDate=$("#collection_to").val();
  if(days=="custom" && (fromDate=='' || uptoDate=='')){
    return false;
  }
  updateGraphs(days,fromDate,uptoDate);
}

function triggerPaymentGraph(){
  var days=$("#paymentGraphDays").val();
  if(days!="custom"){
    $("#payment_from").val('');
    $("#payment_to").val('');
    $(".customDayPayFeild").hide();
  }else{
    $(".customDayPayFeild").show();
  }
  var fromDate=$("#payment_from").val();
  var uptoDate=$("#payment_to").val();
  if(days=="custom" && (fromDate=='' || uptoDate=='')){
    return false;
  }
  updatePaymentGraphs(days,fromDate,uptoDate);
}

function updatePaymentGraphs(dayLimit,fromDate,toDate){
  $.ajax({
        type  : 'post',
        url   : "{{url('admin/dashboard/payment-graph-data')}}",
        data  : { dayLimit: dayLimit ,fromDate: fromDate,toDate:toDate},
        success : function(data){
            data=JSON.parse(data);
            paymentBarGraph.setData(data.barchart);
            paymentCountGraph.setData(data.count);
            paymentAmountGraph.setData(data.amount);

            var countHtml='';
            data.count.forEach(function(item, index) {
                countHtml+='<li><h4><b>'+item.value+'</b></h4><p class="text-muted m-b-0">'+item.label+'</p></li>';
                
            });
            $("#paymentTranxNoPie").html(countHtml);

            var amountHtml='';
            data.amount.forEach(function(item, index) {
                amountHtml+='<li><h4><b>'+item.value+'</b></h4><p class="text-muted m-b-0">'+item.label+'</p></li>';
                
            });
            $("#paymentTranxAmountPie").html(amountHtml);

            paymentPercentGraph.setData(data.percent);
            var percentHtml='';
            data.percent.forEach(function(item, index) {
                percentHtml+='<li><h4><b>'+item.value+'%</b></h4><p class="text-muted m-b-0">'+item.label+'</p></li>';
                
            });
            $("#paymentTranxPerPie").html(percentHtml);

        },
        error:function(){
          $.notify('<strong>Something went wrong , Try Again later!</strong>', {
                'type': "danger",
                offset: {
                  x:20,
                  y:100,
                },
                allow_dismiss: true,
                newest_on_top: false,
            });  
        },complete:function(){
            
        }
    });
}

$(document).ready(function() {

if($('#bar-example').length>0){
  collectionBarGraph=Morris.Bar({
    element: 'bar-example',
    barGap:4,
      barSizeRatio:0.55,
    data: <?php echo json_encode($barchartData); ?>,
    xkey: 'day',
    ykeys: ['tranx'],
    labels: ['Transactions'],
    resize: true,
    xLabelMargin: 5,
    xLabelAngle: '70',
  });
}
  
if($('#morris-donut-example').length>0){  
  collectionCountGraph=Morris.Donut({
    element: 'morris-donut-example',
    data: <?php echo json_encode($collectionTranx['count']); ?>,
    resize: true,
    colors: ['#ffbb44', '#e22a6f', '#24d5d8'],
  });
}

if($('#collection-trnx-amount').length>0){  
  collectionAmountGraph=Morris.Donut({
    element: 'collection-trnx-amount',
    data: <?php echo json_encode($collectionTranx['amount']); ?>,
    resize: true,
    colors: ['#ffbb44', '#e22a6f', '#24d5d8'],
  });
}

if($('#sucess-percent-tranx').length>0){
  collectionPercentGraph=Morris.Donut({
    element: 'sucess-percent-tranx',
    data: <?php echo json_encode($collectionTranx['percent']); ?>,
    resize: true,
    formatter: function (y) { return y + "%" },
    colors: ['#ffbb44', '#e22a6f', '#24d5d8']
  });
}


if($('#paymentBarhart').length>0){  
  paymentBarGraph=Morris.Bar({
      element: 'paymentBarhart',
      barGap:4,
      barSizeRatio:0.55,
      data: <?php echo json_encode($paymentBarchartData); ?>,
      xkey: 'day',
      ykeys: ['tranx'],
      labels: ['Transactions'],
      resize: true,
      xLabelMargin: 5,
      xLabelAngle: '70',
    });
}

if($('#totalPaymentGraph').length>0){  
  paymentCountGraph=Morris.Donut({
    element: 'totalPaymentGraph',
    data: <?php echo json_encode($paymentTranx['count']); ?>,
    resize: true,
    colors: ['#ffbb44', '#e22a6f', '#24d5d8'],
  });
}

if($('#totalPaymentAmountGraph').length>0){  
  paymentAmountGraph=Morris.Donut({
    element: 'totalPaymentAmountGraph',
    data: <?php echo json_encode($paymentTranx['amount']); ?>,
    resize: true,
    colors: ['#ffbb44', '#e22a6f', '#24d5d8'],
  });
}

if($('#successPaymentGraph').length>0){  
  paymentPercentGraph=Morris.Donut({
    element: 'successPaymentGraph',
    data: <?php echo json_encode($paymentTranx['percent']); ?>,
    resize: true,
    formatter: function (y) { return y + "%" },
    colors: ['#ffbb44', '#e22a6f', '#24d5d8']
  });
}
  $('.collectionDatepicker').datepicker({
      
      dateFormat: 'yy-mm-dd',
      changeYear : true,
      changeMonth : true,
      onSelect:function(dateText,obj){
        triggerCollectionGraph();
      }
    });

  $('.paymentDatepicker').datepicker({
      
      dateFormat: 'yy-mm-dd',
      changeYear : true,
      changeMonth : true,
      onSelect:function(dateText,obj){
        triggerPaymentGraph();
      }
    });
  
  $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
    var target = $(e.target).attr("href") // activated tab

    switch (target) {
      case "#collectionStatement":
        collectionBarGraph.redraw();
        collectionCountGraph.redraw();
        collectionAmountGraph.redraw();
        collectionPercentGraph.redraw();
        $("#paymentDonutContainer").addClass('d-none');
        $("#collectionDonutContainer").removeClass('d-none');
        $(window).trigger('resize');
        break;
      case "#paymentStatement":
        paymentBarGraph.redraw();
        paymentCountGraph.redraw();
        paymentAmountGraph.redraw();
        paymentPercentGraph.redraw();
        $("#paymentDonutContainer").removeClass('d-none');
        $("#collectionDonutContainer").addClass('d-none');
        $(window).trigger('resize');
        break;
    }
  });

    
  // setInterval(function() { percentGraph.setData(<?php echo json_encode($collectionTranx['count']); ?>); }, 20000);

  
});
</script>
@endsection