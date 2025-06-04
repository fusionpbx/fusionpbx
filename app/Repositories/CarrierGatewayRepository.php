<?php

namespace App\Repositories;

use App\Models\Carrier;
use App\Models\CarrierGateway;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class CarrierGatewayRepository
{
	protected $model;

	public function __construct(CarrierGateway $carrierGateway)
	{
		$this->model = $carrierGateway;
	}

	public function getAll(): Collection
	{
		return $this->model->all();
	}

    public function findByUuid(string $carrier_gateway_uuid): ?CarrierGateway
    {
        return $this->model->where('carrier_gateway_uuid', $carrier_gateway_uuid)->first();
    }

	public function create(Carrier $carrier, array $carrierGateways): void
	{
		foreach ($carrierGateways as $carrierGateway)
		{
			$carrierGateway['carrier_gateway_uuid'] = Str::uuid();
			$carrierGateway['carrier_uuid'] = $carrier->carrier_uuid;

			$this->model->create($carrierGateway);
		}
	}

	public function update(Carrier $carrier, array $carrierGateways): void
	{
		foreach ($carrierGateways as $carrierGateway)
		{
			if (empty($carrierGateway['carrier_gateway_uuid']))
			{
				$carrierGateway['carrier_gateway_uuid'] = Str::uuid();
				$carrierGateway['carrier_uuid'] = $carrier->carrier_uuid;

				$this->model->create($carrierGateway);
			}
			else
			{
				$this->model->where('carrier_gateway_uuid', $carrierGateway['carrier_gateway_uuid'])->update($carrierGateway);
			}
		}
	}

	public function delete(array $carrierGateways): bool
	{
		return $this->model->whereIn('carrier_gateway_uuid', $carrierGateways)->delete();
	}
}
