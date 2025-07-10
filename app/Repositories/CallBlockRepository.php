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

    public function findByPayload(array $payload): ?CallBlock
    {
        $payloadQuery = CallBlock::where('domain_uuid', '=', Session::get('domain_uuid'));
        foreach ($payload as $p => $v)
        {
            $payloadQuery = $payloadQuery->where('p','=', $v);
        }
        return $payloadQuery->get();
    }

    private function setAppData(array &$data)
    {
		list($call_block_app, $call_block_data) = explode(":", $data["call_block_action"] ?? "") + [null, null];

		$data["call_block_app"] = $call_block_app;
		$data["call_block_data"] = $call_block_data;

        return $data;
    }

    private function fixCountryCode(array &$data)
    {
        if (($data['call_block_country_code'] == 0) || (strlen($data['call_block_country_code']) == 0))
        {
            // Let's try to fix the Country
            if (preg_match('/1(\d{10})/', $data['call_block_number'], $matches))
            {
                $data['call_block_country_code'] = 1;
                $data['call_block_number'] = $matches[1];
            }
        }
        return $data;
    }

    public function create(array $data): CallBlock
    {
		$this->setAppData($data);
        $this->fixCountryCode($data);

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
