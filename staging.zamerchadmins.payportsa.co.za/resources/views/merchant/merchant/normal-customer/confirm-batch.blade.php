@extends('layouts.app') 

@section('extra_style')
    <!-- DataTables -->
    <link rel="stylesheet" type="text/css" href="{{ asset('css/icons.css') }}">
    <link href="{{ asset('plugins/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('plugins/datatables/buttons.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Responsive datatable examples -->
    <link href="{{ asset('plugins/datatables/responsive.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/bootstrap-editable.css') }}" rel="stylesheet">
@endsection 

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom">
                   
                </div>
                <div class="card-body">
                    <div class="errorhtml"></div>
                    @include('elements.message')
                    
                    
                    <form method="POST" action="{{url('merchant/collection/normal/savecsvbatch')}}" class="btn bg-transparent" id="approveStatusForm">
                        {{csrf_field()}}
                        {{method_field('POST')}}
                        <input type="hidden" name="actionType" value="approve"/>
                        <input type="hidden" name="batch_name" value="{{$postData['batch_name']}}"/>
                        <input type="hidden" name="service_type" value="{{$postData['service_type']}}"/>
                        <input type="hidden" name="collection_date" value="{{$postData['collection_date']}}"/>
                        
                        <div id="batchContainerDiv">
                            @foreach($selectedCustomer as $key => $eachCustomer)
                            <input type='hidden' name='customerList[]' value="{{$eachCustomer['id']}}">
                            <input type='hidden' name='customerAmount[]' value="{{$eachCustomer['amount']}}">
                            <input type='hidden' name='customerReff[]' value="{{$eachCustomer['reference']}}">
                            @endforeach
                        </div>
                        <button id="createBatchFormBtn" type="button" onclick=" confirmFormSubmit('Are you sure?','approveStatusForm')" class="btn btn-common mr-3"><i class="lni-thumbs-up" aria-hidden="true"></i> Create Batch</button>
                        <a class="btn btn-common purple-btn mr-4" href="{{ url('merchant/collection/normal/create-batch')}}" class="purple-btn">Cancel</a>
                    </form>
                    
                    
                    <div class="table-responsive" style="overflow-y:scroll; max-height: 600px">
                        <table id="datatable" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Mandate Id</th>
                                    <th>Name</th>
                                    <th>Surname</th>
                                    <th>Amount</th>
                                    <th>Reffrence</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($selectedCustomer as $key => $eachCustomer)
                                    <tr>
                                                                                
                                        <td>
                                            {{$eachCustomer['cust']->mandate_id}}
                                       </td>
                                       <td>
                                            {{$eachCustomer['cust']->first_name}}
                                       </td>
                                       <td>
                                            {{$eachCustomer['cust']->last_name}}
                                       </td>
                                       <td>
                                            {{$eachCustomer['amount']}}
                                       </td>
                                       <td>
                                            {{$eachCustomer['reference']}}
                                       </td>
                                        
                                    </tr>
                                @endforeach
                                
                            </tbody>
                        </table>
                    </div>
                    
                </div>
            </div>
        </div>
        <!-- end col -->
    </div>
    <!-- end row -->
@endsection 

@section('extra_script')
<!-- Required datatable js -->
<script src="{{ asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/dataTables.bootstrap4.min.js') }}"></script>
<!-- Buttons examples -->
<script src="{{ asset('plugins/datatables/dataTables.buttons.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/buttons.bootstrap4.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/jszip.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/vfs_fonts.js') }}"></script>
<script src="{{ asset('plugins/datatables/buttons.html5.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/buttons.print.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/buttons.colVis.min.js') }}"></script>
<!-- Responsive examples -->
<script src="{{ asset('plugins/datatables/dataTables.responsive.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/responsive.bootstrap4.min.js') }}"></script>

<!-- Datatable init js -->
<!-- <script src="{{ asset('js/datatables.init.js') }}"></script> -->

<script src="{{ asset('js/bootstrap-editable.min.js') }}"></script>
<script src="{{ asset('js/bootstrap-editable.js') }}"></script>
<script type="text/javascript">
function confirmFormSubmit(message,formId){
    confirmDialog(message, (ans) => {
      if (ans) {
        $("#"+formId).submit();
        }
     });
}
    
      
    $(document).ready(function() {
        $('#datatable').DataTable( {
            "fnDrawCallback": function( oSettings ) {
                $('.editable').editable();
                $('.bank_select').editable({
                    'mode'  : 'inline',
                    'source': function() {
                        return bank_name;
                    },
                });
                
                $('.account_type').editable({
                    'mode'  : 'inline',
                    'source': function() {
                        return account_type;
                    },
                });
            }
        });
    });

    
</script>
@endsection