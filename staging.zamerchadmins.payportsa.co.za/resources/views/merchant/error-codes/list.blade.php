<!-- @extends('layouts.app')  -->

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
        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom">
                    {{$pagename}}
                </div>
                <div class="card-body">
                    @include('elements.message')
                    <div class="table-responsive">
                        <table id="employees-datatable" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Rejection Reason</th>
                                    <th>Full Description</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach ($errorCodes as $eachErrorCode)
                                <tr>
                                    <td>{{$eachErrorCode['code']}}</th>
                                    <td>{{$eachErrorCode['reason']}}</th>
                                    <td>{{$eachErrorCode['desc']}}</th>
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

<!-- Responsive examples -->
<script src="{{ asset('plugins/datatables/dataTables.responsive.min.js') }}"></script>
<script src="{{ asset('plugins/datatables/responsive.bootstrap4.min.js') }}"></script>

<!-- Datatable init js -->
<!-- <script src="{{ asset('js/datatables.init.js') }}"></script> -->
<script type="text/javascript">
var myEmployeeTable="";
$(document).ready(function() {
    myEmployeeTable=$('#employees-datatable').DataTable( {
        serverSide: false,
        searching: true,
        //dom: "rtiS",
    } );
});
</script>
@endsection