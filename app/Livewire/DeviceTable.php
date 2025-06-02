<?php

namespace App\Livewire;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Device;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;

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

        // if ($canEdit) {
        //     $tableConfig->setTableRowUrl(function ($row) use ($canEdit) {
        //         return route('devices.edit', $row->device_uuid);
        //     });
        // }

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
