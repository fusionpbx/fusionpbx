<?php

namespace App\Livewire;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Device;
use App\Models\DeviceKey;
use App\Models\DeviceLine;
use App\Models\DeviceSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;
use Illuminate\Support\Str;

class DeviceTable extends DataTableComponent
{
    protected $model = Device::class;
    public bool $show_all = false;

    public function configure(): void
    {
        $canEdit = auth()->user()->hasPermission('device_edit');
        $tableConfig = $this->setPrimaryKey('device_uuid')
                        ->setTableAttributes([
                'class' => 'table table-striped table-hover table-bordered'
            ])            
            ->setSearchEnabled()
            ->setSearchPlaceholder('Search devices')
            ->setPerPageAccepted([10, 25, 50, 100])
            ->setPaginationEnabled();

        if ($canEdit) {
            $tableConfig->setTableRowUrl(function ($row) use ($canEdit) {
                return route('devices.edit', $row->device_uuid);
            });
        }

        if(request('show_all')) {
            $this->show_all = true;
        }
    }

    public function columns(): array
    {

        $columns = [];
        $columns = [
            Column::make("Device uuid", "device_uuid")
                ->sortable()
                ->hideIf(true),
            Column::make("Domain uuid", "domain_uuid")
                ->sortable()
                ->hideIf(true),
            Column::make("Mac address", "device_mac_address")
                ->sortable()
                ->searchable(),
            Column::make("Device label", "device_label")
                ->sortable(),
            Column::make("Device vendor", "device_vendor")
                ->sortable(),
            Column::make("Device template", "device_template")
                ->sortable(),
            BooleanColumn::make('Enabled', 'device_enabled')
                ->sortable(),
            Column::make('Profile', 'profile.device_profile_name')
                ->format(fn($value, $row) => $row->profile?->device_profile_name ?? '-')
                ->searchable()
                ->html(),
            Column::make("Device description", "device_description")
                ->sortable(),
        ];
        
        if ($this->show_all) {
            array_splice($columns, 3, 0, [
                Column::make("Domain", "domain.domain_name")
                    ->sortable()
                    ->searchable()
            ]);
        }

        return $columns;
    }

    public function bulkActions(): array
    {
        $bulkActions = [];

        if (auth()->user()->hasPermission('device_profile_edit')) {
            $bulkActions['toogleDevice'] = 'Toggle';
        }

        if (auth()->user()->hasPermission('device_profile_delete')) {
            $bulkActions['bulkDelete'] = 'Delete';
        }

        if (auth()->user()->hasPermission('device_profile_add')) {
            $bulkActions['bulkCopy'] = 'Copy';
        }

        return $bulkActions;
    }

    public function tooggleDevice() 
    {
        $selectRows = $this->getSelected();

        Device::whereIn('device_uuid', $selectRows)
            ->update([
                'device_enabled' => DB::raw("CASE WHEN device_enabled = 'true' THEN 'false' ELSE 'true' END")
            ]);

        $this->clearSelected();
        $this->dispatch('refresh');

        session()->flash('message', 'Device status toggled successfully');
        
    }

    public function bulkDelete() 
    {
        $selectRows = $this->getSelected();

        try {
            DB::beginTransaction();

            Device::whereIn('device_uuid', $selectRows)->delete();
            DeviceLine::whereIn('device_uuid', $selectRows)->delete();
            DeviceKey::whereIn('device_uuid', $selectRows)->delete();
            DeviceSetting::whereIn('device_uuid', $selectRows)->delete();

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');

            session()->flash('message', 'Devices deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function bulkCopy() 
    {
        $selectRows = $this->getSelected();

        try {
            DB::beginTransaction();

            $devices = Device::whereIn('device_uuid', $selectRows)->get();

            foreach ($devices as $device) {
                $newDevice = $device->replicate();
                $newDevice->device_uuid = Str::uuid();
                $newDevice->device_description .= ' (Copy)';
                $newDevice->save();

                $deviceKeys = DeviceKey::where('device_uuid', $device->device_uuid)->get();
                foreach ($deviceKeys as $deviceKey) {
                    $newKey = $deviceKey->replicate();
                    $newKey->device_key_uuid = Str::uuid();
                    $newKey->device_uuid = $newDevice->device_uuid;
                    $newKey->save();
                }

                $deviceLines = DeviceLine::where('device_uuid', $device->device_uuid)->get();
                foreach ($deviceLines as $deviceLine) {
                    $newLine = $deviceLine->replicate();
                    $newLine->device_line_uuid = Str::uuid();
                    $newLine->device_uuid = $newDevice->device_uuid;
                    $newLine->save();
                }


                $deviceSettings = DeviceSetting::where('device_uuid', $device->device_uuid)->get();
                foreach ($deviceSettings as $deviceSetting) {
                    $newSetting = $deviceSetting->replicate();
                    $newSetting->device_setting_uuid = Str::uuid();
                    $newSetting->device_uuid = $newDevice->device_uuid;
                    $newSetting->save();
                }
            }

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');

            session()->flash('message', 'Devices copied successfully');
        }
         catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function builder(): Builder
    {
        $query = Device::query();

        if ($this->show_all) {
            $query->leftJoin('v_domains', 'v_devices.domain_uuid', '=', 'v_domains.domain_uuid')
                ->select('v_devices.*', 'v_domains.domain_name');
        } else {
            $query->where(function ($query) {
                $query->where('v_devices.domain_uuid', auth()->user()->domain_uuid)
                    ->orWhereNull('v_devices.domain_uuid');
            });
        }

        return $query;
    }

}
