<?php

namespace App\Repositories;

use App\Models\AccessControl;
use App\Models\AccessControlNode;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccessControlRepository
{

    protected $accessControl;
    protected $accessControlNode;

    public function __construct(AccessControl $accessControl, AccessControlNode $accessControlNode)
    {
        $this->accessControl = $accessControl;
        $this->accessControlNode = $accessControlNode;
    }


    public function getAll(): Collection
    {
        return $this->accessControl->all();
    }


    public function findByUuid(string $uuid)
    {
        return $this->accessControl->where('access_control_uuid', $uuid)->first();
    }


    public function findByUuidWithNodes(string $uuid)
    {
        return $this->accessControl->with('accesscontrolnodes')->where('access_control_uuid', $uuid)->firstOrFail();
    }

    public function delete(string $uuid): bool
    {
        try {
            DB::beginTransaction();

            $this->accessControlNode->where('access_control_uuid', $uuid)->delete();

            $this->accessControl->where('access_control_uuid', $uuid)->delete();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function copy(string $uuid): AccessControl
    {
        $originalAccessControl = $this->findByUuidWithNodes($uuid);

        try {
            DB::beginTransaction();

           
            $newAccessControl = $originalAccessControl->replicate();
            $newAccessControl->access_control_uuid = Str::uuid();
            $newAccessControl->access_control_description = $newAccessControl->access_control_description . ' (Copy)';
            $newAccessControl->save();

            
            foreach ($originalAccessControl->accesscontrolnodes as $node) {
                $newNode = $node->replicate();
                $newNode->access_control_node_uuid = Str::uuid();
                $newNode->access_control_uuid = $newAccessControl->access_control_uuid;
                $newNode->save();
            }

            DB::commit();
            return $newAccessControl;
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }


    public function create(array $data): AccessControl
    {
        if(Auth::check() && !isset($data['insert_user'])) {
            $data['insert_user'] = Auth::user()->user_uuid;
        }

        $accessControl = new AccessControl();
        $accessControl->access_control_uuid = Str::uuid()->toString();
        $accessControl->access_control_name = $data['access_control_name'];
        $accessControl->access_control_default = $data['access_control_default'];
        $accessControl->access_control_description = $data['access_control_description'];
        $accessControl->insert_user = $data['insert_user'] ?? null;

        $accessControl->save();
        
        return $accessControl;
    }


    public function update(string $uuid, array $data): AccessControl
    {
        if(Auth::check() && !isset($data['update_user'])) {
            $data['update_user'] = Auth::user()->user_uuid;
        }

        $accessControl = $this->findByUuid($uuid);
        $accessControl->access_control_name = $data['access_control_name'];
        $accessControl->access_control_default = $data['access_control_default'];
        $accessControl->access_control_description = $data['access_control_description'];
        $accessControl->update_user = $data['update_user'] ?? null;
        $accessControl->save();

        return $accessControl;
    }


    public function deleteAllNodes(string $uuid): bool
    {
        return $this->accessControlNode->where('access_control_uuid', $uuid)->delete();
    }

    public function createNode(array $nodeData): AccessControlNode
    {
        $accessControlNode = new AccessControlNode();
        $accessControlNode->access_control_node_uuid = $nodeData['access_control_node_uuid'];
        $accessControlNode->access_control_uuid = $nodeData['access_control_uuid'];
        $accessControlNode->node_type = $nodeData['node_type'];
        $accessControlNode->node_cidr = $nodeData['node_cidr'];
        $accessControlNode->node_description = $nodeData['node_description'];
        $accessControlNode->save();

        return $accessControlNode;
    }
}