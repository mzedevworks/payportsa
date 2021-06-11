<ul class="nav nav-tabs" role="tablist">
  <li class="nav-item">
    <a href="{{url('admin/firms/info/').'/'.Helper::encryptVar($firm['id'])}}" class="nav-link {{($tabName=='firmInfo')?'active':''}}">Profile</a>
  </li>
  <li class="nav-item">
    <a href="{{url('admin/firms/user/').'/'.Helper::encryptVar($firm['id'])}}" class="nav-link {{($tabName=='firmUser')?'active':''}}">Merchant Admin</a>
  </li>
  <li class="nav-item">
    <a href="{{url('admin/firms/rates/').'/'.Helper::encryptVar($firm['id'])}}" class="nav-link {{($tabName=='firmRate')?'active':''}}">Rates</a>
  </li>
  @if($firm->is_collection =='1')         
    <li class="nav-item">
      <a href="{{url('admin/firms/collection-limit/').'/'.Helper::encryptVar($firm['id'])}}" class="nav-link {{($tabName=='profileLimit')?'active':''}}">Collection Profile</a>
    </li>
    <li class="nav-item">
      <a href="{{url('admin/firms/monthly-collection/').'/'.Helper::encryptVar($firm['id'])}}" class="nav-link {{($tabName=='monthlyCollection')?'active':''}}">Collection of Month</a>
    </li>
  @endif

  @if($firm->is_payment =='1')         
    <li class="nav-item">
      <a href="{{url('admin/firms/payment-stats/').'/'.Helper::encryptVar($firm['id'])}}" class="nav-link {{($tabName=='profileStats')?'active':''}}">Payment Profile</a>
    </li>
    
  @endif
</ul>