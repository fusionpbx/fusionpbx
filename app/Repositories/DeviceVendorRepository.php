<?php

namespace App\Repositories;

use App\Models\DeviceVendor;
use App\Models\DeviceVendorFunction;
use App\Models\DeviceVendorFunctionGroup;
use App\Models\Group;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class DeviceVendorRepository
{
    protected $deviceVendor;
    protected $deviceVendorFunction;
    protected $deviceVendorFunctionGroup;
    protected $group;

    protected $deviceVendorsTable = 'v_device_vendors';
    protected $deviceVendorFunctionsTable = 'v_device_vendor_functions';
    protected $deviceVendorFunctionGroupsTable = 'v_device_vendor_function_groups';
    protected $groupsTable = 'v_groups';

    public function __construct(
        DeviceVendor $deviceVendor,
        DeviceVendorFunction $deviceVendorFunction,
        DeviceVendorFunctionGroup $deviceVendorFunctionGroup,
        Group $group
    ) {
        $this->deviceVendor = $deviceVendor;
        $this->deviceVendorFunction = $deviceVendorFunction;
        $this->deviceVendorFunctionGroup = $deviceVendorFunctionGroup;
        $this->group = $group;
    }


    public function getAllVendors(): array
    {
        return $this->deviceVendor->orderBy('name', 'asc')->get()->toArray();
    }

    public function getEnabledVendors(): array
    {
        return $this->deviceVendor
            ->where('enabled', 'true')
            ->orderBy('name', 'asc')
            ->get()
            ->toArray();
    }

    public function findVendorByUuid(string $deviceVendorUuid, bool $withFunctions = false): ?DeviceVendor
    {
        $query = $this->deviceVendor->where('device_vendor_uuid', $deviceVendorUuid);

        if ($withFunctions) {
            $query->with(['functions']);
        }

        return $query->first();
    }

    public function createVendor(array $vendorData): DeviceVendor
    {
        $vendorData['device_vendor_uuid'] = $vendorData['device_vendor_uuid'] ?? Str::uuid();

        try {
            DB::beginTransaction();

            $filteredData = $this->applyVendorPermissions($vendorData);
            $vendor = $this->deviceVendor->create($filteredData);

            DB::commit();
            return $vendor;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateVendor(string $deviceVendorUuid, array $vendorData): DeviceVendor
    {
        try {
            DB::beginTransaction();

            $vendor = $this->findVendorByUuid($deviceVendorUuid);
            if (!$vendor) {
                throw new Exception("Device vendor not found");
            }

            $filteredData = $this->applyVendorPermissions($vendorData, $vendor);
            $vendor->update($filteredData);

            DB::commit();
            return $vendor->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteVendor(string $deviceVendorUuid): void
    {
        try {
            DB::beginTransaction();

            $vendor = $this->findVendorByUuid($deviceVendorUuid);
            if (!$vendor) {
                throw new Exception("Device vendor not found");
            }

            $functions = $this->getVendorFunctions($deviceVendorUuid);
            foreach ($functions as $function) {
                $this->deleteVendorFunction($function['device_vendor_function_uuid']);
            }

            $vendor->delete();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


    public function getVendorFunctions(string $deviceVendorUuid)    
    {
        return $this->deviceVendorFunction
            ->where('device_vendor_uuid', $deviceVendorUuid)
            ->orderBy('type', 'asc')
            ->orderBy('subtype', 'asc')
            ->get()
            ->toArray();
    }

    public function findVendorFunctionByUuid(string $deviceVendorFunctionUuid, bool $withGroups = false): ?DeviceVendorFunction
    {
        $query = $this->deviceVendorFunction->where('device_vendor_function_uuid', $deviceVendorFunctionUuid);

        if ($withGroups) {
            $query->with(['groups']);
        }

        return $query->first();
    }

    public function createVendorFunction(string $deviceVendorUuid, array $functionData, array $groupData = []): DeviceVendorFunction
    {
        $functionData['device_vendor_function_uuid'] = $functionData['device_vendor_function_uuid'] ?? Str::uuid();
        $functionData['device_vendor_uuid'] = $deviceVendorUuid;

        try {
            DB::beginTransaction();

            $filteredData = $this->applyVendorFunctionPermissions($functionData);
            $function = $this->deviceVendorFunction->create($filteredData);

            if (!empty($groupData)) {
                $this->syncFunctionGroups($function->device_vendor_function_uuid, $deviceVendorUuid, $groupData);
            }

            DB::commit();
            return $function;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateVendorFunction(string $deviceVendorFunctionUuid, array $functionData, array $groupData = []): DeviceVendorFunction
    {
        try {
            DB::beginTransaction();

            $function = $this->findVendorFunctionByUuid($deviceVendorFunctionUuid);
            if (!$function) {
                throw new Exception("Device vendor function not found");
            }

            $filteredData = $this->applyVendorFunctionPermissions($functionData, $function);
            $function->update($filteredData);

            if (!empty($groupData)) {
                $this->syncFunctionGroups($function->device_vendor_function_uuid, $function->device_vendor_uuid, $groupData);
            }

            DB::commit();
            return $function->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteVendorFunction(string $deviceVendorFunctionUuid): void
    {
        try {
            DB::beginTransaction();

            $function = $this->findVendorFunctionByUuid($deviceVendorFunctionUuid);
            if (!$function) {
                throw new Exception("Device vendor function not found");
            }

            $this->deviceVendorFunctionGroup
                ->where('device_vendor_function_uuid', $deviceVendorFunctionUuid)
                ->delete();

            $function->delete();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


    public function getFunctionGroups(string $deviceVendorFunctionUuid, string $deviceVendorUuid): array
    {
        return DB::table($this->deviceVendorFunctionGroupsTable . ' as fg')
            ->join($this->groupsTable . ' as g', 'fg.group_uuid', '=', 'g.group_uuid')
            ->where('fg.device_vendor_uuid', $deviceVendorUuid)
            ->where('fg.device_vendor_function_uuid', $deviceVendorFunctionUuid)
            ->select('fg.*', 'g.domain_uuid as group_domain_uuid', 'g.group_name')
            ->orderBy('g.domain_uuid', 'desc')
            ->orderBy('g.group_name', 'asc')
            ->get()
            ->toArray();
    }

    public function addFunctionGroup(string $deviceVendorFunctionUuid, string $deviceVendorUuid, string $groupUuid, string $groupName): DeviceVendorFunctionGroup
    {
        $data = [
            'device_vendor_function_uuid' => $deviceVendorFunctionUuid,
            'device_vendor_uuid' => $deviceVendorUuid,
            'group_uuid' => $groupUuid,
            'group_name' => $groupName,
        ];

        return $this->deviceVendorFunctionGroup->create($data);
    }

    public function deleteFunctionGroup(string $deviceVendorFunctionGroupUuid): void
    {
        $functionGroup = $this->deviceVendorFunctionGroup
            ->where('device_vendor_function_group_uuid', $deviceVendorFunctionGroupUuid)
            ->first();

        if ($functionGroup) {
            $functionGroup->delete();
        }
    }

    public function getAvailableGroups(array $assignedGroupUuids = []): array
    {
        $query = $this->group->select('group_uuid', 'group_name', 'domain_uuid');

        if (!empty($assignedGroupUuids)) {
            $query->whereNotIn('group_uuid', $assignedGroupUuids);
        }

        return $query->orderBy('domain_uuid', 'desc')
            ->orderBy('group_name', 'asc')
            ->get()
            ->toArray();
    }

    public function getAssignedGroupUuids(string $deviceVendorFunctionUuid): array
    {
        return $this->deviceVendorFunctionGroup
            ->where('device_vendor_function_uuid', $deviceVendorFunctionUuid)
            ->pluck('group_uuid')
            ->toArray();
    }

    public function getVendorFunctionsByType(string $type, ?string $deviceVendorUuid = null): array
    {
        $query = $this->deviceVendorFunction->where('type', $type)->where('enabled', 'true');

        if ($deviceVendorUuid) {
            $query->where('device_vendor_uuid', $deviceVendorUuid);
        }

        return $query->orderBy('subtype', 'asc')
            ->orderBy('value', 'asc')
            ->get()
            ->toArray();
    }

    public function getVendorFunctionsBySubtype(string $subtype, ?string $deviceVendorUuid = null): array
    {
        $query = $this->deviceVendorFunction->where('subtype', $subtype)->where('enabled', 'true');

        if ($deviceVendorUuid) {
            $query->where('device_vendor_uuid', $deviceVendorUuid);
        }

        return $query->orderBy('type', 'asc')
            ->orderBy('value', 'asc')
            ->get()
            ->toArray();
    }


    private function applyVendorPermissions(array $vendorData, ?DeviceVendor $existingVendor = null): array
    {
        $filteredData = [];
        $user = auth()->user();

        if (is_null($existingVendor)) {
            $filteredData['device_vendor_uuid'] = $vendorData['device_vendor_uuid'] ?? Str::uuid();
        }

        if ($user->hasPermission('device_vendor_add') || $user->hasPermission('device_vendor_edit')) {
            $filteredData['name'] = $vendorData['name'] ?? ($existingVendor->name ?? null);
            $filteredData['enabled'] = $vendorData['enabled'] ?? ($existingVendor->enabled ?? 'true');
            $filteredData['description'] = $vendorData['description'] ?? ($existingVendor->description ?? null);
        }

        return $filteredData;
    }

    private function applyVendorFunctionPermissions(array $functionData, ?DeviceVendorFunction $existingFunction = null): array
    {
        $filteredData = [];
        $user = auth()->user();

        if (is_null($existingFunction)) {
            $filteredData['device_vendor_function_uuid'] = $functionData['device_vendor_function_uuid'] ?? Str::uuid();
            $filteredData['device_vendor_uuid'] = $functionData['device_vendor_uuid'];
        }

        if ($user->hasPermission('device_vendor_function_add') || $user->hasPermission('device_vendor_function_edit')) {
            $filteredData['type'] = $functionData['type'] ?? ($existingFunction->type ?? null);
            $filteredData['subtype'] = $functionData['subtype'] ?? ($existingFunction->subtype ?? null);
            $filteredData['value'] = $functionData['value'] ?? ($existingFunction->value ?? null);
            $filteredData['enabled'] = $functionData['enabled'] ?? ($existingFunction->enabled ?? 'true');
            $filteredData['description'] = $functionData['description'] ?? ($existingFunction->description ?? null);
        }

        return $filteredData;
    }

    private function syncFunctionGroups(string $deviceVendorFunctionUuid, string $deviceVendorUuid, array $groupData): void
    {
        foreach ($groupData as $groupInfo) {
            if (is_string($groupInfo) && strpos($groupInfo, '|') !== false) {
                [$groupUuid, $groupName] = explode('|', $groupInfo, 2);
                $this->addFunctionGroup($deviceVendorFunctionUuid, $deviceVendorUuid, $groupUuid, $groupName);
            } elseif (is_array($groupInfo) && isset($groupInfo['group_uuid'], $groupInfo['group_name'])) {
                $this->addFunctionGroup(
                    $deviceVendorFunctionUuid,
                    $deviceVendorUuid,
                    $groupInfo['group_uuid'],
                    $groupInfo['group_name']
                );
            }
        }
    }


    public function searchVendors(string $search = '', array $filters = []): array
    {
        $query = $this->deviceVendor->newQuery();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if (isset($filters['enabled'])) {
            $query->where('enabled', $filters['enabled']);
        }

        return $query->orderBy('name', 'asc')->get()->toArray();
    }

    public function searchVendorFunctions(string $deviceVendorUuid, string $search = '', array $filters = []): array
    {
        $query = $this->deviceVendorFunction->where('device_vendor_uuid', $deviceVendorUuid);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('type', 'like', '%' . $search . '%')
                  ->orWhere('subtype', 'like', '%' . $search . '%')
                  ->orWhere('value', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if (isset($filters['enabled'])) {
            $query->where('enabled', $filters['enabled']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['subtype'])) {
            $query->where('subtype', $filters['subtype']);
        }

        return $query->orderBy('type', 'asc')
            ->orderBy('subtype', 'asc')
            ->orderBy('value', 'asc')
            ->get()
            ->toArray();
    }


    public function getVendorStats(): array
    {
        $totalVendors = $this->deviceVendor->count();
        $enabledVendors = $this->deviceVendor->where('enabled', 'true')->count();
        $disabledVendors = $totalVendors - $enabledVendors;

        return [
            'total_vendors' => $totalVendors,
            'enabled_vendors' => $enabledVendors,
            'disabled_vendors' => $disabledVendors,
        ];
    }

    public function getVendorFunctionStats(string $deviceVendorUuid): array
    {
        $totalFunctions = $this->deviceVendorFunction->where('device_vendor_uuid', $deviceVendorUuid)->count();
        $enabledFunctions = $this->deviceVendorFunction
            ->where('device_vendor_uuid', $deviceVendorUuid)
            ->where('enabled', 'true')
            ->count();
        $disabledFunctions = $totalFunctions - $enabledFunctions;

        $functionsByType = $this->deviceVendorFunction
            ->where('device_vendor_uuid', $deviceVendorUuid)
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get()
            ->toArray();

        return [
            'total_functions' => $totalFunctions,
            'enabled_functions' => $enabledFunctions,
            'disabled_functions' => $disabledFunctions,
            'functions_by_type' => $functionsByType,
        ];
    }
}