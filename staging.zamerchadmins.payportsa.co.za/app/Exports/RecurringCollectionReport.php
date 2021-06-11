<?php
namespace App\Exports;

use App\User;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;

use App\Model\{Firm,BankDetails,Role,Employees,Customer,Batch,Collections};

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class RecurringCollectionReport implements FromView
{
    use Exportable;

    public function __construct($bindings,$whereConditions)
    {
        $this->bindings = $bindings;
        $this->whereConditions = $whereConditions;
    }


    public function query()
    {
        return Collections::query();
    }

    public function view(): View
    {
      

        $whereConditions ="customers.firm_id=?";


        $transactions=Collections::whereRaw($this->whereConditions, $this->bindings)
                ->leftJoin('customers', function ($join) {
                    $join->on('collections.customer_id', '=', 'customers.id');
                })->orderBy('collections.id', 'desc')->get();


        
        return view('exports.RecurringCollectionReport', [
            'transactions' => $transactions
        ]);
    }
}
