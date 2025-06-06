<?php

namespace App\Repositories;

use App\Models\Phrase;
use Illuminate\Database\Eloquent\Collection;

class PhraseRepository
{
    protected $model;

    public function __construct(Phrase $phrase)
    {
        $this->model = $phrase;
    }

    public function getAll(): Collection
    {
        return $this->model->all();
    }

    public function findByUuid(string $uuid): ?Phrase
    {
        return $this->model->where('phrase_uuid', $uuid)->first();
    }

    public function create(array $data): Phrase
    {
        return $this->model->create($data);
    }

    public function update(Phrase $phrase, array $data): bool
    {
        return $phrase->update($data);
    }

    public function delete(Phrase $phrase): ?bool
    {
        return $phrase->delete();
    }
}
