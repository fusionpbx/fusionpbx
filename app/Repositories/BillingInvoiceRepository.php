<?php

namespace App\Repositories;

use App\Models\BillingInvoice;
use Illuminate\Database\Eloquent\Collection;

class BillingInvoiceRepository
{
    protected $model;

    public function __construct(BillingInvoice $billingInvoice)
    {
        $this->model = $billingInvoice;
    }

    public function getAll(string $domainUuid): Collection
    {
        return $this->model->where('domain_uuid', $domainUuid)->get();
    }

    public function findByUuid(string $uuid): ?BillingInvoice
    {
        return $this->model->where('billing_invoice_uuid', $uuid)->first();
    }

    public function create(array $data): BillingInvoice
    {
        return $this->model->create($data);
    }

    public function update(BillingInvoice $billingInvoice, array $data): bool
    {
        return $billingInvoice->update($data);
    }

    public function delete(BillingInvoice $billingInvoice): ?bool
    {
        return $billingInvoice->delete();
    }
}
