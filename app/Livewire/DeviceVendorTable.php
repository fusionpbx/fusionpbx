<?php

namespace App\Livewire;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\DeviceVendor;

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
