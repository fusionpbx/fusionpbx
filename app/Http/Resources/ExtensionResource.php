<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExtensionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'extension_uuid' => $this->extension_uuid,
            'domain_uuid' => $this->domain_uuid,
            'extension' => $this->extension,
            'password' => $this->password,
            'description' => $this->description,
            'enabled' => boolval($this->enabled),
        ];
    }
}
