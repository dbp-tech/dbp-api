<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\CustomersImport;
use App\Models\Customer;
use App\Repositories\CustomerRepository;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CustomerController extends Controller {
    protected $customerRepo;

    public function __construct()
    {
        $this->customerRepo = new CustomerRepository();
    }

    public function createBulk(Request $request) {
        try {
            $data = Excel::toArray(new CustomersImport(), $request->file('file'));
            $customerInsert = [];
            foreach ($data[0] as $key => $datum) {
                if ($key > 0) {
                    $customerInsert[] = [
                        'company_id' => $request->header('company_id'),
                        'uuid' => null,
                        'name' => $datum['0'],
                        'email' => $datum['1'],
                        'phone' => $datum['2'],
                        'createdAt' => date("Y-m-d H:i:s"),
                        'updatedAt' => date("Y-m-d H:i:s")
                    ];
                }
            }

            Customer::insert($customerInsert);

            return resultFunction("", true);
        } catch (\Exception $e) {
            return resultFunction("Err code CC-CB catch: " . $e->getMessage());
        }
    }

    public function delete($id) {
        return $this->customerRepo->delete($id);
    }

    public function update(Request $request) {
        return $this->customerRepo->update($request->all());
    }

    public function assignRecipe(Request $request) {
        return $this->customerRepo->assignRecipe($request->all());
    }

    public function unassignRecipe(Request $request) {
        return $this->customerRepo->unassignRecipe($request->all());
    }

    public function detail($id) {
        return $this->customerRepo->detail($id);
    }

    public function index(Request $request) {
        $customers = Customer::with(['company'])
            ->where('company_id', $request->header('company_id'));

        $customers = $customers->paginate(50);
        return $customers;
    }
}