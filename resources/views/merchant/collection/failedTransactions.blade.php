@extends('layouts.app') 

@section('extra_style')
    <!-- DataTables -->
    <link rel="stylesheet" type="text/css" href="{{ asset('css/icons.css') }}">
    <link href="{{ asset('plugins/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('plugins/datatables/buttons.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Responsive datatable examples -->
    <link href="{{ asset('plugins/datatables/responsive.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/bootstrap-editable.css') }}" rel="stylesheet">
    <style type="text/css">
        .collection_date_info .editable-container.editable-inline{
            width: 370px;
        }
        .collection_end_date_info .editable-container.editable-inline{
            width: 370px;
        }
    </style>
@endsection 

@section('content')
@php
$service = Config::get('constants.serviceType');
@endphp

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                
                <div class="card-body">
                    <div class="errorhtml"></div>
                    @include('elements.message')
                    
                   
                    <div class="table-responsive" style="overflow-y:scroll; max-height: 600px">
                        <table id="datatable" class="table table-bordered">
                            <thead>
                                <tr>
                                   
                                    <th>Mandate Id</th>
                                    <th>Name</th>
                                    <th>Surname</th>
                                    <th>Amount</th>
                                    <th>Payment Date</th>
                                    <th>Reason</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transactions as $key => $eachTransaction)
                                    <tr>
                                        
                                        
                                        <td>
                                            {{$eachTransaction->customer->mandate_id}}
                                        </td>
                                        <td>
                                            {{$eachTransaction->customer->first_name}}
                                        </td>
                                        <td>
                                            {{$eachTransaction->customer->last_name}} 
                                        </td>
                                        <td>
                                            R {{$eachTransaction->amount}}
                                        </td>
                                        <td>
                                            {{Helper::convertDate($eachTransaction->payment_date)}}
                                        </td>
                                        <td>
                                            {{$eachTransaction->transactionErrorCode->description}}
                                        </td>
                                        <td>

                                            <div class="float-left">
                                                
                                                
                                                <button type="button" class="btn btn-common" onclick="saveAction(this);">Details</button>
                                                
                                          </div>
                                        </td>
                                    </tr>
                                @endforeach
                                
                            </tbody>
                        </table>
                        {{ $transactions->onEachSide(2)->links()}}
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
<script src="{{ asset('js/moment.min.js') }}"></script>
<script src="{{ asset('js/bootstrap-editable.min.js') }}"></script>
<script src="{{ asset('js/bootstrap-editable.js') }}"></script>

@endsection