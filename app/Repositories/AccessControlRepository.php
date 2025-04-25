<?php

namespace App\Repositories;

use App\Models\AccessControl;
use App\Models\AccessControlNode;
use Illuminate\Database\Eloquent\Collection;
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

    /**
     * Elimina un control de acceso y sus nodos por UUID
     * 
     * @param string $uuid
     * @return bool
     */
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

    /**
     * Crea una copia de un control de acceso existente
     * 
     * @param string $uuid
     * @return AccessControl
     */
    public function copy(string $uuid): AccessControl
    {
        $originalAccessControl = $this->findByUuidWithNodes($uuid);

        try {
            DB::beginTransaction();

            // Replicar el control de acceso
            $newAccessControl = $originalAccessControl->replicate();
            $newAccessControl->access_control_uuid = Str::uuid();
            $newAccessControl->access_control_description = $newAccessControl->access_control_description . ' (Copy)';
            $newAccessControl->save();

            // Replicar cada nodo asociado
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

    /**
     * Guarda un nuevo control de acceso
     * 
     * @param array $data
     * @return AccessControl
     */
    public function create(array $data): AccessControl
    {
        $accessControl = new AccessControl();
        $accessControl->access_control_uuid = Str::uuid()->toString();
        $accessControl->access_control_name = $data['access_control_name'];
        $accessControl->access_control_default = $data['access_control_default'];
        $accessControl->access_control_description = $data['access_control_description'];
        $accessControl->save();

        return $accessControl;
    }

    /**
     * Actualiza un control de acceso existente
     * 
     * @param string $uuid
     * @param array $data
     * @return AccessControl
     */
    public function update(string $uuid, array $data): AccessControl
    {
        $accessControl = $this->findByUuid($uuid);
        $accessControl->access_control_name = $data['access_control_name'];
        $accessControl->access_control_default = $data['access_control_default'];
        $accessControl->access_control_description = $data['access_control_description'];
        $accessControl->save();

        return $accessControl;
    }

    /**
     * Elimina todos los nodos de un control de acceso
     * 
     * @param string $uuid
     * @return bool
     */
    public function deleteAllNodes(string $uuid): bool
    {
        return $this->accessControlNode->where('access_control_uuid', $uuid)->delete();
    }

    /**
     * Crea un nuevo nodo para un control de acceso
     * 
     * @param array $nodeData
     * @return AccessControlNode
     */
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