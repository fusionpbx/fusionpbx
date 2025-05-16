<?php

namespace App\Repositories;

use App\Models\DialplanDetail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class DialplanDetailRepository
{
	protected $model;

	public function __construct(DialplanDetail $dialplanDetail)
	{
		$this->model = $dialplanDetail;
	}

	public function getAll(): Collection
	{
		return $this->model->all();
	}

    public function findByUuid(string $dialplan_detail_uuid): ?DialplanDetail
    {
        return $this->model->where('dialplan_detail_uuid', $dialplan_detail_uuid)->first();
    }

	public function create(string $dialplan_uuid, array $dialplanDetails): void
	{
		foreach ($dialplanDetails as $dialplanDetail)
		{
			$dialplanDetail['dialplan_uuid'] = $dialplan_uuid;
			$dialplanDetail['dialplan_detail_uuid'] = Str::uuid();

			$this->model->create($dialplanDetail);
		}
	}

	public function update(string $dialplan_uuid, array $dialplanDetails): void
	{
		foreach ($dialplanDetails as $dialplanDetail)
		{
			if (empty($dialplanDetail['dialplan_detail_uuid']))
			{
				$dialplanDetail['dialplan_uuid'] = $dialplan_uuid;
				$dialplanDetail['dialplan_detail_uuid'] = Str::uuid();

				$this->model->create($dialplanDetail);
			}
			else
			{
				$this->model->where('dialplan_detail_uuid', $dialplanDetail['dialplan_detail_uuid'])->update($dialplanDetail);
			}
		}
	}

	public function delete(array $dialplanDetails): bool
	{
		return $this->model->whereIn('dialplan_detail_uuid', $dialplanDetails)->delete();
	}
}
