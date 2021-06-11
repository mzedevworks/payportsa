<table id="datatable" class="table table-bordered">
    <thead>
        <tr>
           
            <th>Mandate Id</th>
            <th>Customer</th>
            <th>Amount</th>
            <th>Payment Date</th>
            
        </tr>
    </thead>
    <tbody>
        @foreach($transactions as $key => $eachTransaction)
            <tr>
                
                
                <td>
                    {{$eachTransaction->customer->mandate_id}}
                </td>
                <td>
                    {{$eachTransaction->customer->first_name}} {{$eachTransaction->customer->last_name}} 
                </td>
                <td>
                    R {{$eachTransaction->amount}}
                </td>
                <td>
                    {{Helper::convertDate($eachTransaction->payment_date)}}
                </td>
                
                
            </tr>
        @endforeach
        
    </tbody>
</table>