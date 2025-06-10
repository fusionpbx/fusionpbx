<?php

namespace App\Livewire;

use App\Http\Requests\DeviceVendorRequest;
use App\Repositories\DeviceVendorRepository;
use App\Models\DeviceVendor;
use App\Models\Group;
use Livewire\Component;
use Illuminate\Support\Str;
use Exception;

class DeviceVendorForm extends Component
{
    public ?string $vendorUuid;
    public string $vendorName = '';
    public bool $vendorEnabled = true;
    public string $vendorDescription = '';

    public array $functions = [];
    public array $availableGroups = [];
    public string $functionType = '';

    public bool $isEdit = false;
    public bool $showFunctionForm = false;
    public ?int $editingFunctionIndex = null;

    public $tempFunction = [
        'type' => '',
        'subtype' => '',
        'value' => '',
        'enabled' => true,
        'description' => '',
        'groups' => []
    ];

    protected $repository;

    public function boot(DeviceVendorRepository $repository)
    {
        $this->repository = $repository;
    }

    public function rules()
    {
        $request = new DeviceVendorRequest();
        $request->setVendorUuid($this->vendorUuid);
        return $request->rules();
    }

    public function mount($vendorUuid = null)
    {
        $this->availableGroups = Group::select('group_uuid', 'group_name', 'domain_uuid')
            ->orderBy('domain_uuid', 'desc')
            ->orderBy('group_name', 'asc')
            ->get()
            ->toArray();

        if ($vendorUuid) {
            $this->loadVendor($vendorUuid);
        } else {
            $this->resetForm();
        }
    }

    public function loadVendor($vendorUuid)
    {
        try {
            $vendor = $this->repository->findVendorByUuid($vendorUuid);

            if (!$vendor) {
                session()->flash('error', 'Vendor no encontrado.');
                return redirect()->route('devices_vendors.index');
            }

            $this->isEdit = true;
            $this->vendorUuid = $vendor->device_vendor_uuid;
            $this->vendorName = $vendor->name;
            $this->vendorEnabled = $vendor->enabled === 'true';
            $this->vendorDescription = $vendor->description ?? '';

            $this->loadVendorFunctions();
        } catch (Exception $e) {
            throw $e;
            session()->flash('error', 'Error:' . $e->getMessage());
        }
    }

    public function loadVendorFunctions()
    {
        if (!$this->vendorUuid) return;

        try {
            $functions = $this->repository->getVendorFunctions($this->vendorUuid);
            $this->functions = [];

            foreach ($functions as $function) {
                $groups = $this->repository->getFunctionGroups(
                    $function['device_vendor_function_uuid'],
                    $this->vendorUuid
                );

                $this->functions[] = [
                    'uuid' => $function['device_vendor_function_uuid'],
                    'type' => $function['type'],
                    'subtype' => $function['subtype'],
                    'value' => $function['value'],
                    'enabled' => $function['enabled'] === 'true',
                    'description' => $function['description'] ?? '',
                    'groups' => $groups,
                    'selected_groups' => array_column($groups, 'group_uuid')
                ];
            }
        } catch (Exception $e) {
            throw $e;
            session()->flash('error', 'Error : ' . $e->getMessage());
        }
    }

    public function save()
    {
        $this->validate();

        try {
            if ($this->isEdit) {
                $this->updateVendor();
            } else {
                $this->createVendor();
            }

            session()->flash('success', $this->isEdit ? 'Vendor updated successfully.' : 'Vendor created successfully.');

            return redirect()->route('devices_vendors.index');
        } catch (Exception $e) {
            throw $e;
            session()->flash('error', 'Error: ' . $e->getMessage());
        }
    }

    private function createVendor()
    {
        $vendorData = [
            'name' => $this->vendorName,
            'enabled' => $this->vendorEnabled ? 'true' : 'false',
            'description' => $this->vendorDescription,
        ];

        $vendor = $this->repository->createVendor($vendorData);
        $this->vendorUuid = $vendor->device_vendor_uuid;

        $this->saveFunctions();
    }

    private function updateVendor()
    {
        $vendorData = [
            'name' => $this->vendorName,
            'enabled' => $this->vendorEnabled ? 'true' : 'false',
            'description' => $this->vendorDescription,
        ];

        $this->repository->updateVendor($this->vendorUuid, $vendorData);

        $this->saveFunctions();
    }

    private function saveFunctions()
    {
        $existingFunctions = $this->repository->getVendorFunctions($this->vendorUuid);
        $existingUuids = array_column($existingFunctions, 'device_vendor_function_uuid');

        $processedUuids = [];

        foreach ($this->functions as $function) {
            $functionData = [
                'type' => $function['type'],
                'subtype' => $function['subtype'],
                'value' => $function['value'],
                'enabled' => $function['enabled'] ? 'true' : 'false',
                'description' => $function['description'],
            ];

            $groupData = [];
            if (isset($function['selected_groups']) && is_array($function['selected_groups'])) {
                foreach ($function['selected_groups'] as $groupUuid) {
                    $group = collect($this->availableGroups)->firstWhere('group_uuid', $groupUuid);
                    if ($group) {
                        $groupData[] = [
                            'device_vendor_function_group_uuid' => Str::uuid(),
                            'group_uuid' => $group['group_uuid'],
                            'group_name' => $group['group_name']
                        ];
                    }
                }
            }

            if (isset($function['uuid']) && in_array($function['uuid'], $existingUuids)) {
                $this->repository->updateVendorFunction($function['uuid'], $functionData, $groupData);
                $processedUuids[] = $function['uuid'];
            } else {
                $newFunction = $this->repository->createVendorFunction($this->vendorUuid, $functionData, $groupData);
                $processedUuids[] = $newFunction->device_vendor_function_uuid;
            }
        }

        $functionsToDelete = array_diff($existingUuids, $processedUuids);
        foreach ($functionsToDelete as $functionUuid) {
            $this->repository->deleteVendorFunction($functionUuid);
        }
    }

    public function addFunction()
    {
        $this->resetTempFunction();
        $this->showFunctionForm = true;
        $this->editingFunctionIndex = null;
    }

    public function editFunction($index)
    {
        $function = $this->functions[$index];
        $this->tempFunction = [
            'type' => $function['type'],
            'subtype' => $function['subtype'],
            'value' => $function['value'],
            'enabled' => $function['enabled'],
            'description' => $function['description'],
            'groups' => $function['selected_groups'] ?? []
        ];

        $this->showFunctionForm = true;
        $this->editingFunctionIndex = $index;
    }

    public function saveFunction()
    {
        $this->validate([
            'tempFunction.type' => 'required|string',
            'tempFunction.subtype' => 'nullable|string',
            'tempFunction.value' => 'required|string',
        ]);

        $functionData = [
            'type' => $this->tempFunction['type'],
            'subtype' => $this->tempFunction['subtype'],
            'value' => $this->tempFunction['value'],
            'enabled' => $this->tempFunction['enabled'],
            'description' => $this->tempFunction['description'],
            'selected_groups' => $this->tempFunction['groups']
        ];

        if ($this->editingFunctionIndex !== null) {
            $functionData['uuid'] = $this->functions[$this->editingFunctionIndex]['uuid'] ?? null;
            $this->functions[$this->editingFunctionIndex] = $functionData;
        } else {
            $this->functions[] = $functionData;
        }

        $this->cancelFunctionForm();
    }

    public function removeFunction($index)
    {
        unset($this->functions[$index]);
        $this->functions = array_values($this->functions);
    }

    public function cancelFunctionForm()
    {
        $this->showFunctionForm = false;
        $this->editingFunctionIndex = null;
        $this->resetTempFunction();
    }

    private function resetTempFunction()
    {
        $this->tempFunction = [
            'type' => '',
            'subtype' => '',
            'value' => '',
            'enabled' => true,
            'description' => '',
            'groups' => []
        ];
    }

    private function resetForm()
    {
        $this->vendorUuid = '';
        $this->vendorName = '';
        $this->vendorEnabled = true;
        $this->vendorDescription = '';
        $this->functions = [];
        $this->isEdit = false;
        $this->showFunctionForm = false;
        $this->editingFunctionIndex = null;
        $this->resetTempFunction();
    }

    public function render()
    {
        return view('livewire.device-vendor-form');
    }
}
