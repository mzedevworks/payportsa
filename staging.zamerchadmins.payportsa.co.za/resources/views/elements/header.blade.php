@section('header')
<div class="header navbar">
  	<div class="header-container">
        <div class="nav-logo">
          <a href="{{ url('/') }}">
            <span class="logo">
               <img src="{{ asset('img/logo_new.png') }}" alt="" width="200px">
            </span>
          </a>
        </div>
        
        <ul class="nav-left">
          <li>
            <a class="sidenav-fold-toggler" href="javascript:void(0);">
              <i class="lni-menu"></i>
            </a>
            <a class="sidenav-expand-toggler" href="javascript:void(0);">
              <i class="lni-menu"></i>
            </a>
          </li>
        </ul>
        <ul class="nav-right">
          
          <li class="user-profile dropdown dropdown-animated scale-left">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
              <img class="profile-img img-fluid" src="{{ asset('img/avatar/avatar.jpg') }}" alt=""> 
            </a>
            <ul class="dropdown-menu dropdown-md">
              <li>
                <ul class="list-media">
                  <li class="list-item avatar-info">
                    <div class="media-img">
                      <img src="{{ asset('img/avatar/avatar.jpg') }}" alt="">
                    </div>
                    <div class="info">
                      <span class="title text-semibold">
                        {{ isset(auth()->user()->first_name) && isset(auth()->user()->first_name) ? auth()->user()->first_name.' '.auth()->user()->last_name : '' }}</span>
                      
                    </div>
                  </li>
                </ul>
              </li>
              <li role="separator" class="divider"></li>
              <li>
                <a href="">
                  <i class="lni-cog"></i>
                  <span>Setting</span>
                </a>
              </li>
              <li>
                @if(auth()->user()->role_id==1 || auth()->user()->role_id==2)
                <a href="{{ url('admin/edit/profile') }}">
                @endif
                @if(auth()->user()->role_id==3)
                <a href="{{ url('merchant/edit/profile') }}">
                @endif
                  <i class="lni-user"></i>
                  <span>Profile</span>
                </a>
              </li>
              
              @if((auth()->user()->role_id==3 || auth()->user()->role_id==4) && Session::has('admin_id'))
              <li>
                <a href="{{ route('merchant.admin.login') }}" onclick="event.preventDefault(); 
                     document.getElementById('admin-form').submit();">
                  <i class="lni-shift-right"></i>
                  <span>Login Back As Admin</span>
                </a>
                <form id="admin-form" action="{{ route('merchant.admin.login') }}" method="POST" style="display: none;">
                            @csrf
                </form>
              </li>
              @endif
              <li>
                <a href="{{ route('change.password') }}">
                  <i class="lni-lock"></i>
                  <span>Change Password</span>
                </a>
              </li>
              <li>
                 <a  href="{{ route('logout') }}" title="sign out" onclick="event.preventDefault(); 
                     document.getElementById('logout-form').submit();">
                    <i class="lni-exit"></i>
                  <span>Logout</span>
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                </form>
              </li>
            </ul>
          </li>
        </ul>
  	</div>
</div>
@show