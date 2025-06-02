<?php

namespace App\Repositories;

use App\Models\Dialplan;
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

	public function create(Dialplan $dialplan, array $dialplanDetails): void
	{
		foreach ($dialplanDetails as $dialplanDetail)
		{
			$dialplanDetail['domain_uuid'] = $dialplan->domain_uuid;
			$dialplanDetail['dialplan_uuid'] = $dialplan->dialplan_uuid;
			$dialplanDetail['dialplan_detail_uuid'] = Str::uuid();

			$this->model->create($dialplanDetail);
		}
	}

	public function update(Dialplan $dialplan, array $dialplanDetails): void
	{
		foreach ($dialplanDetails as $dialplanDetail)
		{
			if (empty($dialplanDetail['dialplan_detail_uuid']))
			{
				$dialplanDetail['domain_uuid'] = $dialplan->domain_uuid;
				$dialplanDetail['dialplan_uuid'] = $dialplan->dialplan_uuid;
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
