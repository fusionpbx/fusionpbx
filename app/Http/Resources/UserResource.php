<?php

namespace App\Http\Resources;

use App\Http\Requests\UserRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'username' => $this->username,
            'user_email' => $this->user_email,
            'user_status' => $this->user_status,
            'api_key' => $this->api_key,
        ];
    }
}
