<?php

namespace App\Repositories;

use App\Models\CallBlock;
use Illuminate\Database\Eloquent\Collection;

class CallBlockRepository
{
    protected $model;

    public function __construct(CallBlock $callBlock)
    {
        $this->model = $callBlock;
    }

    public function getAll(): Collection
    {
        return $this->model->all();
    }

    public function findByUuid(string $uuid): ?CallBlock
    {
        return $this->model->where('call_block_uuid', $uuid)->first();
    }

    public function create(array $data): CallBlock
    {
        return $this->model->create($data);
    }

    public function update(CallBlock $callBlock, array $data): bool
    {
        return $callBlock->update($data);
    }

    public function delete(CallBlock $callBlock): ?bool
    {
        return $callBlock->delete();
    }
}
