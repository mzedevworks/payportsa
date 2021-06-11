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
        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom">
                    <a href="{{ url('admin/holidays/create')}}" class="btn btn-common">
                      Add Holiday</a>
                    <form method="POST" action="{{url('admin/holidays/deletemultiple')}}" class="btn bg-transparent">
                        {{csrf_field()}}
                        {{method_field('DELETE')}}
                        <div id="feildsToDelete">
                            
                        </div>
                        <button id="deleteAllFormBtn" type="submit" disabled="disabled" onclick="return confirm('Are you sure to delete')" class="btn btn-common mr-3"><i class="lni-trash" aria-hidden="true"></i> Delete</button>
                    </form>
                </div>
                <div class="card-body">
                     @include('elements.message')
                    <div class="table-responsive">
                        <table id="holidays-datatable" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" name="checkallHolidays" id="checkallHolidays" class="">
                                    </th>
                                    <th>Holiday Event</th>
                                    <th>Holiday Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
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
var myHolidayTable="";
var selectedHolidays=[];
function showCheckbox(data, type, row, meta){
    var checkedStr="";
    if(selectedHolidays.indexOf(data)!==-1){

        checkedStr="checked='checked'";
    }
    var format='<input type="checkbox" onclick="holidayClicked(this);" name="checkUsers[]" id="checkUsers_'+data+'" value="'+data+'" class="customerCheckboxes" '+checkedStr+'>';
   return format;
}
function generateAction(data, type, row, meta){

    var str='<div class="float-left"><a href="{{url('admin/setting/holidays/update/')}}/'+data+'" class="btn bg-transparent"  data-toggle="tooltip" data-placement="top" data-original-title="Edit"><i class="lni-pencil" aria-hidden="true"></i></a></div>';
    str+='<button type="button" onclick="deleteRecord(\''+data+'\',this);" class="btn bg-transparent"  data-toggle="tooltip" data-placement="top" data-original-title="Delete"><i class="lni-trash" aria-hidden="true"></i></button>';
    return str;
}

function deleteRecord(dataId,elem){
    var con=confirm("Are you sure to delete this record?");
    
    if(con){
        $.ajax({
                
                type  : 'DELETE',
                url   : "{{url('admin/holidays/delete')}}",
                data  : 'holidayId='+dataId,
                success : function(data){
                    data=JSON.parse(data);
                    $.notify('<strong>'+data.message+'</strong>', {
                        'type': data.type,
                        offset: {
                          x:20,
                          y:100,
                        },
                        allow_dismiss: true,
                        newest_on_top: false,
                    });
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
                    var info = myHolidayTable.page.info();
                    if (info.page > 0) {
                        // when we are in the second page or above
                        if (info.recordsTotal-1 > info.page*info.length) {
                            // after removing 1 from the total, there are still more elements
                            // than the previous page capacity 
                            myHolidayTable.draw( false )
                        } else {
                            // there are less elements, so we navigate to the previous page
                            myHolidayTable.page( 'previous' ).draw( 'page' )
                        }
                    }else{
                        myHolidayTable.draw( false )
                    }
                }
            });
    }
}
function holidayClicked(elem){
    var elemVal=parseInt(elem.value);
    //var elemVal=elem.value;
    var arrIndex=selectedHolidays.indexOf(elemVal);
    if($(elem).prop("checked") == true){
        if(arrIndex===-1){
            
            selectedHolidays.push(elemVal);
        }
    }else if($(elem).prop("checked") == false){
        if(arrIndex!==-1){
            selectedHolidays.splice(arrIndex, 1);
        }
    }

    if($("[class='customerCheckboxes']:not(:checked)").length===0){
        $("#checkallHolidays").prop("checked",true);
    }else{
        $("#checkallHolidays").prop("checked",false);
    }
    createFormFeild();
}

function createFormFeild(){
    
    $("#feildsToDelete").html("");

    selectedHolidays.forEach(function(item, index) {
      $("#feildsToDelete").append("<input type='hidden' name='toDelete[]' value='"+item+"'>");
    });
    if(selectedHolidays.length>0){
        $("#deleteAllFormBtn").prop("disabled",false);
    }else{
        $("#deleteAllFormBtn").prop("disabled",true);
    }
}

$(document).ready(function() {

    $("#checkallHolidays").click(function(){
        if($(this).prop("checked") == true){
            $(".customerCheckboxes").each(function() {
                $( this ).prop("checked",true);
                var elemVal=parseInt(this.value);
                //var elemVal=this.value;
                var arrIndex=selectedHolidays.indexOf(elemVal);
                if(arrIndex===-1){
                    selectedHolidays.push(elemVal);
                }
            });
        }else if($(this).prop("checked") == false){
            $(".customerCheckboxes").each(function() {
                $( this ).prop("checked",false);
                var elemVal=parseInt(this.value);
                //var elemVal=this.value;
                var arrIndex=selectedHolidays.indexOf(elemVal);
                if(arrIndex!==-1){
                    selectedHolidays.splice(arrIndex, 1);
                }
            });
        }
        createFormFeild();
    });

    myHolidayTable=$('#holidays-datatable').DataTable( {
        "lengthMenu": [ [25, 50, 100,200,500,1000, -1], [25, 50, 100,200,500,1000, "All"] ],
        serverSide: true,
        searching: true,
        ajax: {
            url: "{{ url('admin/holidays/listing/ajax')}}"
        },
        //dom: "rtiS",
        "columns": [
            {
                "data":0,
                "render": showCheckbox
            },
            {"data":1},
            {"data":2},
            {
                "data":3,
                "render": generateAction
            },
        ],
        columnDefs: [ 
            { orderable: false, targets: [0,3] },
            { searchable: false, targets: [0,3] }
        ],
        "order": [[2, 'desc']],
        deferRender: true,
        "fnDrawCallback": function( oSettings ) {
            if($("[class='customerCheckboxes']:not(:checked)").length===0){
                $("#checkallHolidays").prop("checked",true);
            }else{
                $("#checkallHolidays").prop("checked",false);
            }
            $('[data-toggle="tooltip"]').tooltip();
        }
        // scrollY: 800,
        // scrollCollapse: true
    } );

    //when typing starts in searchbox
    $('#holidays-datatable_filter input').unbind('keypress keyup').bind('keypress keyup', function(e){
        $("#checkallHolidays").prop("checked",false);
        myHolidayTable.fnFilter($(this).val());
    });
    
    
});
</script>
@endsection