@if(Session::has('status'))
        <div class="row clearfix">
                    <div class="alert alert-{{ Session::get('class') }} col-md-12" id="myalert">
                      <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                      {{ Session::get('status') }}
                    </div>
        </div>
@endif
@if(Session::has('success-message'))
        <div class="row clearfix">
                    <div class="alert alert-success col-md-12" id="myalert">
                      <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                      {{ Session::get('success-message') }}
                    </div>
        </div>
@endif
@if(Session::has('error-msg'))
        <div class="row clearfix">
                    <div class="alert alert-danger col-md-12" id="myalert">
                      <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                      {{ Session::get('error-msg') }}
                    </div>
        </div>
@endif