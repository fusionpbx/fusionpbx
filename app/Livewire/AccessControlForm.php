<?php
namespace App\Livewire;

use App\Http\Requests\AccessControlRequest;
use App\Repositories\AccessControlRepository;
use Livewire\Component;
use App\Models\AccessControl;
use App\Models\AccessControlNode;
use App\Rules\ValidCidr;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

class AccessControlForm extends Component
{
    public $accessControl;
    public $accessControlUuid;
    public string $accessControlName = '';
    public string $accessControlDefault = '';
    public ?string $accessControlDescription = '';
    public array $nodes = [];
    public array $selectedNodes = [];

    public bool $canEdit = false;
    public bool $canDelete = false;
    public bool $canAdd = false;
    public bool $canView = false;
    public bool $canAddNode = false;
    public bool $canEditNode = false;
    public bool $canDeleteNode = false;

    protected $accessControlRepository;

    public function boot(AccessControlRepository $accessControlRepository)
    {
        $this->accessControlRepository = $accessControlRepository;
    }

    public function rules() : array
    {
        $request = new AccessControlRequest();
        return $request->rules();
    }

    public function messages() : array
    {
        $request = new AccessControlRequest();
        return $request->messages();
    }

    public function loadPermissions() : void
    {
        $user = auth()->user();

        $this->canAddNode = $user->hasPermission('access_control_node_add');
        $this->canEditNode = $user->hasPermission('access_control_node_edit');
        $this->canDeleteNode = $user->hasPermission('access_control_node_delete');
    }

    public function mount($accessControlUuid = null) : void
    {
        if ($accessControlUuid) {
            $this->accessControl = $this->accessControlRepository->findByUuid($accessControlUuid);

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

    public function addNode() : void
    {
        $this->validate(
            [
                'nodes.*.node_type' => 'required|in:allow,deny',
                'nodes.*.node_cidr' => ['required', 'string', 'max:255', new ValidCidr()],
                'nodes.*.node_description' => 'nullable|string|max:255',
            ]
        );

        $this->nodes[] = [
            'access_control_node_uuid' => Str::uuid()->toString(),
            'node_type' => '',
            'node_cidr' => '',
            'node_description' => '',
        ];
    }

    public function removeNode($index) : void
    {
        unset($this->nodes[$index]);
        $this->nodes = array_values($this->nodes);
    }

    public function toggleSelectAll() : void
    {
        if (count($this->selectedNodes) === count($this->nodes)) {
            $this->selectedNodes = [];
        } else {
            $this->selectedNodes = array_keys($this->nodes);
        }
    }

    public function deleteSelected() : void
    {
        foreach ($this->selectedNodes as $index) {
            if (isset($this->nodes[$index])) {
                unset($this->nodes[$index]);
            }
        }
        $this->nodes = array_values($this->nodes);
        $this->selectedNodes = [];
    }

    public function save() : void
    {
        $this->validate();

        $formData = [
            'access_control_name' => $this->accessControlName,
            'access_control_default' => $this->accessControlDefault,
            'access_control_description' => $this->accessControlDescription,
        ];

        if ($this->accessControlUuid) {
            $accessControl = $this->accessControlRepository->update($this->accessControlUuid, $formData);
            $this->accessControlRepository->deleteAllNodes($this->accessControlUuid);
        } else {
            $accessControl = $this->accessControlRepository->create($formData);
        }

        foreach ($this->nodes as $node) {
            $nodeData = [
                'access_control_node_uuid' => $node['access_control_node_uuid'],
                'access_control_uuid' => $accessControl->access_control_uuid,
                'node_type' => $node['node_type'],
                'node_cidr' => $node['node_cidr'],
                'node_description' => $node['node_description'],
            ];
            
            $this->accessControlRepository->createNode($nodeData);
        }

        redirect()->route('accesscontrol.index');
    }

    public function render() : View
    {
        return view('livewire.access-control-form');
    }
}