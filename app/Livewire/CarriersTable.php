<?php

namespace App\Livewire;

use App\Models\Carrier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;

class CarriersTable extends DataTableComponent
{
    protected $model = Carrier::class;

    public function configure(): void
    {
        $canEdit = auth()->user()->hasPermission('lcr_edit');
        $this->setPrimaryKey('carrier_uuid')
            ->setTableAttributes([
                'class' => 'table table-striped table-hover table-bordered'
            ])
            ->setSearchEnabled()
            ->setSearchPlaceholder('Search Carriers')
            ->setPerPageAccepted([10, 25, 50, 100, 250])
            ->setDefaultPerPage(100)
            ->setTableRowUrl(function ($row) use ($canEdit)
            {
                return $canEdit
                    ? route('carriers.edit', $row->carrier_uuid)
                    : null;
            })
            ->setPaginationEnabled();
    }

    public function bulkActions(): array
    {
        $bulkActions = [];

        if (auth()->user()->hasPermission('lcr_edit'))
        {
            $bulkActions['markEnabled'] = 'Mark as Enabled';
            $bulkActions['markDisabled'] = 'Mark as Disabled';
        }

        if (auth()->user()->hasPermission('lcr_delete'))
        {
            $bulkActions['bulkDelete'] = 'Delete';
        }

        if (auth()->user()->hasPermission('lcr_add'))
        {
            $bulkActions['bulkCopy'] = 'Copy';
        }

        return $bulkActions;
    }

    public function markEnabled()
    {
        if (!auth()->user()->hasPermission('lcr_edit'))
        {
            session()->flash('error', 'You do not have permission to mark carriers as enabled.');
            return;
        }

        $selectedRows = $this->getSelected();

        Carrier::whereIn('carrier_uuid', $selectedRows)->update(['carrier_enabled' => 'true']);

        $this->clearSelected();
        $this->dispatch('refresh');
        session()->flash('success', 'The carriers were successfully enabled.');
    }

    public function markDisabled()
    {
        if (!auth()->user()->hasPermission('lcr_edit'))
        {
            session()->flash('error', 'You do not have permission to mark carriers as disabled.');
            return;
        }

        $selectedRows = $this->getSelected();

        Carrier::whereIn('carrier_uuid', $selectedRows)->update(['carrier_enabled' => 'false']);

        $this->clearSelected();
        $this->dispatch('refresh');
        session()->flash('success', 'The carriers were successfully disabled.');
    }


    public function bulkDelete()
    {
        if (!auth()->user()->hasPermission('lcr_delete'))
        {
            session()->flash('error', 'You do not have permission to delete carriers.');
            return;
        }

        $selectedRows = $this->getSelected();

        try
        {
            DB::beginTransaction();

            Carrier::whereIn('carrier_uuid', $selectedRows)->delete();

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
            session()->flash('success', 'Carriers successfully deleted.');
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            session()->flash('error', 'There was a problem deleting the carriers: ' . $e->getMessage());
        }
    }

    public function bulkCopy()
    {
        if (!auth()->user()->hasPermission('lcr_add'))
        {
            session()->flash('error', 'You do not have permission to copy carriers.');
            return;
        }

        $selectedRows = $this->getSelected();

        try
        {
            DB::beginTransaction();

            foreach ($selectedRows as $carrierUuid)
            {
                $originalCarrier = Carrier::findOrFail($carrierUuid);

                $newCarrier = $originalCarrier->replicate();
                $newCarrier->carrier_uuid = Str::uuid();
                $newCarrier->carrier_name = $originalCarrier->carrier_name . ' (Copy)';
                $newCarrier->enabled = $originalCarrier->enabled;
                $newCarrier->carrier_channels = $originalCarrier->carrier_channels;
                $newCarrier->priority = $originalCarrier->priority;
                $newCarrier->fax_enabled = $originalCarrier->fax_enabled;
                $newCarrier->cancellation_ratio = $originalCarrier->cancellation_ratio;
                $newCarrier->lcr_tags = $originalCarrier->lcr_tags;
            }

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            throw $e;
            session()->flash('error', 'There was a problem copying the carriers: ' . $e->getMessage());
        }
    }

    public function columns(): array
    {
        return [
            Column::make("Carrier uuid", "carrier_uuid")->hideIf(true),

            Column::make("Carrier name", "carrier_name")
                ->searchable()
                ->sortable(),

            BooleanColumn::make("Carrier enabled", "enabled")
                ->sortable(),
        ];
    }

    public function builder(): Builder
    {
        $query = Carrier::query()
            ->orderBy('carrier_name', 'asc');
        return $query;
    }
}
