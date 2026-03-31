<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\CustomerService;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    protected $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    /**
     * Display a listing of customers
     */
    public function index(Request $request)
    {
        $sortable = ['name', 'mobile', 'status', 'created_at'];
        $sort = in_array($request->sort, $sortable) ? $request->sort : 'created_at';
        $dir  = $request->dir === 'asc' ? 'asc' : 'desc';

        $filters = [
            'status'   => $request->get('status'),
            'search'   => $request->get('search'),
            'per_page' => 15,
            'sort'     => $sort,
            'dir'      => $dir,
        ];

        if ($request->export === 'csv') {
            $all = $this->customerService->getAllCustomers(array_merge($filters, ['per_page' => 99999]));
            return $this->exportCsv($all->items());
        }

        $customers = $this->customerService->getAllCustomers($filters);
        return view('admin.customers.index', compact('customers', 'sort', 'dir'));
    }

    private function exportCsv($customers)
    {
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="customers.csv"'];
        $callback = function () use ($customers) {
            $f = fopen('php://output', 'w');
            fputcsv($f, ['ID', 'Name', 'Mobile', 'Email', 'Address', 'Status', 'Created At']);
            foreach ($customers as $c) {
                fputcsv($f, [$c->id, $c->name, $c->mobile, $c->email, $c->address, $c->status, $c->created_at->toDateTimeString()]);
            }
            fclose($f);
        };
        return response()->stream($callback, 200, $headers);
    }

    /**
     * Show the form for creating a new customer
     */
    public function create()
    {
        return view('admin.customers.create');
    }

    /**
     * Store a newly created customer
     */
    public function store(StoreCustomerRequest $request)
    {
        try {
            $customer = $this->customerService->createCustomer($request->validated());

            return redirect()->route('admin.customers.index')
                ->with('success', 'Customer created successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to create customer: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified customer
     */
    public function show(Customer $customer)
    {
        $customer->load('directBookings');
        return view('admin.customers.show', compact('customer'));
    }

    /**
     * Show the form for editing the customer
     */
    public function edit(Customer $customer)
    {
        return view('admin.customers.edit', compact('customer'));
    }

    /**
     * Update the customer
     */
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        try {
            $customer = $this->customerService->updateCustomer($customer, $request->validated());

            return redirect()->route('admin.customers.index')
                ->with('success', 'Customer updated successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to update customer: ' . $e->getMessage());
        }
    }

    /**
     * Soft delete (deactivate) the customer
     */
    public function destroy(Customer $customer)
    {
        try {
            $this->customerService->deleteCustomer($customer);

            return redirect()->route('admin.customers.index')
                ->with('success', 'Customer deactivated successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to deactivate customer: ' . $e->getMessage());
        }
    }
}
