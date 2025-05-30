<?php

namespace App\Repositories;

use App\Models\Carrier;
use Illuminate\Database\Eloquent\Collection;

class CarrierRepository
{
    protected $model;

    public function __construct(Carrier $carrier)
    {
        $this->model = $carrier;
    }

    public function getAll(): Collection
    {
        return $this->model->all();
    }

    public function findByUuid(string $uuid): ?Carrier
    {
        return $this->model->where('carrier_uuid', $uuid)->first();
    }

    public function create(array $data): Carrier
    {
        return $this->model->create($data);
    }

    public function update(Carrier $carrier, array $data): bool
    {
        return $carrier->update($data);
    }

    public function delete(Carrier $carrier): ?bool
    {
        return $carrier->delete();
    }
}
