<div class="col-lg-12">
  <div class="card">
    <!-- <div class="card-header border-bottom">
      <h4 class="card-title">Alerts with icon</h4>
    </div> -->
    <div class="card-body">
      <div class="row">               
                
        <div class="col-md-12">
          <div class="form-group row">
            <label class="col-sm-3">Account Number</label>
          
              <div class="col-sm-9">
                @if (Helper::isAvsFieldCorrect($resultSet,'acc_number'))
                    <span class="badge badge-success">Matched</span>
                @else
                    <span class="badge badge-danger">Not-Matched</span>
                @endif
              </div>
              
          </div>
        </div>

        <div class="col-md-12">
          <div class="form-group row">
            <label class="col-sm-3 col-form-label">ID/Registration Number</label>
            <div class="col-sm-9">
              @if (Helper::isAvsFieldCorrect($resultSet,'id_number'))
                  <span class="badge badge-success">Matched</span>
              @else
                  <span class="badge badge-danger">Not-Matched</span>
              @endif
              
            </div>
          </div>
        </div>

        <div class="col-md-12">
          <div class="form-group row ">
            <label class="col-sm-3 col-form-label">Beneficiary initials</label>
            <div class="col-sm-9">
              @if (Helper::isAvsFieldCorrect($resultSet,'initials'))
                  <span class="badge badge-success">Matched</span>
              @else
                  <span class="badge badge-danger">Not-Matched</span>
              @endif
              
            </div>
          </div>
        </div>

        <div class="col-md-12">
          <div class="form-group row ">
            <label class="col-sm-3 col-form-label">Surname/Company</label>
            <div class="col-sm-9">
              @if (Helper::isAvsFieldCorrect($resultSet,'last_name'))
                  <span class="badge badge-success">Matched</span>
              @else
                  <span class="badge badge-danger">Not-Matched</span>
              @endif
              
            </div>
          </div>
        </div>
        
        <div class="col-md-12">
          <div class="form-group row">
            <label class="col-sm-3 col-form-label">Is Active</label>
            <div class="col-sm-9">
              @if (Helper::isAvsFieldCorrect($resultSet,'account_open'))
                  <span class="badge badge-success">Yes</span>
              @else
                  <span class="badge badge-danger">No</span>
              @endif
              
            </div>
          </div>
        </div>

        <div class="col-md-12">
          <div class="form-group row">
            <label class="col-sm-3 col-form-label">Accepts debits</label>
            <div class="col-sm-9">
              @if (Helper::isAvsFieldCorrect($resultSet,'accepts_debits'))
                  <span class="badge badge-success">Yes</span>
              @else
                  <span class="badge badge-danger">No</span>
              @endif
              
            </div>
          </div>
        </div>
        
        <div class="col-md-12">
          <div class="form-group row">
            <label class="col-sm-3 col-form-label">Accepts Credit</label>
            <div class="col-sm-9">
              @if (Helper::isAvsFieldCorrect($resultSet,'accepts_credits'))
                  <span class="badge badge-success">Yes</span>
              @else
                  <span class="badge badge-danger">No</span>
              @endif
              
            </div>
          </div>
        </div>
        <div class="col-md-12">
          <div class="form-group row">
            <label class="col-sm-3 col-form-label">Active from last 3 Months</label>
            <div class="col-sm-9">
              @if (Helper::isAvsFieldCorrect($resultSet,'last_3_month'))
                  <span class="badge badge-success">Yes</span>
              @else
                  <span class="badge badge-danger">No</span>
              @endif
              
            </div>
          </div>
        </div>

        <div class="col-md-12">
          <div class="form-group row">
            <label class="col-sm-3 col-form-label">Account Type</label>
            <div class="col-sm-9">
              @if (Helper::isAvsFieldCorrect($resultSet,'acc_type'))
                  <span class="badge badge-success">Matched</span>
              @else
                  <span class="badge badge-danger">Not-Matched</span>
              @endif
              
            </div>
          </div>
        </div>
                
      </div>
    </div>
  </div>
</div>
