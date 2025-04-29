<?php

namespace App\Livewire;

use App\Models\Bridge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;

class BridgesTable extends DataTableComponent
{
    protected $model = Bridge::class;

    public function configure(): void
    {
        $canEdit = auth()->user()->hasPermission('bridge_edit');
        $this->setPrimaryKey('bridge_uuid')
            ->setTableAttributes([
                'class' => 'table table-striped table-hover table-bordered'
            ])
            ->setSearchEnabled()
            ->setSearchPlaceholder('Search Bridges')
            ->setPerPageAccepted([10, 25, 50, 100, 250])
            ->setDefaultPerPage(100)
            ->setTableRowUrl(function ($row) use ($canEdit)
            {
                return $canEdit
                    ? route('bridges.edit', $row->bridge_uuid)
                    : null;
            })
            ->setPaginationEnabled();
    }

    public function bulkActions(): array
    {
        $bulkActions = [];

        if (auth()->user()->hasPermission('bridge_edit'))
        {
            $bulkActions['markEnabled'] = 'Mark as Enabled';
            $bulkActions['markDisabled'] = 'Mark as Disabled';
        }

        if (auth()->user()->hasPermission('bridge_delete'))
        {
            $bulkActions['bulkDelete'] = 'Delete';
        }

        if (auth()->user()->hasPermission('bridge_add'))
        {
            $bulkActions['bulkCopy'] = 'Copy';
        }

        return $bulkActions;
    }

    public function markEnabled()
    {
        if (!auth()->user()->hasPermission('bridge_edit'))
        {
            session()->flash('error', 'You do not have permission to mark bridges as enabled.');
            return;
        }

        $selectedRows = $this->getSelected();

        Bridge::whereIn('bridge_uuid', $selectedRows)->update(['bridge_enabled' => 'true']);

        $this->clearSelected();
        $this->dispatch('refresh');
        session()->flash('success', 'The bridges were successfully enabled.');
    }

    public function markDisabled()
    {
        if (!auth()->user()->hasPermission('bridge_edit'))
        {
            session()->flash('error', 'You do not have permission to mark bridges as disabled.');
            return;
        }

        $selectedRows = $this->getSelected();

        Bridge::whereIn('bridge_uuid', $selectedRows)->update(['bridge_enabled' => 'false']);

        $this->clearSelected();
        $this->dispatch('refresh');
        session()->flash('success', 'The bridges were successfully disabled.');
    }


    public function bulkDelete()
    {
        if (!auth()->user()->hasPermission('bridge_delete'))
        {
            session()->flash('error', 'You do not have permission to delete bridges.');
            return;
        }

        $selectedRows = $this->getSelected();

        try
        {
            DB::beginTransaction();

            Bridge::whereIn('bridge_uuid', $selectedRows)->delete();

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
            session()->flash('success', 'Bridges successfully deleted.');
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            session()->flash('error', 'There was a problem deleting the bridges: ' . $e->getMessage());
        }
    }

    public function bulkCopy()
    {
        if (!auth()->user()->hasPermission('bridge_add'))
        {
            session()->flash('error', 'You do not have permission to copy bridges.');
            return;
        }

        $selectedRows = $this->getSelected();

        try
        {
            DB::beginTransaction();

            foreach ($selectedRows as $bridgeUuid)
            {
                $originalBridge = Bridge::findOrFail($bridgeUuid);

                $newBridge = $originalBridge->replicate();
                $newBridge->bridge_uuid = Str::uuid();
                $newBridge->domain_uuid = $newBridge->domain_uuid;
                $newBridge->bridge_name = $newBridge->bridge_name . ' (Copy)';
                $newBridge->bridge_enabled = $newBridge->bridge_enabled;
                $newBridge->bridge_description = $newBridge->bridge_description;
                $newBridge->save();
            }

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            throw $e;
            session()->flash('error', 'There was a problem copying the bridges: ' . $e->getMessage());
        }
    }

    public function columns(): array
    {
        return [
            Column::make("Bridge uuid", "bridge_uuid")->hideIf(true),

            Column::make("Bridge name", "bridge_name")
                ->sortable(),

            Column::make("Bridge destination", "bridge_destination")
                ->sortable(),

            BooleanColumn::make("Bridge enabled", "bridge_enabled")
                ->sortable(),
        ];
    }

    public function builder(): Builder
    {
        $query = Bridge::query()
            ->where('domain_uuid', Session::get('domain_uuid'))
            ->orderBy('bridge_name', 'asc');
        return $query;
    }
}
