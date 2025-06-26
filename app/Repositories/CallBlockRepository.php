<?php

namespace App\Repositories;

use App\Models\CallBlock;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Session;

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

    private function setAppData(array &$data)
    {
		list($call_block_app, $call_block_data) = explode(":", $data["call_block_action"]);

		$data["call_block_app"] = $call_block_app;
		$data["call_block_data"] = $call_block_data;
    }

    public function create(array $data): CallBlock
    {
		$this->setAppData($data);

        $data["domain_uuid"] = Session::get("domain_uuid");

        return $this->model->create($data);
    }

    public function update(CallBlock $callBlock, array $data): bool
    {
        $this->setAppData($data);

        return $callBlock->update($data);
    }

    public function delete(CallBlock $callBlock): ?bool
    {
        return $callBlock->delete();
    }
}
