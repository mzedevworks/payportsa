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
                <div class="card-body">
                    @include('elements.message')
                    <div class="table-responsive">
                        <table id="datatable" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Customer Name</th>
                                    <th>Customer Surname</th>
                                    <th>Customer Email</th>
                                    <th>Payment Date</th>
                                    <th>OnceOff Amount</th>
                                    <th>Recurring Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(count($customers)>0)
                                @foreach($customers as $customer)
                                <tr>
                                <td><a href="{{ url('merchant/customers/update/'.encrypt($customer->customer_id))}}">{{ $customer->first_name }}</a></td>
                                <td><a href="{{ url('merchant/customers/update/'.encrypt($customer->customer_id))}}">{{ $customer->last_name }}</a></td>
                                <td><a href="{{ url('merchant/customers/update/'.encrypt($customer->customer_id))}}">{{ $customer->email }}</a></td>
                                <td>{{ $customer->payment_date }}</td>
                                <td>{{ $customer->once_off_amount }}</td>
                                <td>{{ $customer->recurring_amount }}</td>
                                </tr></a>
                                @endforeach
                                @endif
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
    $(document).ready(function() {
        $('#datatable').DataTable();
    });
</script>
@endsection