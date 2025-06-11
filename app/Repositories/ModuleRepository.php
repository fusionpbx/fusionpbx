<?php

namespace App\Repositories;

use App\Models\Module;
use Illuminate\Database\Eloquent\Collection;

class ModuleRepository
{
    protected $model;

    public function __construct(Module $module)
    {
        $this->model = $module;
    }

    public function getAll(): Collection
    {
        return $this->model->all();
    }

    public function findByUuid(string $uuid): ?Module
    {
        return $this->model->where('module_uuid', $uuid)->first();
    }

    public function create(array $data): Module
    {
        return $this->model->create($data);
    }

    public function update(Module $module, array $data): bool
    {
        return $module->update($data);
    }

    public function delete(Module $module): ?bool
    {
        return $module->delete();
    }
}
