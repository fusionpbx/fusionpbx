<?php

namespace App\Repositories;

use App\Models\BillingDeal;
use Illuminate\Database\Eloquent\Collection;

class BillingDealRepository
{
    protected $model;

    public function __construct(BillingDeal $billingDeal)
    {
        $this->model = $billingDeal;
    }

    public function getAll(string $domainUuid): Collection
    {
        return $this->model->where('domain_uuid', $domainUuid)->get();
    }

    public function findByUuid(string $uuid): ?BillingDeal
    {
        return $this->model->where('billing_deal_uuid', $uuid)->first();
    }

    public function create(array $data): BillingDeal
    {
        return $this->model->create($data);
    }

    public function update(BillingDeal $billingDeal, array $data): bool
    {
        return $billingDeal->update($data);
    }

    public function delete(BillingDeal $billingDeal): ?bool
    {
        return $billingDeal->delete();
    }
}
