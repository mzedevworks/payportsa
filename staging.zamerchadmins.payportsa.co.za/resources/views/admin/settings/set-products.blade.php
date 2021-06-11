@extends('layouts.app')

@section('extra_style')
    <!-- DataTables -->
    <link href="{{ asset('plugins/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('plugins/datatables/buttons.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Responsive datatable examples -->
    <link href="{{ asset('plugins/datatables/responsive.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" /> 
@endsection 

@section('content')
  <div class="container-fluid">
    <div class="row">
      <div class="col-12 grid-margin">
        <div class="card">    
          <div class="card-body">
            @include('elements.message')
              <div class="col-md-6">
                <div class="form-group row {{ $errors->first('merchant')? 'has-error' : '' }}">
                  <label class="col-sm-3 col-form-label">Firm</label>
                  <div class="col-sm-9">
                    <select class="form-control" name="merchant" id="merchant">
                      <option value="">Merchant Name</option>
                      @if(count($firms)>0)
                        @foreach($firms as $eachMerchant)
                          <option value="{{ $eachMerchant->id }}">
                            {{ $eachMerchant->business_name }}
                          </option>
                        @endforeach
                      @endif
                    </select>
                    <p class="error">{{ $errors->first('merchant')}}</p>
                  </div>
                </div>
              </div>
            <div id="details"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('extra_script')
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
<script src="{{ asset('js/datatables.init.js') }}"></script>
<script type="text/javascript">
  $(document).ready(function(){
      $('#merchant').on('change',function(){
         $('#details').html('');
         var firmId = $(this).val();
         if(firmId!=''){
           $.ajax({
              type  : 'get',
              url   : "{{url('admin/setting/merchant/product')}}"+"/"+firmId,
              success : function(response){
                $('#details').html(response);
              }
           });
         }else{
            alert("Please select Merchant");
         }
      });
  });
</script>
@endsection