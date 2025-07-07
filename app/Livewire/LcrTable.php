<?php

namespace App\Livewire;

use App\Models\Lcr;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;

class LcrTable extends DataTableComponent
{
    protected $model = Lcr::class;

    public function configure(): void
    {
        $canEdit = auth()->user()->hasPermission('lcr_edit');
        $this->setPrimaryKey('lcr_uuid')
            ->setTableAttributes([
                'class' => 'table table-striped table-hover table-bordered'
            ])
            ->setSearchEnabled()
            ->setSearchPlaceholder('Search Lcr')
            ->setPerPageAccepted([10, 25, 50, 100, 250])
            ->setDefaultPerPage(100)
            ->setTableRowUrl(function ($row) use ($canEdit)
            {
                return $canEdit
                    ? route('lcr.edit', $row->lcr_uuid)
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
            session()->flash('error', 'You do not have permission to mark lcrs as enabled.');
            return;
        }

        $selectedRows = $this->getSelected();

        Lcr::whereIn('lcr_uuid', $selectedRows)->update(['lcr_enabled' => 'true']);

        $this->clearSelected();
        $this->dispatch('refresh');
        session()->flash('success', 'The lcrs were successfully enabled.');
    }

    public function markDisabled()
    {
        if (!auth()->user()->hasPermission('lcr_edit'))
        {
            session()->flash('error', 'You do not have permission to mark lcrs as disabled.');
            return;
        }

        $selectedRows = $this->getSelected();

        Lcr::whereIn('lcr_uuid', $selectedRows)->update(['lcr_enabled' => 'false']);

        $this->clearSelected();
        $this->dispatch('refresh');
        session()->flash('success', 'The lcrs were successfully disabled.');
    }


    public function bulkDelete()
    {
        if (!auth()->user()->hasPermission('lcr_delete'))
        {
            session()->flash('error', 'You do not have permission to delete lcrs.');
            return;
        }

        $selectedRows = $this->getSelected();

        try
        {
            DB::beginTransaction();

            Lcr::whereIn('lcr_uuid', $selectedRows)->delete();

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
            session()->flash('success', 'Lcr successfully deleted.');
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            session()->flash('error', 'There was a problem deleting the lcrs: ' . $e->getMessage());
        }
    }

    public function bulkCopy()
    {
        if (!auth()->user()->hasPermission('lcr_add'))
        {
            session()->flash('error', 'You do not have permission to copy lcrs.');
            return;
        }

        $selectedRows = $this->getSelected();

        try
        {
            DB::beginTransaction();

            foreach ($selectedRows as $lcrUuid)
            {
                $originalLcr = Lcr::findOrFail($lcrUuid);

                $newLcr = $originalLcr->replicate();
                $newLcr->lcr_uuid = Str::uuid();
                $newLcr->save();
            }

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            throw $e;
            session()->flash('error', 'There was a problem copying the lcrs: ' . $e->getMessage());
        }
    }

    public function columns(): array
    {
        return [
            Column::make("Lcr uuid", "lcr_uuid")->hideIf(true),

            Column::make("Digits", "digits")
                ->sortable(),

            Column::make("Direction", "lcr_direction")
                ->sortable(),

            BooleanColumn::make("Enabled", "enabled")
                ->sortable(),
        ];
    }

    public function builder(): Builder
    {
        $query = Lcr::query()
            ->orderBy('insert_date', 'desc');
        return $query;
    }
}
