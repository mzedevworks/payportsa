@section('sidebar')

<div class="side-nav expand-lg">
    <div class="side-nav-inner">
      <ul class="side-nav-menu">
        <li class="side-nav-header">
          <span>Navigation</span>
        </li>
        @if(auth()->user()->role_id==1)
          @include('elements.adminSidebar')
        @endif
        
        @if(auth()->user()->role_id==3 || auth()->user()->role_id==4)
          @include('elements.merchentSidebar')
        @endif
      </ul>
    </div>
  </div>
@show