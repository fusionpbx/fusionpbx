<?php

namespace App\Livewire;

use App\Models\Module;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;

class ModulesTable extends DataTableComponent
{
    protected $model = Module::class;

    public function configure(): void
    {
        $canEdit = auth()->user()->hasPermission('module_edit');
        $this->setPrimaryKey('module_uuid')
            ->setTableAttributes([
                'class' => 'table table-striped table-hover table-bordered'
            ])
            ->setSearchEnabled()
            ->setSearchPlaceholder('Search Modules')
            ->setPerPageAccepted([10, 25, 50, 100, 250])
            ->setDefaultPerPage(100)
            ->setTableRowUrl(function ($row) use ($canEdit)
            {
                return $canEdit
                    ? route('modules.edit', $row->module_uuid)
                    : null;
            })
            ->setPaginationEnabled();
    }

    public function bulkActions(): array
    {
        $bulkActions = [];

        if (auth()->user()->hasPermission('module_edit'))
        {
            $bulkActions['markEnabled'] = 'Mark as Enabled';
            $bulkActions['markDisabled'] = 'Mark as Disabled';
        }

        if (auth()->user()->hasPermission('module_delete'))
        {
            $bulkActions['bulkDelete'] = 'Delete';
        }

        if (auth()->user()->hasPermission('module_add'))
        {
            $bulkActions['bulkCopy'] = 'Copy';
        }

        return $bulkActions;
    }

    public function markEnabled()
    {
        if (!auth()->user()->hasPermission('module_edit'))
        {
            session()->flash('error', 'You do not have permission to mark modules as enabled.');
            return;
        }

        $selectedRows = $this->getSelected();

        Module::whereIn('module_uuid', $selectedRows)->update(['module_enabled' => 'true']);

        $this->clearSelected();
        $this->dispatch('refresh');
        session()->flash('success', 'The modules were successfully enabled.');
    }

    public function markDisabled()
    {
        if (!auth()->user()->hasPermission('module_edit'))
        {
            session()->flash('error', 'You do not have permission to mark modules as disabled.');
            return;
        }

        $selectedRows = $this->getSelected();

        Module::whereIn('module_uuid', $selectedRows)->update(['module_enabled' => 'false']);

        $this->clearSelected();
        $this->dispatch('refresh');
        session()->flash('success', 'The modules were successfully disabled.');
    }


    public function bulkDelete()
    {
        if (!auth()->user()->hasPermission('module_delete'))
        {
            session()->flash('error', 'You do not have permission to delete modules.');
            return;
        }

        $selectedRows = $this->getSelected();

        try
        {
            DB::beginTransaction();

            Module::whereIn('module_uuid', $selectedRows)->delete();

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
            session()->flash('success', 'Modules successfully deleted.');
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            session()->flash('error', 'There was a problem deleting the modules: ' . $e->getMessage());
        }
    }

    public function bulkCopy()
    {
        if (!auth()->user()->hasPermission('module_add'))
        {
            session()->flash('error', 'You do not have permission to copy modules.');
            return;
        }

        $selectedRows = $this->getSelected();

        try
        {
            DB::beginTransaction();

            foreach ($selectedRows as $moduleUuid)
            {
                $originalModule = Module::findOrFail($moduleUuid);

                $newModule = $originalModule->replicate();
                $newModule->module_uuid = Str::uuid();
                $newModule->module_label = $originalModule->module_label . ' (Copy)';
                $newModule->module_name = $originalModule->module_name . ' (Copy)';
                $newModule->save();
            }

            DB::commit();

            $this->clearSelected();
            $this->dispatch('refresh');
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            throw $e;
            session()->flash('error', 'There was a problem copying the modules: ' . $e->getMessage());
        }
    }

    public function columns(): array
    {
        return [
            Column::make("Module uuid", "module_uuid")->hideIf(true),

            Column::make("Module label", "module_label")
                ->sortable(),

            BooleanColumn::make("Module enabled", "module_enabled")
                ->sortable(),
        ];
    }

    public function builder(): Builder
    {
        $query = Module::query()
            ->orderBy('module_name', 'asc');
        return $query;
    }
}
