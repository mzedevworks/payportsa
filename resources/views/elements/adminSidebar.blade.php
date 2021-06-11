<li class="nav-item {{ Request::is('admin/dashboard') ? 'active' : ''}}">
  <a href="{{ url('admin/dashboard') }}" class="dropdown-toggle">
    <span class="icon-holder">
      <i class="lni-dashboard"></i>
    </span>
    <span class="title">Dashboard</span>
    
  </a>
</li>
<li class="nav-item dropdown  {{ Request::is('admin/payment-wallet/*') ? 'open active' : ''}}" >
  <a href="#">
    
    <span class="icon-holder">
      <i class="lni-dashboard"></i>
    </span>
    <span class="title">Payment Wallet</span>
    <span class="arrow">
      <i class="lni-chevron-right"></i>
    </span>
  </a>
  <ul class="dropdown-menu sub-down">
    <li class="{{ Request::is('admin/payment-wallet/non-allocated') ? 'active' : ''}}">
      <a href="{{ url('admin/payment-wallet/non-allocated') }}">UnAllocated</a>
    </li>
  </ul>
</li>
<li class="nav-item dropdown  {{ Request::is('admin/collection-wallet/*') ? 'open active' : ''}}" >
  <a href="#">
    <span class="icon-holder">
        <i class="lni-dashboard"></i>
      </span>
      <span class="title">Collection Wallet</span>
      <span class="arrow">
        <i class="lni-chevron-right"></i>
      </span>
  </a>
  <ul class="dropdown-menu sub-down">
    
    <li class="{{ Request::is('admin/collection-wallet/profilestats') ? 'active' : ''}}">
      <a href="{{ url('admin/collection-wallet/profilestats') }}">Profile</a>
    </li>
    <li class="{{ Request::is('admin/collection-wallet/topup') ? 'active' : ''}}">
      <a href="{{ url('admin/collection-wallet/topup') }}">Merchant Profile Top up</a>
    </li>
  </ul>
</li>      
<li class="nav-item dropdown {{ Request::is('admin/firms*') ? 'open active' : ''}}">
  <a href="{{ url('admin/firms') }}" class="">
    <span class="icon-holder">
      <i class="lni-user"></i>
    </span>
    Merchants
  </a>
</li>
<li class="nav-item dropdown {{ Request::is('admin/administors*') || Request::is('admin/merchants*') ? 'open' : ''}}">
  <a class="dropdown-toggle" href="#">
      <span class="icon-holder">
        <i class="lni-users"></i>
      </span>
      <span class="title">User Management</span>
      <span class="arrow">
        <i class="lni-chevron-right"></i>
      </span>
  </a>
  <ul class="dropdown-menu sub-down">
    <li class="{{ Request::is('admin/administors*') ? 'active' : ''}}">
      <a href="{{ url('admin/administors') }}">Administrator</a>
    </li>
    <li class="{{ Request::is('admin/merchants*') ? 'active' : ''}}">
      <a href="{{ url('admin/merchants') }}">System Users</a>
    </li>
  </ul>
</li>
<li class="nav-item dropdown {{ Request::is('admin/outputs*') ? 'open' : ''}}">
  <a class="dropdown-toggle" href="#">
      <span class="icon-holder">
        <i class="lni-users"></i>
      </span>
      <span class="title">Outputs</span>
      <span class="arrow">
        <i class="lni-chevron-right"></i>
      </span>
  </a>
  <ul class="dropdown-menu sub-down">
    <li class="{{ Request::is('admin/outputs/collection*') ? 'active' : ''}}">
      <a href="{{ url('admin/outputs/collection') }}">Collection</a>
    </li>
    <li class="{{ Request::is('admin/outputs/payment*') ? 'active' : ''}}">
      <a href="{{ url('admin/outputs/payment') }}">Payments</a>
    </li>

    <li class="{{ Request::is('admin/outputs/avs*') ? 'active' : ''}}">
      <a href="{{ url('admin/outputs/avs') }}">Avs</a>
    </li>
  </ul>
</li>

<li class="nav-item dropdown {{ Request::is('admin/transmission*') ? 'open' : ''}}">
  <a class="dropdown-toggle" href="#">
      <span class="icon-holder">
        <i class="lni-users"></i>
      </span>
      <span class="title">Transmissions</span>
      <span class="arrow">
        <i class="lni-chevron-right"></i>
      </span>
  </a>
  <ul class="dropdown-menu sub-down">
    <li class="{{ Request::is('admin/transmission/collection*') ? 'active' : ''}}">
      <a href="{{ url('admin/transmission/collection') }}">Collection</a>
    </li>
    <li class="{{ Request::is('admin/transmission/payment*') ? 'active' : ''}}">
      <a href="{{ url('admin/transmission/payment') }}">Payments</a>
    </li>

    <li class="{{ Request::is('admin/transmission/avs*') ? 'active' : ''}}">
      <a href="{{ url('admin/transmission/avs') }}">Avs</a>
    </li>
  </ul>
</li>

<li class="nav-item dropdown {{ Request::is('admin/batch-collection*')? 'open active' : ''}}">
  <a href="#" class="dropdown-toggle">
    <span class="icon-holder">
      <i class="lni-wallet"></i>
    </span>
    <span class="title">Collections</span>
    <span class="arrow">
      <i class="lni-chevron-right"></i>
    </span>
  </a>
   
  
  <ul class="dropdown-menu sub-down">
    <li class="nav-item dropdown {{ Request::is('admin/batch-collection/normal*') ? 'open' : ''}}">
      <a href="#" class="dropdown-toggle">
        <span class="icon-holder">
          <i class="lni-wallet"></i>
        </span>
        <span class="title">Standard Batch</span>
        <span class="arrow">
          <i class="lni-chevron-right"></i>
        </span>
      </a>
      <ul class="dropdown-menu sub-down" style="display: {{ Request::is('admin/batch-collection/normal*') ? 'block' : 'none'}}">
            <li class="{{ Request::is('admin/batch-collection/normal/pending*') ? 'active' : ''}}">
              <a href="{{ url('admin/batch-collection/normal/pending') }}">
                Pending Batches
              </a>
            </li>
            <li class="{{ Request::is('admin/batch-collection/normal/queued*') ? 'active' : ''}}">
              <a href="{{ url('admin/batch-collection/normal/queued') }}">Queued to Bank</a>
            </li>
            
            <li class="{{ Request::is('admin/batch-collection/normal/processed*') ? 'active' : ''}}">
              <a href="{{ url('admin/batch-collection/normal/processed') }}">Processed Batches</a>
            </li>
          </ul>
    </li>
    
    
    <li class="nav-item dropdown {{ Request::is('admin/batch-collection/reoccur*') ? 'open' : ''}}">
      <a href="#" class="dropdown-toggle">
        <span class="icon-holder">
          <i class="lni-wallet"></i>
        </span>
        <span class="title">Reoccur Batch</span>
        <span class="arrow">
          <i class="lni-chevron-right"></i>
        </span>
      </a>
      <ul class="dropdown-menu sub-down" style="display: {{ Request::is('admin/batch-collection/reoccur*') ? 'block' : 'none'}}">
        <li class="{{ Request::is('admin/batch-collection/reoccur/pending') ? 'active' : ''}}">
          <a href="{{ url('admin/batch-collection/reoccur/pending') }}">Pending</a>
        </li>
        <li class="{{ Request::is('admin/batch-collection/reoccur/submitted') ? 'active' : ''}}">
          <a href="{{ url('admin/batch-collection/reoccur/submitted') }}">Submitted to bank</a>
        </li>
        
        <li class="{{ Request::is('admin/batch-collection/reoccur/processed') ? 'active' : ''}}">
          <a href="{{ url('admin/batch-collection/reoccur/processed') }}">Processed Batches</a>
        </li>
      </ul>
    </li>

    
  </ul>
  
</li>

<li class="nav-item dropdown {{ Request::is('admin/tranx-report*') ? 'open' : ''}}">
  <a class="dropdown-toggle" href="#">
      <span class="icon-holder">
        <i class="lni-users"></i>
      </span>
      <span class="title">Transaction Report</span>
      <span class="arrow">
        <i class="lni-chevron-right"></i>
      </span>
  </a>
  <ul class="dropdown-menu sub-down">
    <li class="{{ (Request::is('admin/tranx-report/collection*') || Request::is('admin/tranx-report')) ? 'active' : ''}}">
      <a href="{{ url('admin/tranx-report/collection') }}">Collection</a>
    </li>
    <li class="{{ Request::is('admin/tranx-report/payment*') ? 'active' : ''}}">
      <a href="{{ url('admin/tranx-report/payment') }}">Payments</a>
    </li>
    <li class="{{ Request::is('admin/tranx-report/avs*') ? 'active' : ''}}">
      <a href="{{ url('admin/tranx-report/avs') }}">Avs</a>
    </li>
  </ul>
</li>
<li class="nav-item dropdown {{ (Request::is('admin/batch-payment*'))  ? 'open' : ''}}">
  <a href="#" class="dropdown-toggle">
    <span class="icon-holder">
      <i class="lni-wallet"></i>
    </span>
    <span class="title">
      Payments
    </span>
    <span class="arrow">
      <i class="lni-chevron-right"></i>
    </span>
  </a>
  <ul class="dropdown-menu sub-down" >
    <li class="nav-item dropdown {{ Request::is('admin/batch-payment/salary*') ? 'open' : ''}}">
      <a href="#" class="dropdown-toggle">
        <span class="icon-holder">
          <i class="lni-briefcase"></i>
        </span>
        <span class="title">Salary</span>
        <span class="arrow">
          <i class="lni-chevron-right"></i>
        </span>
      </a>
      <ul class="dropdown-menu sub-down" style="display: {{ Request::is('admin/batch-payment/salary*') ? 'block' : 'none'}}">
        <li class="{{ (Request::is('admin/batch-payment/salary/pending*')) ? 'active' : ''}}">
          <a href="{{url('admin/batch-payment/salary/pending')}}">Pending Batches</a>
        </li>
        
        <li class="{{ (Request::is('admin/batch-payment/salary/queued*')) ? 'active' : ''}}">
          <a href="{{url('admin/batch-payment/salary/queued')}}">Queued To Bank</a>
        </li>

        <li class="{{ (Request::is('admin/batch-payment/salary/processed*') ) ? 'active' : ''}}">
          <a href="{{url('admin/batch-payment/salary/processed')}}">Processed Batches</a>
        </li>
      </ul>
    </li>
    <li class="nav-item dropdown {{ Request::is('admin/batch-payment/credit*') ? 'open' : ''}}">
      <a href="#" class="dropdown-toggle">
        <span class="icon-holder">
          <i class="lni-briefcase"></i>
        </span>
        <span class="title">Creditors</span>
        <span class="arrow">
          <i class="lni-chevron-right"></i>
        </span>
      </a>
      <ul class="dropdown-menu sub-down" style="display: {{ Request::is('admin/batch-payment/credit*') ? 'block' : 'none'}}">
        <li class="{{ (Request::is('admin/batch-payment/credit/pending*')) ? 'active' : ''}}">
          <a href="{{url('admin/batch-payment/credit/pending')}}">Pending Batches</a>
        </li>
        
        <li class="{{ (Request::is('admin/batch-payment/credit/queued*')) ? 'active' : ''}}">
          <a href="{{url('admin/batch-payment/credit/queued')}}">Queued To Bank</a>
        </li>

        <li class="{{ (Request::is('admin/batch-payment/credit/processed*')) ? 'active' : ''}}">
          <a href="{{url('admin/batch-payment/credit/processed')}}">Processed Batches</a>
        </li>
      </ul>
    </li>
  </ul>
</li> 

<!-- <li class="nav-item dropdown">
  <a class="dropdown-toggle" href="#">
    <span class="icon-holder">
      <i class="lni-wallet"></i>
    </span>
    <span class="title">Payments</span>
    <span class="arrow">
      <i class="lni-chevron-right"></i>
    </span>
  </a>
  <ul class="dropdown-menu sub-down">
    <li>
      <a href="#">Creditors</a>
    </li>
   
  </ul>
</li>
<li class="nav-item dropdown">
  <a class="dropdown-toggle" href="#">
    <span class="icon-holder">
      <i class="lni-layers"></i>
    </span>
    <span class="title">Collections</span>
    <span class="arrow">
      <i class="lni-chevron-right"></i>
    </span>
  </a>
  <ul class="dropdown-menu sub-down">
    <li>
      <a href="#">Creditors</a>
    </li>
   
  </ul>
</li> -->



<li class="nav-item dropdown {{ Request::is('admin/setting*') ? 'open' : ''}}">
  <a class="dropdown-toggle" href="#">
    <span class="icon-holder">
      <i class="lni-timer"></i>
    </span>
    <span class="title">Setting</span>
    <span class="arrow">
      <i class="lni-chevron-right"></i>
    </span>
  </a>
  <ul class="dropdown-menu sub-down">
    <li class="{{ Request::is('admin/setting/holidays*') ? 'active' : ''}}">
      <a href="{{ url('admin/setting/holidays')}}">Public Holidays</a>
    </li>
    <li class="{{ Request::is('admin/setting/profile-limit*') ? 'active' : ''}}">
      <a href="{{ url('admin/setting/profile-limit')}}">Profile Limit</a>
    </li>
    <!-- <li  class="{{ Request::is('admin/setting/setproduct') ? 'active' : ''}}">
      <a href="{{ url('admin/setting/setproduct')}}">Set up products</a>
    </li> -->
    
  </ul>
  
</li>
<li class="nav-item dropdown {{ Request::is('admin/banks*') ? 'open' : ''}}">
  <a class="dropdown-toggle" href="{{ url('admin/banks')}}">
    <span class="icon-holder">
      <i class="lni-timer"></i>
    </span>
    <span class="title">Banks</span>
    
  </a>
  
  
</li>