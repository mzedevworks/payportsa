<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" type="text/css" href="{{ asset('css/bootstrap.min.css') }}">
    <!-- Fonts -->
    <link rel="stylesheet" type="text/css" href="{{ asset('fonts/line-icons.css') }}">
    <!--Morris Chart CSS -->
    <link rel="stylesheet" href="{{ asset('plugins/morris/morris.css') }}">
    <!-- Main Style -->
    <link rel="stylesheet" type="text/css" href="{{ asset('css/main.css') }}">
    <!-- Responsive Style -->
    <link rel="stylesheet" type="text/css" href="{{ asset('css/responsive.css') }}">
    @yield('extra_style')
  </head>

  <body>
    <div class="app header-default side-nav-dark">
      <div class="layout">
        <!-- Header START -->
        @include('elements.header')
        
        <!-- Header END -->

        <!-- Side Nav START -->
          @include('elements.sidebar')
        <!-- Side Nav END -->

        <!-- Page Container START -->
        <div class="page-container">
          <!-- Content Wrapper START -->
          <div class="main-content">
            <div class="container-fluid">
              <!-- Breadcrumb Start -->
              <div class="breadcrumb-wrapper row">
                <div class="col-12 col-lg-3 col-md-6">
                  <h4 class="page-title">{{ isset($pagename) ? $pagename : ''}}</h4>
                </div>
                <div class="col-12 col-lg-9 col-md-6">
                  <ol class="breadcrumb float-right">
                    <li><a href="{{ URL::previous() }}">Back</a></li>
                    <li class="active">/{{ isset($pagename) ? $pagename :'' }}</li>
                  </ol>
                </div>
              </div>
              <!-- Breadcrumb End -->
            </div>
              @yield('content')
          </div>
          <!-- Content Wrapper END -->

          <!-- Footer START -->
          @include('elements.footer')
          <!-- Footer END -->

        </div>
        <!-- Page Container END -->
      </div>
    </div>

    <!-- Preloader -->
    <div id="preloader">
      <div class="loader" id="loader-1"></div>
    </div>
    <!-- End Preloader -->

    <!-- jQuery first, then Popper.js') }}, then Bootstrap JS -->
    <script src="{{ asset('js/jquery-min.js') }}"></script>
    <script src="{{ asset('js/popper.min.js') }}"></script>
    <script src="{{ asset('js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('js/jquery.app.js') }}"></script>
    <script src="{{ asset('plugins/morris/morris.min.js') }}"></script>
    <script src="{{ asset('plugins/raphael/raphael-min.js') }}"></script>
    <script type="text/javascript">
      var sastTimeZone=<?php echo Config('constants.sastTimeOffset'); ?>;
      var bankingCutoffTime=parseInt(<?php echo Config('constants.bankingCutOffTime'); ?>);
      var getbankUrl='{{ url("getBank") }}';
      var reocurTwoDayCalOffset=parseInt(<?php echo Config('constants.reocurTwoDayCalOffset'); ?>);
      var normalTwoDayCalOffset=parseInt(<?php echo Config('constants.normalTwoDayCalOffset'); ?>);
      var normalOneDayCalOffset=parseInt(<?php echo Config('constants.normalOneDayCalOffset'); ?>);
      var normalSameDayCalOffset=parseInt(<?php echo Config('constants.normalSameDayCalOffset'); ?>);
      
    

      var sameDayPaymentCutoff=parseInt(<?php echo Config('constants.sameDayPaymentCutOffTime'); ?>);
      var sameDayPaymentOffsetDay=parseInt(<?php echo Config('constants.sameDayPaymentOffset'); ?>);

      var oneDayPaymentCutoff=parseInt(<?php echo Config('constants.oneDayPaymentCutOffTime'); ?>);
      var oneDayPaymentOffsetDay=parseInt(<?php echo Config('constants.oneDayPaymentOffset'); ?>);
    </script>
    <script src="{{ asset('js/main.js') }}"></script>
    <script src="{{ asset('js/bootstrap-notify.js') }}"></script>
    <script type="text/javascript">
    
      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
      });
    </script>
    @yield('extra_script')
    <style type="text/css">
      .contactDetail.alert.alert-info p{
        line-height: 0.8 !important;
        /*color: red;*/
      }
      .contactDetail.alert.alert-info p a{
        padding: 1px 0px;
      }
      
      .contactDetail.alert.alert-info p a:focus,li .contactDetail.alert.alert-info p a:hover{
        color: #8a8a8a !important;
        background: none !important;
      }
    </style>
  </body>
</html>

