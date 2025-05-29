<?php

namespace App\Repositories;

use App\Models\Lcr;
use Illuminate\Database\Eloquent\Collection;

class LcrRepository
{
    protected $model;

    public function __construct(Lcr $lcr)
    {
        $this->model = $lcr;
    }

    public function getAll(): Collection
    {
        return $this->model->all();
    }

    public function findByUuid(string $uuid): ?Lcr
    {
        return $this->model->where('lcr_uuid', $uuid)->first();
    }

    public function create(array $data): Lcr
    {
        return $this->model->create($data);
    }

    public function update(Lcr $lcr, array $data): bool
    {
        return $lcr->update($data);
    }

    public function delete(Lcr $lcr): ?bool
    {
        return $lcr->delete();
    }
}
