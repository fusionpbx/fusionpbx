<?php

namespace App\Livewire;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\DeviceProfile;
use App\Models\DeviceProfileKey;
use App\Models\DeviceProfileSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;

class DeviceProfileTable extends DataTableComponent
{
    protected $model = DeviceProfile::class;
    public bool $show_all = false;

    public function configure(): void
    {
        $canEdit = auth()->user()->hasPermission('device_profile_edit');
        $tableConfig = $this->setPrimaryKey('device_profile_uuid')
            ->setTableAttributes([
                'class' => 'table table-striped table-hover table-bordered'
            ])
            ->setSearchEnabled()
            ->setSearchPlaceholder('Search Device Profiles')
            ->setPerPageAccepted([10, 25, 50, 100])
            ->setPaginationEnabled();

        if ($canEdit) {
            $tableConfig->setTableRowUrl(function ($row) use ($canEdit) {
                return route('devices_profiles.edit', $row->device_profile_uuid);
            });
        }
    }

    public function bulkActions(): array
    {
        $bulkActions = [];

        if (auth()->user()->hasPermission('device_profile_edit')) {
            $bulkActions['toogleDeviceProfile'] = 'Toggle';
        }

        if (auth()->user()->hasPermission('device_profile_delete')) {
            $bulkActions['bulkDelete'] = 'Delete';
        }

        if (auth()->user()->hasPermission('device_profile_add')) {
            $bulkActions['bulkCopy'] = 'Copy';
        }

        return $bulkActions;
    }

    public function toogleDeviceProfile(): void
    {
        if (!auth()->user()->hasPermission('device_profile_edit')) {
            session()->flash('error', 'You do not have permission to toggle Device Profile status.');
            return;
        }
        $selectRows = $this->getSelected();

        DeviceProfile::whereIn('device_profile_uuid', $selectRows)
            ->update([
                'device_profile_enabled' => DB::raw("CASE WHEN device_profile_enabled = 'true' THEN 'false' ELSE 'true' END")
            ]);

        $this->clearSelected();
        $this->dispatch('refresh');

        session()->flash('message', 'Device Profile status toggled successfully');
    }

    public function bulkDelete(): void
    {
        if (!auth()->user()->hasPermission('device_profile_delete')) {
            session()->flash('error', 'You do not have permission to delete Device Profiles.');
            return;
        }

        $selectedRows = $this->getSelected();

        try {
            DB::beginTransaction();

            DeviceProfile::whereIn('device_profile_uuid', $selectedRows)->delete();
            DeviceProfileKey::whereIn('device_profile_uuid', $selectedRows)->delete();
            DeviceProfileSetting::whereIn('device_profile_uuid', $selectedRows)->delete();

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function bulkCopy(): void
    {
        if (!auth()->user()->hasPermission('device_profile_add')) {
            session()->flash('error', 'You do not have permission to copy Device Profiles.');
            return;
        }

        $selectedRows = $this->getSelected();

        try {
            DB::beginTransaction();

            $deviceProfiles = DeviceProfile::where('device_profile_uuid', $selectedRows)->with('keys', 'settings')->get();
            foreach ($deviceProfiles as $deviceProfile) {
                $originalUuid = $deviceProfile->device_profile_uuid;
                $newUuid = Str::uuid(); 

                $newProfile = $deviceProfile->replicate(); 
                $newProfile->device_profile_uuid = $newUuid;
                $newProfile->device_profile_description .= ' (Copy)';
                $newProfile->save();

                $deviceProfileKeys = DeviceProfileKey::where('device_profile_uuid', $originalUuid)->get();
                foreach ($deviceProfile->keys as $deviceProfileKey) {
                    $newKey = $deviceProfileKey->replicate();
                    $newKey->device_profile_key_uuid = Str::uuid();
                    $newKey->device_profile_uuid = $newProfile->device_profile_uuid;
                    $newKey->save();
                }

                $deviceProfileSettings = DeviceProfileSetting::where('device_profile_uuid', $originalUuid)->get();
                foreach ($deviceProfile->settings as $deviceProfileSetting) {
                    $newSetting = $deviceProfileSetting->replicate();
                    $newSetting->device_profile_setting_uuid = Str::uuid();
                    $newSetting->device_profile_uuid =  $newProfile->device_profile_uuid;
                    $newSetting->save();
                }
            }

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function columns(): array
    {
        $columns = [
            Column::make('ID', 'device_profile_uuid')
                ->sortable()
                ->hideIf(true),
            Column::make("Name", "device_profile_name")
                ->sortable(),
            Column::make("Enabled", "device_profile_enabled")
                ->sortable(),
            Column::make("Description", "device_profile_description")
                ->sortable(),
        ];

        if ($this->show_all) {
            array_splice($columns, 3, 0, [
                Column::make("Domain", "domain.domain_name")
                    ->sortable()
                    ->searchable(),
            ]);
        }

        return $columns;
    }

    public function builder(): Builder
    {
        $query = DeviceProfile::query();
        if ($this->show_all) {
            $query->leftJoin('v_domains', 'v_device_profiles.domain_uuid', '=', 'v_domains.domain_uuid')
                ->select('v_device_profiles.*', 'v_domains.domain_name');
        } else {
            $query->where(function ($query) {
                $query->where('v_device_profiles.domain_uuid', auth()->user()->domain_uuid)
                    ->orWhereNull('v_device_profiles.domain_uuid');
            });
        }
        return $query;
    }
}
