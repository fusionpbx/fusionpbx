<?php

namespace App\Livewire;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\DeviceVendor;
use App\Models\DeviceVendorFunction;
use Illuminate\Support\Facades\DB;

class DeviceVendorTable extends DataTableComponent
{
    protected $model = DeviceVendor::class;

    public function configure(): void
    {
        $canEdit = auth()->user()->hasPermission('device_vendor_edit');
        
        $tableConfig = $this->setPrimaryKey('device_vendor_uuid')
            ->setTableAttributes([
                'class' => 'table table-striped table-hover table-bordered'
            ])
            ->setSearchEnabled()
            ->setSearchPlaceholder('Search Device Vendors')
            ->setPerPageAccepted([10, 25, 50, 100])
            ->setPaginationEnabled();

        if ($canEdit) {
            $tableConfig->setTableRowUrl(function ($row) use ($canEdit) {
                return route('devices_vendors.edit', $row->device_vendor_uuid);
            });
        }
    }

    public function bulkActions(): array
    {
        $bulkActions = [];

        if (auth()->user()->hasPermission('device_vendor_delete')) {
            $bulkActions['bulkDelete'] = 'Delete';
        }

        if (auth()->user()->hasPermission('device_vendor_edit')) {
            $bulkActions['bulkToggle'] = 'Toggle';
        }

        return $bulkActions;
    }

    public function bulkToggle()
    {
        $selectedRows = $this->getSelected();

        DeviceVendor::whereIn('device_vendor_uuid', $selectedRows)->update([
            'enabled' => DB::raw("CASE WHEN enabled = 'true' THEN 'false' ELSE 'true' END")
        ]);

        $this->clearSelected();
        $this->dispatch('refresh');
        session()->flash('message', 'Device vendors status toggled successfully');
    }

    public function bulkDelete()
    {
        $selectedRows = $this->getSelected();

        try {
            DB::beginTransaction();

            DeviceVendor::whereIn('device_vendor_uuid', $selectedRows)->delete();
            DeviceVendorFunction::whereIn('device_vendor_uuid', $selectedRows)->delete();

            $this->clearSelected();
            $this->dispatch('refresh');

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function columns(): array
    {
        return [
            Column::make("Device vendor uuid", "device_vendor_uuid")
                ->sortable()
                ->hideIf(true),
            Column::make("Name", "name")
                ->sortable()
                ->searchable(),
            Column::make("Enabled", "enabled")
                ->sortable()
                ->searchable(),
            Column::make("Description", "description")
                ->sortable()
                ->searchable(),
        ];
    }
}
