<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    /**
     * Get all customers with optional filters
     */
    public function getAllCustomers($filters = [])
    {
        $query = Customer::query();

        // Filter by status
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Search by name or mobile
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Create a new customer
     */
    public function createCustomer(array $data)
    {
        try {
            DB::beginTransaction();

            $customer = Customer::create($data);

            // Log audit
            app(AuditLogService::class)->log(
                'CUSTOMER_CREATED',
                'Customer',
                $customer->id,
                "Customer {$customer->name} created"
            );

            DB::commit();
            return $customer;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update existing customer
     */
    public function updateCustomer(Customer $customer, array $data)
    {
        try {
            DB::beginTransaction();

            $customer->update($data);

            // Log audit
            app(AuditLogService::class)->log(
                'CUSTOMER_UPDATED',
                'Customer',
                $customer->id,
                "Customer {$customer->name} updated"
            );

            DB::commit();
            return $customer;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Soft deactivate customer
     */
    public function deleteCustomer(Customer $customer)
    {
        try {
            DB::beginTransaction();

            $customer->update(['status' => 'inactive']);

            // Log audit
            app(AuditLogService::class)->log(
                'CUSTOMER_DEACTIVATED',
                'Customer',
                $customer->id,
                "Customer {$customer->name} deactivated"
            );

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Find customer by mobile number
     */
    public function findByMobile(string $mobile)
    {
        return Customer::where('mobile', $mobile)->first();
    }

    /**
     * Get active customers for dropdown
     */
    public function getActiveCustomersForDropdown()
    {
        return Customer::active()
            ->select('id', 'name', 'mobile')
            ->orderBy('name')
            ->get();
    }
}
