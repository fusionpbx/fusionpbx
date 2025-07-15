<?php

namespace App\Repositories;

use App\Models\Billing;
use Illuminate\Database\Eloquent\Collection;

class BillingRepository
{
    protected $model;

    public function __construct(Billing $billing)
    {
        $this->model = $billing;
    }

    public function getAll(string $domainUuid): Collection
    {
        return $this->model->where('domain_uuid', $domainUuid)->get();
    }

    public function findByUuid(string $uuid): ?Billing
    {
        return $this->model->where('billing_uuid', $uuid)->first();
    }

    public function create(array $data): Billing
    {
        return $this->model->create($data);
    }

    public function update(Billing $billing, array $data): bool
    {
        return $billing->update($data);
    }

    public function delete(Billing $billing): ?bool
    {
        return $billing->delete();
    }
}
