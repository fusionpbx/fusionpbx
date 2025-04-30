<?php

namespace App\Repositories;

use App\Models\Stream;
use Illuminate\Database\Eloquent\Collection;

class StreamRepository
{
    protected $model;

    public function __construct(Stream $stream)
    {
        $this->model = $stream;
    }

    public function getAll(string $domainUuid): Collection
    {
        return $this->model->where('domain_uuid', $domainUuid)->get();
    }

    public function findByUuid(string $uuid): ?Stream
    {
        return $this->model->where('stream_uuid', $uuid)->first();
    }

    public function create(array $data): Stream
    {
        return $this->model->create($data);
    }

    public function update(Stream $stream, array $data): bool
    {
        return $stream->update($data);
    }

    public function delete(Stream $stream): ?bool
    {
        return $stream->delete();
    }
}
