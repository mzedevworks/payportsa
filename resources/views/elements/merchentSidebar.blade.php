<li class="{{ Request::is('merchant/dashboard') ? 'active' : ''}}">
 	<a href="{{ url('merchant/dashboard') }}" class="dropdown-toggle">
		<span class="icon-holder">
	  		<i class="lni-dashboard"></i>
		</span>
		<span class="title">Dashboard</span>
		
 	</a>
 	
</li>
@if($firmDetails->is_payment===1)
<li class="{{ Request::is('merchant/wallet/payment') ? ' active' : ''}}">
	<a href="{{ url('merchant/wallet/payment') }}">
  		<span class="icon-holder">
		  <i class="lni-wallet"></i>
		</span>Payment Limits
	</a>
</li>
@endif
@if($firmDetails->is_collection===1)
<li class="{{ Request::is('merchant/wallet/collection') ? ' active' : ''}}">
	<a href="{{ url('merchant/wallet/collection') }}">
  		<span class="icon-holder">
		  <i class="lni-layers"></i>
		</span>
		Collection Limits
	</a>
</li>
@endif
@if($firmDetails->is_payment===1)
<li class="nav-item dropdown {{ (Request::is('merchant/employees*') || Request::is('merchant/creditors*'))  ? 'open' : ''}}">
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
 	@if($firmDetails->is_salaries===1)
 	<ul class="dropdown-menu sub-down" >
		<li class="nav-item dropdown {{ Request::is('merchant/employees*') ? 'open' : ''}}">
		  	<a href="#" class="dropdown-toggle">
			 	<span class="icon-holder">
					<i class="lni-briefcase"></i>
			 	</span>
			 	<span class="title">Salary</span>
			 	<span class="arrow">
					<i class="lni-chevron-right"></i>
			 	</span>
		  	</a>
		  	<ul class="dropdown-menu sub-down" style="display:{{ Request::is('merchant/employees*') ? 'block' : 'none'}}">
			 	<li class="{{ Request::is('merchant/employees') ? 'active' : ''}}">
					<a href="{{ url('merchant/employees') }}">
						<span class="icon-holder">
							<i class="lni-users"></i>
					 	</span>
					 	<span class="title">Employees</span>
					</a>
			 	</li>
			 	<!-- only for merchant admin -->
			  	@if(auth()->user()->role_id==-3)
				<li class="{{ Request::is('merchant/employees/pending-list') ? 'active' : ''}}">
					<a href="{{ url('merchant/employees/pending-list') }}">
						<span class="icon-holder">
							<i class="lni-check-box"></i>
					 	</span>
						Pending Employees
					</a>
			 	</li>
			 	@endif
			 	<li class="{{ Request::is('merchant/employees/create-batch') ? 'active' : ''}}">
					<a href="{{ url('merchant/employees/create-batch') }}">
						<span class="icon-holder">
							<i class="lni-check-box"></i>
					 	</span>
						Create Batch
					</a>
			 	</li>
			 	
			 	<li class="nav-item dropdown ">
				 	<a href="#" class="dropdown-toggle">
					 	<span class="icon-holder">
							<i class="lni-book"></i>
					 	</span>
					 	<span>Batch History</span>
					 	<span class="arrow">
							<i class="lni-chevron-right"></i>
					 	</span>
				  	</a>
				  	<ul class="dropdown-menu sub-down" style="">
					 	<li class="{{ (Request::is('merchant/employees/batch/pending') || Request::is('merchant/employees/batch/pending-*')) ? 'active' : ''}}">
							<a href="{{url('merchant/employees/batch/pending')}}">Pending Batches</a>
					 	</li>
					 	
					 	<li class="{{ (Request::is('merchant/employees/batch/queued') || Request::is('merchant/employees/batch/queued-t*')) ? 'active' : ''}}">
							<a href="{{url('merchant/employees/batch/queued')}}">Queued To Bank</a>
					 	</li>

					 	<li class="{{ (Request::is('merchant/employees/batch/processed') || Request::is('merchant/employees/batch/processed-t*') ) ? 'active' : ''}}">
							<a href="{{url('merchant/employees/batch/processed')}}">Processed Batches</a>
					 	</li>
				  	</ul>
			    </li>
		  </ul>
		</li>

 	</ul>
 	@endif

 	@if($firmDetails->is_creditors===1)
 	<ul class="dropdown-menu sub-down">
		<li class="nav-item dropdown {{ Request::is('merchant/creditors*') ? 'open' : ''}}">
		  	<a href="#" class="dropdown-toggle">
			 	<span class="icon-holder">
					<i class="lni-briefcase"></i>
			 	</span>
			 	<span class="title">Creditors</span>
			 	<span class="arrow">
					<i class="lni-chevron-right"></i>
			 	</span>
		  	</a>
		  	<ul class="dropdown-menu sub-down"  style="display:{{ Request::is('merchant/creditors*') ? 'block' : 'none'}}">
		  		<li class="{{ Request::is('merchant/creditors') ? 'active' : ''}}">
					<a href="{{ url('merchant/creditors') }}">
						<span class="icon-holder">
							<i class="lni-users"></i>
					 	</span>
					 	<span class="title">Creditors</span>
					</a>
			 	</li>
			 	<li class="{{ Request::is('merchant/creditors/create-batch') ? 'active' : ''}}">
					<a href="{{ url('merchant/creditors/create-batch') }}">
						<span class="icon-holder">
							<i class="lni-check-box"></i>
					 	</span>
						Create Batch
					</a>
			 	</li>
			 	<li class="nav-item dropdown ">
				 	<a href="#" class="dropdown-toggle">
					 	<span class="icon-holder">
							<i class="lni-book"></i>
					 	</span>
					 	<span>Batch History</span>
					 	<span class="arrow">
							<i class="lni-chevron-right"></i>
					 	</span>
				  	</a>
				  	<ul class="dropdown-menu sub-down" style="">
					 	<li class="{{ (Request::is('merchant/creditors/batch/pending') || Request::is('merchant/creditors/batch/pending-*')) ? 'active' : ''}}">
							<a href="{{url('merchant/creditors/batch/pending')}}">Pending Batches</a>
					 	</li>
					 	
					 	<li class="{{ (Request::is('merchant/creditors/batch/queued') || Request::is('merchant/creditors/batch/queued-t*')) ? 'active' : ''}}">
							<a href="{{url('merchant/creditors/batch/queued')}}">Queued To Bank</a>
					 	</li>

					 	<li class="{{ (Request::is('merchant/creditors/batch/processed') || Request::is('merchant/creditors/batch/processed-t*') ) ? 'active' : ''}}">
							<a href="{{url('merchant/creditors/batch/processed')}}">Processed Batches</a>
					 	</li>
				  	</ul>
			    </li>
		  	</ul>
		</li>
 	</ul>
 	@endif
</li>
@endif

@if($firmDetails->is_collection===1)
<li class="nav-item dropdown {{ Request::is('merchant/collection*') ? 'open active' : ''}}">
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
		<!-- <li class="{{ Request::is('merchant/collection/statement') ? 'active' : ''}}">
		  <a href="{{ url('merchant/collection/statement') }}" class="">
			 <span class="icon-holder">
				<i class="lni-users"></i>
			 </span>
			 Transaction Statements
		  </a>
		</li> -->
		@if($firmDetails->is_normal_collection===1)
		<li class="nav-item dropdown {{ Request::is('merchant/collection/normal*') ? 'open' : ''}}">
		  	<a href="#" class="dropdown-toggle">
			 	<span class="icon-holder">
					<i class="lni-wallet"></i>
			 	</span>
			 	<span class="title">Standard</span>
			 	<span class="arrow">
					<i class="lni-chevron-right"></i>
			 	</span>
		  	</a>
			<ul class="dropdown-menu sub-down" style="display:{{ Request::is('merchant/collection/normal*') ? 'block' : 'none'}}">
				<li class="{{ (Request::is('merchant/collection/normal/customers') || Request::is('merchant/collection/normal/customer/create') || Request::is('merchant/collection/normal/customer/upload')) ? 'active' : ''}}">
				  <a href="{{ url('merchant/collection/normal/customers') }}" class="">
					 <span class="icon-holder">
						<i class="lni-users"></i>
					 </span>
					 Customers
				  </a>
				</li>
			  	
			  	<!-- only for merchant admin -->
			  	<?php /* @if(auth()->user()->role_id==3)
				<li class="{{ Request::is('merchant/collection/normal/customer/pending-list') ? 'active' : ''}}">
					<a href="{{ url('merchant/collection/normal/customer/pending-list') }}">
						<span class="icon-holder">
							<i class="lni-check-box"></i>
					 	</span>
						Approve Customer
					</a>
			 	</li>
			 	@endif
			 	*/ ?>
			 	<li class="{{ Request::is('merchant/collection/normal/create-batch') ? 'active' : ''}}">
					<a href="{{ url('merchant/collection/normal/create-batch') }}">
						<span class="icon-holder">
							<i class="lni-check-box"></i>
					 	</span>
						Create Batch
					</a>
			 	</li>
			 	<li class="nav-item dropdown {{ Request::is('merchant/collection/normalbatch*') ? 'open' : ''}}">
				 	<a href="#" class="dropdown-toggle">
					 	<span class="icon-holder">
							<i class="lni-book"></i>
					 	</span>
					 	<span>Batch History</span>
					 	<span class="arrow">
							<i class="lni-chevron-right"></i>
					 	</span>
				  	</a>
				  	<ul class="dropdown-menu sub-down" style="display: {{ Request::is('merchant/collection/normalbatch*') ? 'block' : 'none'}}">
					 	<li class="{{ Request::is('merchant/collection/normalbatch/pending*') ? 'active' : ''}}">
							<a href="{{ url('merchant/collection/normalbatch/pending') }}">
								Pending Batches
							</a>
					 	</li>
					 	<!-- <li class="{{ Request::is('merchant/collection/history/approved/batches') ? 'active' : ''}}">
							<a href="{{ url('merchant/collection/history/approved/batches')}}">Queued for submission</a>
					 	</li> -->
					 	<!-- <li class="{{ Request::is('merchant/collection/normalbatch/approved*') ? 'active' : ''}}">
							<a href="{{ url('merchant/collection/normalbatch/approved') }}">Approved Batches</a>
					 	</li> -->

					 	<li class="{{ Request::is('merchant/collection/normalbatch/queued*') ? 'active' : ''}}">
							<a href="{{ url('merchant/collection/normalbatch/queued') }}">Queued to Bank</a>
					 	</li>
					 	
					 	<li class="{{ Request::is('merchant/collection/normalbatch/processed*') ? 'active' : ''}}">
							<a href="{{ url('merchant/collection/normalbatch/processed-list') }}">Processed Batches</a>
					 	</li>
				  	</ul>
			    </li>
			 	<li class="{{ Request::is('merchant/collection/normal/failed') ? 'active' : ''}}">
					<a href="{{ url('merchant/collection/normal/failed') }}">
						<span class="icon-holder">
							<i class="lni-check-box"></i>
					 	</span>
						Failed Transactions
					</a>
			 	</li>
			 	<li class="{{ Request::is('merchant/collection/normal/disputes') ? 'active' : ''}}">
					<a href="{{ url('merchant/collection/normal/disputes') }}">
						<span class="icon-holder">
							<i class="lni-check-box"></i>
					 	</span>
						Dispute Transactions
					</a>
			 	</li>
			 	<li class="{{ Request::is('merchant/collection/normal/reports') ? 'active' : ''}}">
					<a href="{{ url('merchant/collection/normal/reports') }}">
						<span class="icon-holder">
							<i class="lni-check-box"></i>
					 	</span>
						Transaction Report
					</a>
			 	</li>
			 	
			</ul>
		</li>
		@endif
	
		@if($firmDetails->is_reoccur_collection===1)
		<li class="nav-item dropdown {{ Request::is('merchant/collection/reoccur*') ? 'open' : ''}}">
		  	<a href="#" class="dropdown-toggle">
			 	<span class="icon-holder">
					<i class="lni-wallet"></i>
			 	</span>
			 	<span class="title">Recurring</span>
			 	<span class="arrow">
					<i class="lni-chevron-right"></i>
			 	</span>
		  	</a>
			<ul class="dropdown-menu sub-down" style="display:{{ Request::is('merchant/collection/reoccur*') ? 'block' : 'none'}}">
				<li class="{{ (Request::is('merchant/collection/reoccur/customers') || Request::is('merchant/collection/reoccur/customer/create') || Request::is('merchant/collection/reoccur/customer/upload')) ? 'active' : ''}}">
				  <a href="{{ url('merchant/collection/reoccur/customers') }}" class="">
					 <span class="icon-holder">
						<i class="lni-users"></i>
					 </span>
					 Customers
				  </a>
				</li>
				
			  	
			  	
			  	<!-- only for merchant admin -->
			  	@if(auth()->user()->role_id==3)
				<li class="{{ Request::is('merchant/collection/reoccur/customer/pending-list') ? 'active' : ''}}">
					<a href="{{ url('merchant/collection/reoccur/customer/pending-list') }}">
						<span class="icon-holder">
							<i class="lni-check-box"></i>
					 	</span>
						Approve Customer
					</a>
			 	</li>
			 	@endif
			 	<li class="{{ Request::is('merchant/collection/reoccur/failed') ? 'active' : ''}}">
					<a href="{{ url('merchant/collection/reoccur/failed') }}">
						<span class="icon-holder">
							<i class="lni-check-box"></i>
					 	</span>
						Failed Transactions
					</a>
			 	</li>
			 	<li class="{{ Request::is('merchant/collection/reoccur/disputes') ? 'active' : ''}}">
					<a href="{{ url('merchant/collection/reoccur/disputes') }}">
						<span class="icon-holder">
							<i class="lni-check-box"></i>
					 	</span>
						Dispute Transactions
					</a>
			 	</li>
			 	<li class="{{ Request::is('merchant/collection/reoccur/reports') ? 'active' : ''}}">
					<a href="{{ url('merchant/collection/reoccur/reports') }}">
						<span class="icon-holder">
							<i class="lni-check-box"></i>
					 	</span>
						Transaction Report
					</a>
			 	</li>
			 	<li class="nav-item dropdown {{ Request::is('merchant/collection/reoccurbatch*') ? 'open' : ''}}">
				 	<a href="#" class="dropdown-toggle">
					 	<span class="icon-holder">
							<i class="lni-book"></i>
					 	</span>
					 	<span>Batch History</span>
					 	<span class="arrow">
							<i class="lni-chevron-right"></i>
					 	</span>
				  	</a>
				  	<ul class="dropdown-menu sub-down" style="display: {{ Request::is('merchant/collection/reoccur*') ? 'block' : 'none'}}">
					 	<li class="{{ Request::is('merchant/collection/reoccurbatch/approval-list') || Request::is('merchant/collection/reoccur/transmission/approval-list*') ? 'active' : ''}}">
							<a href="{{ url('merchant/collection/reoccurbatch/approval-list') }}">Queued For Bank</a>
					 	</li>
					 	<!-- <li class="{{ Request::is('merchant/collection/history/approved/batches') ? 'active' : ''}}">
							<a href="{{ url('merchant/collection/history/approved/batches')}}">Queued for submission</a>
					 	</li> -->
					 	<li class="{{ Request::is('merchant/collection/reoccurbatch/submitted-list') || Request::is('merchant/collection/reoccur/transmission/submitted-list*')  ? 'active' : ''}}">
							<a href="{{ url('merchant/collection/reoccurbatch/submitted-list') }}">Submitted to bank</a>
					 	</li>
					 	
					 	<li class="{{ Request::is('merchant/collection/reoccurbatch/processed-list') || Request::is('merchant/collection/reoccur/transmission/processed-list*') ? 'active' : ''}}">
							<a href="{{ url('merchant/collection/reoccurbatch/processed-list') }}">Processed Batches</a>
					 	</li>
				  	</ul>
			    </li>
			</ul>
		</li>
		@endif
 	</ul>
	
</li>
@endif

@if($firmDetails->is_avs==1)
<li class="nav-item dropdown {{ Request::is('merchant/avs*') ? 'open active' : ''}}">
	<a href="#" class="dropdown-toggle">
		<span class="icon-holder">
		  <i class="lni-wallet"></i>
		</span>
		<span class="title">AVS</span>
		<span class="arrow">
		  <i class="lni-chevron-right"></i>
		</span>
 	</a>
	 
	
	<ul class="dropdown-menu sub-down">
	@if($firmDetails->is_avs_rt==1)
		<li class="{{ (Request::is('merchant/avs/create-realtime')) ? 'active' : ''}}">
		  <a href="{{ url('merchant/avs/create-realtime') }}" class="">
			 <span class="icon-holder">
				<i class="lni-users"></i>
			 </span>
			 Real Time
		  </a>
		</li>
	@endif
	@if($firmDetails->is_avs_batch==1)
		<li class="{{ (Request::is('merchant/avs/create-batch')) ? 'active' : ''}}">
		  <a href="{{ url('merchant/avs/create-batch') }}" class="">
			 <span class="icon-holder">
				<i class="lni-users"></i>
			 </span>
			 Batch Upload
		  </a>
		</li>
	@endif
		<li class="nav-item dropdown {{ Request::is('merchant/avs/history*') ? 'open' : ''}}">
		  	<a href="#" class="dropdown-toggle">
			 	<span class="icon-holder">
					<i class="lni-wallet"></i>
			 	</span>
			 	<span class="title">History</span>
			 	<span class="arrow">
					<i class="lni-chevron-right"></i>
			 	</span>
		  	</a>
		  	<ul class="dropdown-menu sub-down" style="display:{{ Request::is('merchant/avs/history*') ? 'block' : 'none'}}">
			@if($firmDetails->is_avs_rt==1)
		  		<li class="{{ (Request::is('merchant/avs/history/realtime')) ? 'active' : ''}}">
				  <a href="{{ url('merchant/avs/history/realtime') }}" class="">
					 <span class="icon-holder">
						<i class="lni-users"></i>
					 </span>
					 Real Time
				  </a>
				</li>
			@endif
			@if($firmDetails->is_avs_batch==1)
				<li class="{{(Request::is('merchant/avs/history/batch') || Request::is('merchant/avs/history/batch*')) ? 'active' : ''}}">
				  <a href="{{ url('merchant/avs/history/batch') }}" class="">
					 <span class="icon-holder">
						<i class="lni-users"></i>
					 </span>
					 Batch
				  </a>
				</li>
			@endif
		  	</ul>
		</li>
		
	  	
	  	
	 	
	 	
	
		
 	</ul>
	
</li>

@endif
@if(isset(auth()->user()->id) && auth()->user()->role_id==3)
                
<li class="nav-item dropdown {{ Request::is('merchant/users*') ? 'open active' : ''}}">
	 <a href="#" class="dropdown-toggle">
		<span class="icon-holder">
		  <i class="lni-users"></i>
		</span>
		<span class="title">Users</span>
		<span class="arrow">
		  <i class="lni-chevron-right"></i>
		</span>
	 </a>
	 <ul class="dropdown-menu sub-down">
		<li class="{{ Request::is('merchant/users') ? 'active' : ''}}">
		  <a href="{{ url('merchant/users') }}" class="">
			 <span class="icon-holder">
				<!-- <i class="lni-cloud"></i> -->
			 </span>
			 List Users
		  </a>
		</li>
		<li class="{{ Request::is('merchant/users/create') ? 'active' : ''}}">
		  <a href="{{ url('merchant/users/create') }}" class="">
			 <span class="icon-holder">
				<!-- <i class="lni-cloud"></i> -->
			 </span>
			 Add Users
		  </a>
		</li>
	 </ul>
</li>
@endif

<li class="{{ Request::is('merchant/error-codes') ? ' active' : ''}}">
	<a href="{{ url('merchant/error-codes') }}">
  		<span class="icon-holder">
		  <i class="lni-layers"></i>
		</span>
		Error Codes
	</a>
</li>
<li>
	<div class="contactDetail alert alert-info" role="alert">
      <p>Need Help? Contact Us!</p>
      <p><i class="lni-phone"></i> 021 210 5772</p>
      
      <p><a href="mailto:operations@payportsa.co.za"><i class="lni-envelope"></i> operations@payportsa.co.za</a></p>
      
      <!-- <p><a href="mailto:support@payportsa.co.za"><i class="lni-envelope"></i> support@payportsa.co.za</a></p> -->
    </div>
</li>