<div class="col-lg-12">
  <div class="card">
    <!-- <div class="card-header border-bottom">
      <h4 class="card-title">Alerts with icon</h4>
    </div> -->
    <div class="card-body">
    @foreach ($transRecords as $eachRecord)
      <div class="alert alert-primary alert-raised" role="alert">
        <strong>{{Helper::convertDate($eachRecord->created_at,'d-m-y H:i')}} :</strong>
        {{$eachRecord->change_statement}}
      </div>
      @endforeach
    </div>
  </div>
</div>
