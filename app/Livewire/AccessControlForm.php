<?php

namespace App\Livewire;

use App\Http\Requests\AccessControlRequest;
use Livewire\Component;
use App\Models\AccessControl;
use App\Models\AccessControlNode;
use Illuminate\Support\Str;

class AccessControlForm extends Component
{
    public $accessControl;
    public $accessControlUuid;
    public $accessControlName = '';
    public $accessControlDefault = '';
    public $accessControlDescription = '';
    public $nodes = [];
    public $selectedNodes = [];

    public $canEdit = false;
    public $canDelete = false;
    public $canAdd = false;
    public $canView = false;
    public $canAddNode = false;
    public $canEditNode = false;
    public $canDeleteNode = false;


    public function rules()
    {
        $request = new AccessControlRequest();
        return $request->rules();
    }

    public function messages()
    {
        $request = new AccessControlRequest();
        return $request->messages();
    }

    public function loadPermissions()
    {
        $user = auth()->user();

        $this->canEdit = $user->hasPermission('access_control_edit');
        $this->canDelete = $user->hasPermission('access_control_delete');
        $this->canAdd = $user->hasPermission('access_control_add');
        $this->canView = $user->hasPermission('access_control_view');

        $this->canAddNode = $user->hasPermission('access_control_node_add');
        $this->canEditNode = $user->hasPermission('access_control_node_edit');
        $this->canDeleteNode = $user->hasPermission('access_control_node_delete');
    }


    public function mount($accessControlUuid = null)
    {

        if ($accessControlUuid) {

            $this->accessControl = AccessControl::where('access_control_uuid', $accessControlUuid)->first();

            if ($this->accessControl) {
                $this->accessControlUuid = $this->accessControl->access_control_uuid;
                $this->accessControlName = $this->accessControl->access_control_name;
                $this->accessControlDefault = $this->accessControl->access_control_default;
                $this->accessControlDescription = $this->accessControl->access_control_description;

                $this->nodes = AccessControlNode::where('access_control_uuid', $accessControlUuid)
                    ->get()
                    ->map(function ($node) {
                        return [
                            'access_control_node_uuid' => $node->access_control_node_uuid,
                            'node_type' => $node->node_type,
                            'node_cidr' => $node->node_cidr,
                            'node_description' => $node->node_description,
                        ];
                    })
                    ->toArray();
            }
        }


        if (empty($this->nodes)) {
            $this->addNode();
        }
    }

    public function addNode()
    {
        $this->validate();

        $this->nodes[] = [
            'access_control_node_uuid' => (string) Str::uuid(),
            'node_type' => '',
            'node_cidr' => '',
            'node_description' => '',
        ];
    }

    public function removeNode($index)
    {

        unset($this->nodes[$index]);
        $this->nodes = array_values($this->nodes);
    }

    public function toggleSelectAll()
    {
        if (count($this->selectedNodes) === count($this->nodes)) {
            $this->selectedNodes = [];
        } else {
            $this->selectedNodes = array_keys($this->nodes);
        }
    }

    public function deleteSelected()
    {
        foreach ($this->selectedNodes as $index) {

            if (isset($this->nodes[$index])) {
                unset($this->nodes[$index]);
            }
        }
        $this->nodes = array_values($this->nodes);
        $this->selectedNodes = [];
    }

    public function save()
    {
        $this->validate();

        if ($this->accessControlUuid) {

            $accessControl = $this->accessControl;
            $accessControl->access_control_name = $this->accessControlName;
            $accessControl->access_control_default = $this->accessControlDefault;
            $accessControl->access_control_description = $this->accessControlDescription;
            $accessControl->save();


            AccessControlNode::where('access_control_uuid', $this->accessControlUuid)->delete();
        } else {

            $accessControl = new AccessControl();
            $accessControl->access_control_uuid = (string) Str::uuid();
            $accessControl->access_control_name = $this->accessControlName;
            $accessControl->access_control_default = $this->accessControlDefault;
            $accessControl->access_control_description = $this->accessControlDescription;
            $accessControl->save();

            $this->accessControlUuid = $accessControl->access_control_uuid;
        }


        foreach ($this->nodes as $node) {
            $accessControlNode = new AccessControlNode();
            $accessControlNode->access_control_node_uuid = $node['access_control_node_uuid'];
            $accessControlNode->access_control_uuid = $accessControl->access_control_uuid;
            $accessControlNode->node_type = $node['node_type'];
            $accessControlNode->node_cidr = $node['node_cidr'];
            $accessControlNode->node_description = $node['node_description'];
            $accessControlNode->save();
        }


        session()->flash('message', $this->accessControl ? 'Control de acceso actualizado con éxito.' : 'Control de acceso creado con éxito.');

        return redirect()->route('accesscontrol.index');
    }

    public function render()
    {
        return view('livewire.access-control-form');
    }
}
