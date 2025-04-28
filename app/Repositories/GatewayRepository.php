<?php

namespace App\Repositories;

use App\Models\Gateway;
use App\Models\Domain;
use App\Models\SipProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GatewayRepository
{
    protected $model;
    
    public function __construct()
    {
        $this->model = new Gateway();
    }
    

    public function getAll(): Collection
    {
        return $this->model->all();
    }
    

    public function findByUuid(string $uuid): ?Gateway
    {
        return $this->model->where('gateway_uuid', $uuid)->first();
    }
    
    public function create(array $data): Gateway
    {
        if(Auth::check() && !isset($data['insert_user'])) {
            $data['insert_user'] = Auth::user()->user_uuid;
        }

        return $this->model->create($data);
    }
    

    public function update(Gateway $gateway, array $data): bool
    {
        if(Auth::check() && !isset($data['update_user'])) {
            $data['update_user'] = Auth::user()->user_uuid;
        }
        return $gateway->update($data);
    }
    
    public function delete(Gateway $gateway): ?bool
    {
        return $gateway->delete();
    }
    
    public function copy(Gateway $gateway): Gateway
    {
        $newGateway = $gateway->replicate();
        $newGateway->gateway_uuid = Str::uuid();
        $newGateway->description = $newGateway->description . ' (Copy)';
        $newGateway->save();
        
        return $newGateway;
    }
    

    public function getAllDomains(): Collection
    {
        return Domain::all();
    }
    

    public function getAllSipProfiles(): Collection
    {
        return SipProfile::all();
    }
}