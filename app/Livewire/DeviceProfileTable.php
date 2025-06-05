<?php

namespace App\Livewire;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\DeviceProfile;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;

class DeviceProfileTable extends DataTableComponent
{
    protected $model = DeviceProfile::class;

    public function configure(): void
    {
        $this->setPrimaryKey('device_profile_uuid');
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'device_profile_uuid')
                ->sortable()
                ->hideIf(true),
            Column::make("Name", "device_profile_name")
                ->sortable(),
            BooleanColumn::make("Enabled", "device_profile_enabled")
                ->sortable(),
            Column::make("Description", "device_profile_description")
                ->sortable(),
        ];
    }
}
