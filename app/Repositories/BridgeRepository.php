<?php

namespace App\Repositories;

use App\Models\Bridge;
use Illuminate\Database\Eloquent\Collection;

class BridgeRepository
{
    protected $model;    

    public function __construct(Bridge $bridge)
    {
        $this->model = $bridge;
    }
    

    public function getAll(string $domainUuid): Collection
    {
        return $this->model->where('domain_uuid', $domainUuid)->get();
    }
    

    public function findByUuid(string $uuid): ?Bridge
    {
        return $this->model->where('bridge_uuid', $uuid)->first();
    }
    

    public function create(array $data): Bridge
    {
        return $this->model->create($data);
    }
    
    public function update(Bridge $bridge, array $data): bool
    {
        return $bridge->update($data);
    }
    
    public function delete(Bridge $bridge): ?bool
    {
        return $bridge->delete();
    }
}