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
                    <a href="{{ url('admin/merchants/create')}}" class="btn btn-common">
                      Add User</a>
                       <?php /* <form method="POST" action="{{url('admin/merchants/deletemultiple')}}" class="btn bg-transparent">
                        {{csrf_field()}}
                        {{method_field('DELETE')}}
                        <div id="feildsToDelete">
                            
                        </div>
                        <button id="deleteAllFormBtn" type="submit" disabled="disabled" onclick="return confirm('Are you sure to delete')" class="btn btn-common mr-3"><i class="lni-trash" aria-hidden="true"></i> Delete</button>
                    </form> */ ?>
                </div>
                <div class="card-body">
                    @include('elements.message')
                    <div class="table-responsive">
                        <table id="merchants-datatable" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" name="checkallMerchants" id="checkallMerchants" class=""></th>
                                    <th>First Name</th>
                                    <th>Last Name</th>
                                    <th>Firm</th>
                                    <th>Role</th>
                                    <th>Email</th>
                                    <th>Contact</th>
                                    <th>Status</th>
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
var selectedMerchants=[];
var myMerchantTable="";
function showCheckbox(data, type, row, meta){
    var checkedStr="";
    if(selectedMerchants.indexOf(data)!==-1){

        checkedStr="checked='checked'";
    }
    var format='<input type="checkbox" onclick="merchantsClicked(this);" name="checkAdministors[]" id="checkAdministors_'+data+'" value="'+data+'" class="merchantsCheckboxes" '+checkedStr+'>';
   return format;
}

function generateAction(data, type, row, meta){
    var str='<div class="float-left"><a href="{{url('admin/merchants/update')}}/'+data+'" class="btn bg-transparent"  data-toggle="tooltip" data-placement="top" data-original-title="Edit"><i class="lni-pencil" aria-hidden="true"></i></a></div>';
    //str+='<button type="button" onclick="deleteRecord(\''+data+'\',this);" class="btn bg-transparent"><i class="lni-trash" aria-hidden="true"></i></button>'
    str+='<a href="{{url('admin/login/as/merchant')}}/'+data+'" class="btn btn-common">Login</a>'
    return str;
}

function deleteRecord(dataId,elem){
    var con=confirm("Are you sure to delete this record?");
    
    if(con){
        $.ajax({
                
                type  : 'DELETE',
                url   : "{{url('admin/merchants/delete')}}",
                data  : 'userId='+dataId,
                success : function(data){
                    data=JSON.parse(data);
                    console.log(data);
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
                    var info = myMerchantTable.page.info();
 
                    if (info.page > 0) {
                        // when we are in the second page or above
                        if (info.recordsTotal-1 > info.page*info.length) {
                            // after removing 1 from the total, there are still more elements
                            // than the previous page capacity 
                            myMerchantTable.draw( false )
                        } else {
                            // there are less elements, so we navigate to the previous page
                            myMerchantTable.page( 'previous' ).draw( 'page' )
                        }
                    }else{
                        myMerchantTable.draw( false )
                    }
                }
            });
    }
}
function merchantsClicked(elem){
    var elemVal=parseInt(elem.value);
    //var elemVal=elem.value;
    var arrIndex=selectedMerchants.indexOf(elemVal);
    if($(elem).prop("checked") == true){
        if(arrIndex===-1){
            
            selectedMerchants.push(elemVal);
        }
    }else if($(elem).prop("checked") == false){
        if(arrIndex!==-1){
            selectedMerchants.splice(arrIndex, 1);
        }
    }

    if($("[class='merchantsCheckboxes']:not(:checked)").length===0){
        $("#checkallMerchants").prop("checked",true);
    }else{
        $("#checkallMerchants").prop("checked",false);
    }
    createFormFeild();
}

function createFormFeild(){
    
    $("#feildsToDelete").html("");

    selectedMerchants.forEach(function(item, index) {
      $("#feildsToDelete").append("<input type='hidden' name='toDelete[]' value='"+item+"'>");
    });
    if(selectedMerchants.length>0){
        $("#deleteAllFormBtn").prop("disabled",false);
    }else{
        $("#deleteAllFormBtn").prop("disabled",true);
    }

}
$(document).ready(function() {

    $("#checkallMerchants").click(function(){
        if($(this).prop("checked") == true){
            $(".merchantsCheckboxes").each(function() {
                $( this ).prop("checked",true);
                var elemVal=parseInt(this.value);
                //var elemVal=this.value;
                var arrIndex=selectedMerchants.indexOf(elemVal);
                if(arrIndex===-1){
                    selectedMerchants.push(elemVal);
                }
            });
        }else if($(this).prop("checked") == false){
            $(".merchantsCheckboxes").each(function() {
                $( this ).prop("checked",false);
                var elemVal=parseInt(this.value);
                //var elemVal=this.value;
                var arrIndex=selectedMerchants.indexOf(elemVal);
                if(arrIndex!==-1){
                    selectedMerchants.splice(arrIndex, 1);
                }
            });
        }
        createFormFeild();
    });

    myMerchantTable=$('#merchants-datatable').DataTable( {
        "lengthMenu": [ [25, 50, 100,200,500,1000, -1], [25, 50, 100,200,500,1000, "All"] ],
        serverSide: true,
        searching: true,
        ajax: {
            url: "{{ url('admin/merchants/listing/ajax')}}"
        },
        //dom: "rtiS",
        "columns": [
            {
                "data":0,
                "render": showCheckbox
            },
            {"data":1},
            {"data":2},
            {"data":3},
            {"data":4},
            {"data":5},
            {"data":6},
            {"data":7},
            {
                "data":8,
                "render": generateAction
            },
        ],
        columnDefs: [ 
            { orderable: false, targets: [0,8] },
            { searchable: false, targets: [0,7,8] }
        ],
        "order": [[1, 'asc']],
        deferRender: true,
        "fnDrawCallback": function( oSettings ) {
            
            if($("[class='merchantsCheckboxes']:not(:checked)").length===0){
                $("#checkallMerchants").prop("checked",true);
            }else{
                $("#checkallMerchants").prop("checked",false);
            }
            $('[data-toggle="tooltip"]').tooltip();
        }
        // scrollY: 800,
        // scrollCollapse: true
    } );

    //when typing starts in searchbox
    $('#merchants-datatable_filter input').unbind('keypress keyup').bind('keypress keyup', function(e){
        $("#checkallMerchants").prop("checked",false);
        myMerchantTable.fnFilter($(this).val());
    });
    
    
});
</script>
@endsection