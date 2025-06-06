<?php

namespace App\Repositories;

use App\Models\Phrase;
use App\Models\PhraseDetail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class PhraseDetailRepository
{
	protected $model;

	public function __construct(PhraseDetail $phraseDetail)
	{
		$this->model = $phraseDetail;
	}

	public function getAll(): Collection
	{
		return $this->model->all();
	}

    public function findByUuid(string $phrase_detail_uuid): ?PhraseDetail
    {
        return $this->model->where('phrase_detail_uuid', $phrase_detail_uuid)->first();
    }

	public function create(Phrase $phrase, array $phraseDetails): void
	{
		foreach ($phraseDetails as $phraseDetail)
		{
			$phraseDetail['domain_uuid'] = $phrase->domain_uuid;
			$phraseDetail['phrase_uuid'] = $phrase->phrase_uuid;
			$phraseDetail['phrase_detail_uuid'] = Str::uuid();

			$this->model->create($phraseDetail);
		}
	}

	public function update(Phrase $phrase, array $phraseDetails): void
	{
		foreach ($phraseDetails as $phraseDetail)
		{
			if (empty($phraseDetail['phrase_detail_uuid']))
			{
				$phraseDetail['domain_uuid'] = $phrase->domain_uuid;
				$phraseDetail['phrase_uuid'] = $phrase->phrase_uuid;
				$phraseDetail['phrase_detail_uuid'] = Str::uuid();

				$this->model->create($phraseDetail);
			}
			else
			{
				$this->model->where('phrase_detail_uuid', $phraseDetail['phrase_detail_uuid'])->update($phraseDetail);
			}
		}
	}

	public function delete(array $phraseDetails): bool
	{
		return $this->model->whereIn('phrase_detail_uuid', $phraseDetails)->delete();
	}
}
